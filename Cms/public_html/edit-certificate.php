<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

/* ===== Timeout ===== */
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: Login?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

/* ===== Debug (matikan di production) ===== */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ===== Log aktivitas ===== */
function logActivity($action) {
    $logFile = 'logs.json';
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
    if (!is_array($logs)) $logs = [];
    $logs[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user'      => $_SESSION['full_name'] ?? $_SESSION['user'],
        'action'    => $action
    ];
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ===== Load data ===== */
$jsonFile = 'certificates.json';
$raw = file_exists($jsonFile) ? file_get_contents($jsonFile) : '';
$certificates = $raw ? json_decode($raw, true) : [];
if (!is_array($certificates)) $certificates = [];

/* ===== Ambil ID & data ===== */
if (!isset($_GET['id']) || !array_key_exists($_GET['id'], $certificates)) {
    echo "Sertifikat tidak ditemukan.";
    exit;
}
$id   = $_GET['id'];
$cert = $certificates[$id];

/* ===== Parse periode ke start/end untuk form ===== */
$startVal = '';
$endVal   = '';
if (!empty($cert['period'])) {
    if (strpos($cert['period'], ' - ') !== false) {
        [$s, $e] = explode(' - ', $cert['period'], 2);
        $startVal = trim($s);
        $endVal   = trim($e);
    } else {
        $startVal = trim($cert['period']);
    }
}
if (!preg_match('/^\d{4}-\d{2}$/', (string)$startVal)) $startVal = '';
if ($endVal !== '' && !preg_match('/^\d{4}-\d{2}$/', (string)$endVal)) $endVal = '';

/* ===== Handler POST ===== */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $title        = trim($_POST['title'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $link         = trim($_POST['link'] ?? '');
        $start        = trim($_POST['start'] ?? '');
        $end          = trim($_POST['end'] ?? '');

        // Validasi dasar
        if ($title === '' || $organization === '' || $start === '') {
            $error = 'Judul, Penyelenggara, dan Periode (Dari) wajib diisi.';
        } elseif ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
            $error = 'Format Link tidak valid.';
        } elseif (!preg_match('/^\d{4}-\d{2}$/', $start)) {
            $error = 'Format periode (Dari) harus YYYY-MM.';
        } elseif ($end !== '' && !preg_match('/^\d{4}-\d{2}$/', $end)) {
            $error = 'Format periode (Sampai) harus YYYY-MM.';
        }

        /* === Upload gambar (opsional) === */
        $newImagePath = $cert['image'] ?? ''; // default: pakai lama
        if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Upload gambar gagal.';
            } else {
                $tmp  = $_FILES['image']['tmp_name'];
                $size = (int)$_FILES['image']['size'];

                // Maks 2MB
                if ($size > 2 * 1024 * 1024) {
                    $error = 'Ukuran gambar maksimal 2MB.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $tmp);
                    finfo_close($finfo);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($allowed[$mime])) {
                        $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.';
                    } else {
                        $dir = 'img/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $name = 'cert_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                        $dest = $dir . $name;
                        if (!move_uploaded_file($tmp, $dest)) {
                            $error = 'Gagal menyimpan gambar di server.';
                        } else {
                            $newImagePath = $dest;
                        }
                    }
                }
            }
        }

        if (!$error) {
            $period = $start . ($end !== '' ? ' - ' . $end : '');

            // Update record (pertahankan 'published' bila ada)
            $certificates[$id] = [
                'title'        => $title,
                'organization' => $organization,
                'link'         => $link,
                'period'       => $period,
                'image'        => $newImagePath,
                'published'    => !empty($cert['published'])
            ];

            file_put_contents($jsonFile, json_encode($certificates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            logActivity("Mengedit sertifikat: " . $title);
            unset($_SESSION['csrf']); // one-time token

            header("Location: List-Sertifikat?success=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Sertifikat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="img/Logo.png" type="image/png">
  <style>
    :root{ --sidebar-w:240px; --topbar-h:56px; }
    body{ background:#f8f9fa; }

    /* Desktop beri ruang sidebar */
    @media (min-width: 992px){ body{ padding-left: var(--sidebar-w); } }

    /* Sidebar */
    .sidebar{ width:var(--sidebar-w); height:100vh; position:fixed; inset:0 auto 0 0;
      background:#212529; color:#fff; display:flex; flex-direction:column; z-index:1040;
      transition: transform .3s ease; transform: translateX(0); }
    .sidebar .branding{ padding:1rem; text-align:center; border-bottom:1px solid rgba(255,255,255,.1); position:relative; }
    .sidebar .branding img{ height:80px; }
    .sidebar .brand-name{ font-weight:600; }
    .sidebar .close-btn{ position:absolute; right:1rem; top:1rem; color:#fff; display:none; cursor:pointer; }
    .sidebar .nav-link{ color:#ccc; padding:.75rem 1rem; display:flex; align-items:center; gap:.5rem; border-radius:10px; margin:.2rem .5rem; }
    .sidebar .nav-link:hover{ background:rgba(255,255,255,.1); color:#fff; }
    .sidebar .nav-link.active{ background:#ffc107; color:#000; font-weight:600; }
    .sidebar .user-info{ margin-top:auto; padding:1rem; border-top:1px solid rgba(255,255,255,.1); font-size:.9rem; }

    /* Topbar & overlay (mobile) */
    .topbar{ display:none; position:fixed; inset:0 0 auto 0; height:var(--topbar-h); background:#fff; border-bottom:1px solid #e9ecef;
      z-index:1035; align-items:center; padding:0 .75rem; gap:.75rem; }
    .topbar .brand{ display:flex; align-items:center; gap:.5rem; font-weight:700; }
    .topbar img{ height:28px; }
    .sidebar-overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1039; }
    .sidebar-overlay.show{ display:block; }

    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .sidebar .close-btn{ display:block; }
      .topbar{ display:flex; }
      .main-wrap{ padding-top: calc(var(--topbar-h) + 1rem); }
    }
  </style>
</head>
<body>

<!-- Topbar (mobile) -->
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
    <span class="close-btn d-lg-none" onclick="toggleSidebar()" aria-label="Tutup">&times;</span>
  </div>
  <a href="Dashboard" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="Remote" class="nav-link"><i class="bi bi-pencil-square"></i> Editor Teks</a>
  <a href="Ganti-Password" class="nav-link"><i class="bi bi-key"></i> Ganti Password</a>
  <a href="List-Sertifikat" class="nav-link active"><i class="bi bi-award-fill me-1"></i> Sertifikat</a>
  <a href="List-Portofolio" class="nav-link"><i class="bi bi-folder2-open"></i> Portofolio</a>
  <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <div class="user-info">
    <i class="bi bi-person-circle me-1"></i>
    <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
    <span class="badge bg-secondary">User</span>
  </div>
</nav>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Main -->
<div class="container main-wrap py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
          <h4 class="mb-4 fw-semibold text-primary">
            <i class="bi bi-pencil-square me-2"></i> Edit Sertifikat
          </h4>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

            <div class="mb-3">
              <label for="title" class="form-label">Judul Sertifikat</label>
              <input type="text" class="form-control" id="title" name="title"
                     value="<?= htmlspecialchars($cert['title'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
              <label for="organization" class="form-label">Penyelenggara</label>
              <input type="text" class="form-control" id="organization" name="organization"
                     value="<?= htmlspecialchars($cert['organization'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
              <label for="link" class="form-label">Link Dokumen (opsional)</label>
              <input type="url" class="form-control" id="link" name="link"
                     value="<?= htmlspecialchars($cert['link'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Periode Sertifikat</label>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="start" class="form-label">Dari</label>
                  <input type="month" class="form-control" id="start" name="start"
                         value="<?= htmlspecialchars($startVal) ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="end" class="form-label">Sampai (opsional)</label>
                  <input type="month" class="form-control" id="end" name="end"
                         value="<?= htmlspecialchars($endVal) ?>">
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label for="image" class="form-label">Gambar Sertifikat (opsional)</label>
              <?php if (!empty($cert['image'])): ?>
                <div class="mb-2">
                  <img src="<?= htmlspecialchars($cert['image']) ?>" alt="Preview Sertifikat"
                       class="img-fluid rounded shadow-sm" style="max-height:180px;">
                </div>
              <?php endif; ?>
              <input type="file" class="form-control" id="image" name="image"
                     accept="image/jpeg,image/png,image/webp">
              <div class="form-text">Maks 2MB. Format: JPG/PNG/WebP.</div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="List-Sertifikat" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Perubahan
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle sidebar (mobile)
  function toggleSidebar() {
    const sb = document.getElementById('sidebarMenu');
    const ov = document.querySelector('.sidebar-overlay');
    sb.classList.toggle('show');
    ov.classList.toggle('show');
  }

  // Bootstrap validation helper
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
