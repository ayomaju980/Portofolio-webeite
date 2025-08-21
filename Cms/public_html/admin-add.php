<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

/* === Fungsi Log Activity === */
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

/* === Fungsi Rapikan Deskripsi === */
function cleanDescription($desc) {
    $desc = preg_replace("/[ \t]+/", ' ', $desc);
    $desc = preg_replace("/(\r?\n){2,}/", "\n", $desc);
    return trim($desc);
}

/* === CSRF Token === */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['csrf']) {
        die('CSRF token tidak valid.');
    }

    $title = trim($_POST['title'] ?? '');
    $date  = trim($_POST['date'] ?? '');
    $types = $_POST['type'] ?? [];
    if (!is_array($types)) $types = [];

    if ($title === '' || $date === '') {
        $error = "Judul dan tanggal wajib diisi.";
    } elseif (!isset($_FILES['img']) || $_FILES['img']['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload gambar gagal.";
    } else {
        // Validasi file gambar
        $tmp = $_FILES['img']['tmp_name'];
        $maxSize = 2 * 1024 * 1024;
        if ($_FILES['img']['size'] > $maxSize) {
            $error = "Ukuran gambar maksimal 2MB.";
        } else {
            // MIME check yang lebih andal
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $error = "Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.";
            }
        }

        if (!$error) {
            // Pastikan data.json ada & valid
            $raw = @file_get_contents('data.json');
            $data = $raw ? json_decode($raw, true) : [];
            if (!is_array($data)) $data = [];

            // Pastikan folder img/
            $dir = 'img';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            // Generate nama file aman & unik
            $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($_FILES['img']['name'], PATHINFO_FILENAME));
            $ext = $allowed[$mime] ?? strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
            $imgName = time() . '_' . substr($safeBase, 0, 40) . '.' . $ext;
            $uploadPath = $dir . '/' . $imgName;

            if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $uploadPath)) {
                $error = "Gagal menyimpan gambar.";
            } else {
                // Hitung ID baru
                $newId = count($data) > 0 ? (max(array_column($data, 'id')) + 1) : 1;

                $new_item = [
                    "id"          => $newId,
                    "title"       => $title, // simpan raw; escape saat output
                    "date"        => $date,  // format YYYY-MM
                    "type"        => array_values($types),
                    "img"         => $uploadPath,
                    "link"        => trim($_POST['link'] ?? '#'),
                    "client"      => trim($_POST['client'] ?? ''),
                    "description" => cleanDescription($_POST['description'] ?? '')
                ];

                $data[] = $new_item;
                file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                logActivity("Menambahkan portofolio: " . $title);
                $success = true;
                unset($_SESSION['csrf']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Portofolio</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="img/Logo.png" type="image/png">
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<style>
  :root{
    --sidebar-w: 240px;
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

  /* ===== Main ===== */
  .main-content { margin-left: var(--sidebar-w); padding: 1.5rem; }
  .form-label.required::after { content: " *"; color: #dc3545; }

  /* ===== Toast anim ===== */
  .toast { animation: slideIn .5s ease; }
  @keyframes slideIn { from { transform: translateY(-20px); opacity:0; } to { transform: translateY(0); opacity:1; } }

  /* ===== Responsive ===== */
  @media (max-width: 991.98px){
    /* jadikan sidebar off-canvas */
    .sidebar{ transform: translateX(-100%); }
    .sidebar.show{ transform: translateX(0); }
    .sidebar .close-btn{ display:block; }
    .topbar{ display:flex; }
    .main-content{ margin-left: 0; padding-top: calc(var(--topbar-h) + 1rem); }
  }

  @media (max-width: 575.98px){
    .card{ padding: 1rem !important; }
    .d-flex.gap-2.mt-4 { flex-direction: column; }
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

<main class="main-content">
  <h4 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Tambah Portofolio</h4>

  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success">Portofolio berhasil ditambahkan!</div><?php endif; ?>

  <div class="card shadow-sm p-4">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="token" value="<?= $_SESSION['csrf'] ?>">
      <div class="mb-3">
        <label class="form-label required">Judul</label>
        <input type="text" name="title" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label required">Tanggal</label>
        <input type="month" name="date" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Kategori:</label>
        <div class="row">
        <?php
          $types = ['UI DESIGN','Mobile Apps','Landing Page','UX Design','UX Writer','Web Design','Case Study','Real Project','Personal Project','Academic Project','Design Challenge'];
          foreach($types as $i=>$type):
        ?>
          <div class="col-6 col-sm-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="type[]" value="<?= htmlspecialchars($type) ?>" id="type<?= $i ?>">
              <label class="form-check-label" for="type<?= $i ?>"><?= htmlspecialchars($type) ?></label>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label required">Upload Gambar</label>
        <input type="file" name="img" class="form-control" accept="image/*" required>
        <small class="text-muted">Maksimal 2MB. Format: JPG, PNG, WebP.</small>
      </div>
      <div class="mb-3">
        <label class="form-label">Nama Projek:</label>
        <input type="text" name="client" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Deskripsi:</span>
          <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#aiModal">
            <i class="bi bi-stars"></i> Generate AI
          </button>
        </label>
        <textarea id="descriptionArea" name="description"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Link Proyek:</label>
        <input type="text" name="link" class="form-control" placeholder="https://...">
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="List-Portofolio" class="btn btn-secondary">Kembali</a>
      </div>
    </form>
  </div>
</main>

<!-- Modal Generate AI -->
<div class="modal fade" id="aiModal" tabindex="-1" aria-labelledby="aiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header flex-column">
        <h5 class="modal-title" id="aiModalLabel">Buat Deskripsi dengan AI</h5>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
          Masukkan ide atau poin-poin utama, lalu biarkan AI menyusunnya menjadi deskripsi.
        </p>
      </div>
      <div class="modal-body">
        <textarea id="aiIdea" class="form-control" rows="6" placeholder="Tulis ide atau poin-poin di sini..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" id="aiModalSubmit" class="btn btn-primary">✨ Buat dengan AI</button>
      </div>
    </div>
  </div>
</div>

<?php if ($success): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div class="toast align-items-center text-bg-success show shadow rounded-3" role="alert">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i> Portofolio berhasil ditambahkan!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* TinyMCE */
tinymce.init({
  selector: '#descriptionArea',
  height: 300,
  menubar: false,
  plugins: 'lists link code',
  toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link | code',
  branding: false
});

/* AI Modal (dummy generator) */
document.getElementById('aiModalSubmit').addEventListener('click', () => {
  const idea = document.getElementById('aiIdea').value.trim();
  if (!idea) {
    alert('Masukkan ide terlebih dahulu.');
    return;
  }
  const generatedText = "Deskripsi otomatis dari AI berdasarkan ide:<br><br>" + idea.replace(/\n/g, '<br>');
  tinymce.get('descriptionArea').setContent(generatedText);
  const modal = bootstrap.Modal.getInstance(document.getElementById('aiModal'));
  modal.hide();
});

/* Sidebar toggle (mobile/tablet) */
function toggleSidebar() {
  const sb = document.getElementById('sidebarMenu');
  const ov = document.querySelector('.sidebar-overlay');
  sb.classList.toggle('show');
  ov.classList.toggle('show');
}

/* Redirect setelah sukses (opsional) */
<?php if ($success): ?>
  setTimeout(() => { window.location.href = "dashboard.php"; }, 3000);
<?php endif; ?>

/* Nonaktifkan klik kanan (opsional) */
document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
