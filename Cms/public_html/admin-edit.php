<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

// Fungsi log aktivitas
function logActivity($action) {
    $logFile = 'logs.json';
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
    if (!is_array($logs)) $logs = [];

    $logs[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['full_name'] ?? $_SESSION['user'],
        'action' => $action
    ];

    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Ambil data dari file JSON (aman terhadap file kosong/invalid)
$rawData = @file_get_contents('data.json');
$data = $rawData ? json_decode($rawData, true) : [];
if (!is_array($data)) $data = [];

// Ambil ID dari URL
$id = $_GET['id'] ?? null;
$index = array_search($id, array_column($data, 'id'));

// Kalau ID tidak valid
if ($id === null || $index === false) {
    die("Data tidak ditemukan.");
}

$item = $data[$index];
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi wajib isi
    if (empty($_POST['title']) || empty($_POST['date'])) {
        $error = "Judul dan Tanggal wajib diisi.";
    } elseif (empty($_POST['type'])) {
        $error = "Pilih minimal 1 kategori.";
    }

    if (!$error) {
        // Default: pakai gambar lama
        $imgRelativePath = $item['img'];

        // Jika ada file baru, validasi dan simpan
        if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['img']['tmp_name'];
            $size = $_FILES['img']['size'] ?? 0;

            // Cek ukuran (maks 2MB)
            if ($size > 2 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 2MB.";
            } else {
                // Cek MIME (simple)
                $fi = new finfo(FILEINFO_MIME_TYPE);
                $mime = $fi->file($tmp);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

                if (!isset($allowed[$mime])) {
                    $error = "Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.";
                } else {
                    // Pastikan folder img/
                    $imgDir = 'img/';
                    if (!is_dir($imgDir)) @mkdir($imgDir, 0777, true);

                    $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($_FILES['img']['name'], PATHINFO_FILENAME));
                    $ext = $allowed[$mime];
                    $imgName = uniqid('img_', true) . '_' . substr($safeBase, 0, 40) . '.' . $ext;
                    $dest = $imgDir . $imgName;

                    if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $dest)) {
                        $error = "Gagal menyimpan gambar baru.";
                    } else {
                        $imgRelativePath = $dest;
                    }
                }
            }
        }

        if (!$error) {
            // Format tanggal hanya ambil tahun-bulan
            $formattedDate = date('Y-m', strtotime($_POST['date']));

            // Pastikan type berupa array
            $types = $_POST['type'];
            if (!is_array($types)) $types = [$types];

            // Simpan data hasil edit
            $data[$index] = [
                "id"          => (int)$id,
                "title"       => $_POST['title'],
                "date"        => $formattedDate,
                "type"        => $types,
                "description" => $_POST['description'],
                "img"         => $imgRelativePath,
                "client"      => $_POST['client'] ?? '',
                "link"        => $_POST['link'] ?? ''
            ];

            // Pertahankan status publish dan waktu publish kalau ada
            if (isset($item['published'])) {
                $data[$index]['published'] = $item['published'];
            }
            if (isset($item['published_at'])) {
                $data[$index]['published_at'] = $item['published_at'];
            }

            // Update waktu edit
            $data[$index]['updated_at']   = date('Y-m-d H:i:s');
            $data[$index]['updated_unix'] = time();

            // Simpan ke file JSON
            file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Log aktivitas
            logActivity("Mengedit portofolio: " . $_POST['title']);

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Portofolio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="img/Logo.png" type="image/png">
  <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
  <script>
  tinymce.init({
    selector: '#tinymceEditor',
    height: 320,
    menubar: false,
    plugins: 'lists link image preview code',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link image | preview code',
    branding: false
  });
  </script>
  <style>
    :root{
      --sidebar-w: 240px;   /* konsisten pakai -w */
      --topbar-h: 56px;
    }
    body { background-color: #f5f7fa; }

    /* ===== Sidebar (desktop default, mobile off-canvas) ===== */
    .sidebar {
      width: var(--sidebar-w);
      height: 100vh;
      position: fixed; inset: 0 auto 0 0;
      background:#212529; color:#fff;
      display:flex; flex-direction:column;
      z-index:1040; transition: transform .3s ease;
      transform: translateX(0);
    }
    .sidebar .branding { padding:1rem; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); position:relative; }
    .sidebar .branding img{ height:80px; }
    .sidebar .brand-name{ font-weight:600; }
    .sidebar .close-btn{ position:absolute; right:1rem; top:1rem; font-size:1.25rem; cursor:pointer; color:#fff; display:none; }
    .sidebar .nav-link{ color:#ccc; padding:.75rem 1rem; display:flex; align-items:center; gap:.5rem; border-radius:10px; margin:.2rem .5rem; }
    .sidebar .nav-link:hover{ background:rgba(255,255,255,.1); color:#fff; }
    .sidebar .nav-link.active{ background:#ffc107; color:#000; font-weight:600; }
    .sidebar .user-info{ margin-top:auto; padding:1rem; border-top:1px solid rgba(255,255,255,0.1); font-size:.9rem; }

    /* ===== Overlay (mobile) ===== */
    .sidebar-overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1039; }
    .sidebar-overlay.show{ display:block; }

    /* ===== Topbar (mobile/tablet) ===== */
    .topbar{
      display:none; position:fixed; inset:0 0 auto 0; height:var(--topbar-h);
      background:#fff; border-bottom:1px solid #e9ecef; z-index:1035;
      align-items:center; padding:0 .75rem; gap:.75rem;
    }
    .topbar .brand{ display:flex; align-items:center; gap:.5rem; font-weight:700; }
    .topbar img{ height:28px; }

    /* ===== Card / Form tweaks ===== */
    .card { border-radius: 14px; }
    .form-label.required::after { content: " *"; color: #dc3545; margin-left: 2px; }
    .img-preview { max-width: 220px; border-radius: .5rem; }

    /* ===== Main spacing ===== */
    .main-content { margin-left: var(--sidebar-w); padding: 1.5rem; } /* desktop default */

    /* Toast anim */
    .toast { animation: slideIn .5s ease; }
    @keyframes slideIn { from { transform: translateY(-20px); opacity:0; } to { transform: translateY(0); opacity:1; } }

    /* ====== RESPONSIVE ====== */
    /* ≤ 991.98px : tablet & smartphone */
    @media (max-width: 991.98px) {
      /* jadikan sidebar off-canvas */
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .sidebar .close-btn{ display:block; }
      .topbar{ display:flex; }

      .main-content { margin-left: 0; padding-top: calc(var(--topbar-h) + 1rem); } /* hindari ketutup topbar */

      /* Form spacing tighter on small screens */
      .card { padding: 1rem !important; }
      .img-preview { max-width: 160px; }
    }

    /* Smartphone (≤ 575.98px) */
    @media (max-width: 575.98px) {
      .page-title { font-size: 1.1rem; }
      .btn { padding: .5rem .75rem; }
      .grid-tight > [class*="col-"] { margin-bottom: .5rem; }
      .img-preview { max-width: 130px; }
    }

    /* Tablet only (576px – 991.98px) */
    @media (min-width: 576px) and (max-width: 991.98px) {
      .page-title { font-size: 1.25rem; }
    }

    /* Desktop (≥ 992px) – refine spacing */
    @media (min-width: 992px) {
      .main-content { padding: 2rem; }
    }
  </style>
</head>
<body>

<!-- Topbar (mobile/tablet) -->
<header class="topbar d-lg-none">
  <button class="btn btn-outline-secondary btn-sm" onclick="toggleSidebar()" aria-label="Toggle sidebar">
    <i class="bi bi-list" style="font-size:1.2rem"></i>
  </button>
  <div class="brand">
    <img src="img/ICON.png" alt="Logo">
    <span>Portofolio-CMS</span>
  </div>
</header>

<!-- Sidebar -->
<nav id="sidebarMenu" class="sidebar" aria-label="Sidebar navigasi">
  <div class="branding">
    <img src="img/ICON.png" alt="Logo">
    <div class="brand-name">Portofolio-CMS</div>
    <span class="close-btn d-lg-none" onclick="toggleSidebar()" aria-label="Close">&times;</span>
  </div>
  <a href="Dashboard" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="Remote" class="nav-link"><i class="bi bi-pencil-square"></i> Editor Teks</a>
  <a href="Ganti-Password" class="nav-link"><i class="bi bi-key"></i> Ganti Password</a>
  <a href="List-Sertifikat" class="nav-link"><i class="bi bi-award-fill me-1"></i> Sertifikat</a>
  <a href="List-Portofolio" class="nav-link active"><i class="bi bi-folder2-open"></i> Portofolio</a>
  <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <div class="user-info">
    <i class="bi bi-person-circle me-1"></i>
    <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
    <span class="badge bg-secondary">User</span>
  </div>
</nav>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Main -->
<main class="main-content">
  <div class="container-fluid px-0 px-sm-2 px-md-3">
    <h4 class="mb-4 fw-semibold page-title"><i class="bi bi-pencil-square me-2"></i>Edit Data Portofolio</h4>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm p-4">
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label required">Judul</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label required">Tanggal</label>
          <input type="month" name="date" class="form-control" value="<?= htmlspecialchars($item['date']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label required">Kategori</label>
          <div class="row grid-tight">
            <?php
              $typeList = ['UI DESIGN', 'Mobile Apps', 'Landing Page', 'UX Design', 'UX Writer', 'Web Design', 'Case Study', 'Real Project','Personal Project', 'Academic Project', 'Design Challenge'];
              foreach ($typeList as $i => $type):
                $checked = in_array($type, $item['type']) ? 'checked' : '';
            ?>
              <!-- 2 kolom di smartphone, 3 kolom di tablet, bebas di desktop -->
              <div class="col-6 col-sm-4 col-lg-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="type[]" value="<?= htmlspecialchars($type) ?>" id="type<?= $i ?>" <?= $checked ?>>
                  <label class="form-check-label" for="type<?= $i ?>"><?= htmlspecialchars($type) ?></label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea id="tinymceEditor" name="description"><?= $item['description'] ?></textarea>
        </div>

        <div class="row">
          <div class="col-12 col-md-6">
            <div class="mb-3">
              <label class="form-label">Nama Proyek</label>
              <input type="text" name="client" class="form-control" value="<?= htmlspecialchars($item['client']) ?>">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="mb-3">
              <label class="form-label">Link Proyek</label>
              <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($item['link']) ?>">
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Gambar Saat Ini</label><br>
          <img src="<?= htmlspecialchars($item['img']) ?>" class="img-fluid mb-2 shadow-sm border img-preview" alt="Preview">
          <input type="file" name="img" class="form-control" accept="image/*">
          <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar (maks 2MB; JPG/PNG/WebP).</small>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
          <a href="List-Portofolio" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Batal</a>
        </div>
      </form>
    </div>
  </div>
</main>

<?php if ($success): ?>
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast align-items-center text-bg-success show shadow rounded-3" role="alert">
      <div class="d-flex">
        <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i> Portofolio berhasil diperbarui!</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle sidebar (mobile/tablet)
function toggleSidebar() {
  const sb = document.getElementById('sidebarMenu');
  const ov = document.querySelector('.sidebar-overlay');
  sb.classList.toggle('show');
  ov.classList.toggle('show');
}

<?php if ($success): ?>
  setTimeout(() => { window.location.href = "dashboard.php"; }, 3000);
<?php endif; ?>

// Nonaktifkan klik kanan (opsional)
document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
