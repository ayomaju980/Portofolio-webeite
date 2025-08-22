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
            // MIME check
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $error = "Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.";
            }
        }

        if (!$error) {
            // Ambil data lama
            $raw = @file_get_contents('data.json');
            $data = $raw ? json_decode($raw, true) : [];
            if (!is_array($data)) $data = [];

            // Pastikan folder img/ ada
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
                    "title"       => $title,
                    "date"        => $date,
                    "type"        => array_values($types),
                    "img"         => $uploadPath,
                    "link"        => trim($_POST['link'] ?? '#'),
                    "client"      => trim($_POST['client'] ?? ''),
                    "description" => cleanDescription($_POST['description'] ?? ''),
                    "prd_link"    => trim($_POST['prd_link'] ?? '')   // <--- tambahan PRD
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
  :root{ --sidebar-w:240px; --topbar-h:56px; }
  body{ background:#f5f7fa; }
  .sidebar{ width:var(--sidebar-w); height:100vh; position:fixed; inset:0 auto 0 0; background:#212529; color:#fff; display:flex; flex-direction:column; z-index:1040; transition:.3s; transform:translateX(0);}
  .sidebar .branding{ padding:1rem; text-align:center; border-bottom:1px solid rgba(255,255,255,.1); position:relative;}
  .sidebar .branding img{ height:80px;}
  .sidebar .brand-name{ font-weight:600;}
  .sidebar .close-btn{ position:absolute; right:1rem; top:1rem; display:none; cursor:pointer;}
  .sidebar .nav-link{ color:#ccc; padding:.75rem 1rem; border-radius:10px;}
  .sidebar .nav-link:hover{ background:rgba(255,255,255,.1); color:#fff;}
  .sidebar .nav-link.active{ background:#ffc107; color:#000; font-weight:600;}
  .sidebar-overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1039;}
  .sidebar-overlay.show{ display:block;}
  .topbar{ display:none; position:fixed; inset:0 0 auto 0; height:var(--topbar-h); background:#fff; border-bottom:1px solid #e9ecef; z-index:1035; align-items:center; padding:0 .75rem; gap:.75rem;}
  .topbar img{ height:28px;}
  .main-content{ margin-left:var(--sidebar-w); padding:1.5rem;}
  .form-label.required::after{ content:" *"; color:#dc3545;}
  .toast{ animation:slideIn .5s ease;}
  @keyframes slideIn{from{transform:translateY(-20px);opacity:0;}to{transform:translateY(0);opacity:1;}}
  @media (max-width:991.98px){ .sidebar{transform:translateX(-100%);} .sidebar.show{transform:translateX(0);} .sidebar .close-btn{display:block;} .topbar{display:flex;} .main-content{margin-left:0;padding-top:calc(var(--topbar-h) + 1rem);} }
</style>
</head>
<body>
<!-- Topbar (mobile) -->
<header class="topbar d-lg-none">
  <button class="btn btn-outline-secondary btn-sm" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
  <div class="brand"><img src="img/ICON.png" alt="Logo"><span>Portofolio-CMS</span></div>
</header>

<!-- Sidebar -->
<nav id="sidebarMenu" class="sidebar">
  <div class="branding">
    <img src="img/ICON.png" alt="Logo">
    <div class="brand-name">Portofolio-CMS</div>
    <span class="close-btn d-lg-none" onclick="toggleSidebar()">&times;</span>
  </div>
  <a href="Dashboard" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="Remote" class="nav-link"><i class="bi bi-pencil-square"></i> Editor Teks</a>
  <a href="Ganti-Password" class="nav-link"><i class="bi bi-key"></i> Ganti Password</a>
  <a href="List-Sertifikat" class="nav-link"><i class="bi bi-award-fill"></i> Sertifikat</a>
  <a href="List-Portofolio" class="nav-link active"><i class="bi bi-folder2-open"></i> Portofolio</a>
  <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <div class="user-info p-3 border-top border-secondary">
    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?>
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
      <div class="mb-3"><label class="form-label required">Judul</label><input type="text" name="title" class="form-control" required></div>
      <div class="mb-3"><label class="form-label required">Tanggal</label><input type="month" name="date" class="form-control" required></div>
      <div class="mb-3">
        <label class="form-label">Kategori:</label>
        <div class="row">
        <?php $types=['UI DESIGN','Mobile Apps','Landing Page','UX Design','UX Writer','Web Design','Case Study','Real Project','Personal Project','Academic Project','Design Challenge'];
        foreach($types as $i=>$type): ?>
          <div class="col-6 col-sm-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="type[]" value="<?= htmlspecialchars($type) ?>" id="type<?= $i ?>">
              <label class="form-check-label" for="type<?= $i ?>"><?= htmlspecialchars($type) ?></label>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
      <div class="mb-3"><label class="form-label required">Upload Gambar</label><input type="file" name="img" class="form-control" accept="image/*" required></div>
      <div class="mb-3"><label class="form-label">Nama Projek:</label><input type="text" name="client" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Deskripsi:</label><textarea id="descriptionArea" name="description"></textarea></div>
      <div class="mb-3"><label class="form-label">Link Proyek:</label><input type="url" name="link" class="form-control" placeholder="https://..."></div>
      <div class="mb-3"><label class="form-label">Link Dokumen PRD:</label><input type="url" name="prd_link" class="form-control" placeholder="https://docs.google.com/..."></div>
      <div class="d-flex gap-2 mt-4"><button type="submit" class="btn btn-primary">Simpan</button><a href="List-Portofolio" class="btn btn-secondary">Kembali</a></div>
    </form>
  </div>
</main>

<?php if ($success): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div class="toast align-items-center text-bg-success show shadow rounded-3">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i> Portofolio berhasil ditambahkan!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
tinymce.init({ selector:'#descriptionArea', height:300, menubar:false, plugins:'lists link code', toolbar:'undo redo | styles | bold italic underline | bullist numlist | link | code', branding:false});
function toggleSidebar(){ document.getElementById('sidebarMenu').classList.toggle('show'); document.querySelector('.sidebar-overlay').classList.toggle('show'); }
<?php if ($success): ?> setTimeout(()=>{window.location.href="dashboard.php";},3000); <?php endif; ?>
</script>
</body>
</html>
