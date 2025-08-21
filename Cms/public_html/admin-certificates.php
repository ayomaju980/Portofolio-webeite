<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

/* === Session Timeout === */
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: Login?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

/* === Debug (matikan di production) === */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* === Load JSON aman === */
$jsonFile = 'certificates.json';
$raw = file_exists($jsonFile) ? file_get_contents($jsonFile) : '';
$certificates = $raw ? json_decode($raw, true) : [];
if (!is_array($certificates)) $certificates = [];

/* ==== FILTER (tanpa reindex!) ==== */
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
if ($search !== '') {
    $certificates = array_filter($certificates, function($cert) use ($search) {
        return strpos(strtolower($cert['title'] ?? ''), $search) !== false;
    });
    // JANGAN array_values() -> biar key asli tetap jadi "id" yang valid
}

/* Base URL gambar (opsional) */
$cms_url = ''; // contoh: 'https://cms.domainmu.id/'

/* ==== PAGINATION pakai key asli ==== */
$perPage = 4;
$totalItems = count($certificates);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;

$allKeys  = array_keys($certificates);
$start    = ($page - 1) * $perPage;
$pageKeys = array_slice($allKeys, $start, $perPage);

$certificatesPaginated = [];
foreach ($pageKeys as $k) { $certificatesPaginated[$k] = $certificates[$k]; }

/* ==== Helper format periode ==== */
function formatPeriode($periode) {
    $bulanIndo = [
        '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
        '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
        '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
    ];
    $format = function($value) use ($bulanIndo) {
        $value = trim((string)$value);
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            [$tahun, $bulan] = explode('-', $value);
            return ($bulanIndo[$bulan] ?? $bulan) . ' ' . $tahun;
        }
        return htmlspecialchars($value);
    };
    if ($periode === null || $periode === '') return '-';
    $parts = explode(' - ', $periode);
    return count($parts) === 2 ? $format($parts[0]) . ' - ' . $format($parts[1]) : $format($periode);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Portofolio CMS - Manajemen Sertifikat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="img/Logo.png" type="image/png">
  <style>
    :root{ --sidebar-w:240px; --topbar-h:56px; }
    body{ background:#f8f9fa; }

    /* desktop: body disisihkan buat sidebar */
    @media (min-width:992px){ body{ padding-left:var(--sidebar-w); } }

    /* ===== Sidebar / Topbar ===== */
    .sidebar{ width:var(--sidebar-w); height:100vh; position:fixed; inset:0 auto 0 0; background:#212529; color:#fff;
      display:flex; flex-direction:column; z-index:1040; transition:transform .3s ease; transform:translateX(0); }
    .sidebar .branding{ padding:1rem; text-align:center; border-bottom:1px solid rgba(255,255,255,.1); position:relative; }
    .sidebar .branding img{ height:80px; }
    .sidebar .brand-name{ font-weight:600; }
    .sidebar .close-btn{ position:absolute; right:1rem; top:1rem; font-size:1.25rem; cursor:pointer; color:#fff; display:none; }
    .sidebar .nav-link{ color:#ccc; padding:.75rem 1rem; display:flex; align-items:center; gap:.5rem; border-radius:10px; margin:.2rem .5rem; }
    .sidebar .nav-link:hover{ background:rgba(255,255,255,.1); color:#fff; }
    .sidebar .nav-link.active{ background:#ffc107; color:#000; font-weight:600; }
    .sidebar .user-info{ margin-top:auto; padding:1rem; border-top:1px solid rgba(255,255,255,.1); font-size:.9rem; }

    .sidebar-overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1039; }
    .sidebar-overlay.show{ display:block; }

    .topbar{ display:none; position:fixed; inset:0 0 auto 0; height:var(--topbar-h); background:#fff; border-bottom:1px solid #e9ecef;
      z-index:1035; align-items:center; padding:0 .75rem; gap:.75rem; }
    .topbar .brand{ display:flex; align-items:center; gap:.5rem; font-weight:700; }
    .topbar img{ height:28px; }

    @media (max-width:991.98px){
      .sidebar{ transform:translateX(-100%); }
      .sidebar.show{ transform:translateX(0); }
      .sidebar .close-btn{ display:block; }
      .topbar{ display:flex; }
      .main-wrap{ padding-top:calc(var(--topbar-h) + 1rem); }
    }

    /* ===== Table styles (desktop & tablet) ===== */
    .thumb{ height:40px; border-radius:4px; object-fit:contain; }
    .table th{ background:#f0f2f5; font-weight:600; white-space:nowrap; }
    .table td,.table th{ vertical-align:middle; text-align:center; }
    .table td a.btn{ white-space:nowrap; }

    /* ===== Card view (mobile only) ===== */
    .cert-card{ background:#fff; border:1px solid #eef2f6; border-radius:12px; padding:14px; box-shadow:0 6px 20px rgba(16,24,40,.06); }
    .cert-card + .cert-card{ margin-top:12px; }
    .cert-card .logo{ width:48px; height:48px; border-radius:8px; object-fit:contain; background:#fff; }
    .cert-card .title{ font-weight:700; line-height:1.25; }
    .cert-card .meta{ font-size:.9rem; color:#6b7280; }
    .cert-card .actions .btn{ border-radius:10px; }

    /* Show/Hide */
    .mobile-cards{ display:none; }
    @media (max-width: 767.98px){
      .desktop-table{ display:none!important; }
      .mobile-cards{ display:block; }
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

<div class="container main-wrap py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h3 class="mb-0">Manajemen Sertifikat</h3>
    <a href="Add-Sertifikat" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Tambah Sertifikat
    </a>
  </div>

  <form method="GET" class="row row-cols-lg-auto g-3 align-items-center mb-4">
    <div class="col-12">
      <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control" placeholder="Cari Judul Sertifikat">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
      <a href="List-Sertifikat" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <?php if ($totalItems === 0): ?>
    <div class="alert alert-warning">
      Belum ada sertifikat yang ditambahkan<?= $search ? " untuk kata kunci \"".htmlspecialchars($search)."\"" : "" ?>.
    </div>
  <?php else: ?>

    <!-- ===== MOBILE: CARD VIEW ===== -->
    <div class="mobile-cards">
      <?php foreach ($certificatesPaginated as $keyIndex => $cert): ?>
        <?php
          $img = trim((string)($cert['image'] ?? ''));
          $imgSrc = ($cms_url ? rtrim($cms_url,'/').'/' : '') . ltrim($img, '/');
        ?>
        <div class="cert-card">
          <div class="d-flex align-items-start gap-3">
            <img src="<?= htmlspecialchars($imgSrc) ?>" class="logo" alt="logo" loading="lazy"
                 onerror="this.src='img/placeholder.png';this.onerror=null;">
            <div class="flex-grow-1">
              <div class="title mb-1"><?= htmlspecialchars($cert['title'] ?? '-') ?></div>
              <div class="meta mb-2">
                <i class="bi bi-calendar2-week me-1"></i><?= formatPeriode($cert['period'] ?? '-') ?>
                <?php if (!empty($cert['published'])): ?>
                  <span class="badge bg-success ms-2 align-middle">Published</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger ms-2 align-middle">Draft</span>
                <?php endif; ?>
              </div>
              <div class="actions d-flex flex-wrap gap-2">
                <?php if (!empty($cert['link'])): ?>
                  <a href="<?= htmlspecialchars($cert['link']) ?>" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye"></i> Lihat
                  </a>
                <?php endif; ?>
                <a href="Edit-Sertifikat?id=<?= urlencode($keyIndex) ?>" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
                <button type="button" class="btn btn-sm btn-danger"
                        data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                        data-id="<?= htmlspecialchars($keyIndex) ?>">
                  <i class="bi bi-trash"></i> Hapus
                </button>
                <a href="toggle-certificate.php?id=<?= urlencode($keyIndex) ?>"
                   class="btn btn-sm <?= !empty($cert['published']) ? 'btn-secondary' : 'btn-success' ?>">
                  <i class="bi <?= !empty($cert['published']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                  <?= !empty($cert['published']) ? 'Unpublish' : 'Publish' ?>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ===== DESKTOP/TABLET: TABLE VIEW ===== -->
    <div class="table-responsive desktop-table">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:80px">Logo</th>
            <th>Judul</th>
            <th style="width:180px">Periode</th>
            <th style="width:120px">Link</th>
            <th style="width:120px">Status</th>
            <th style="width:140px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($certificatesPaginated as $keyIndex => $cert): ?>
            <tr>
              <td>
                <?php
                  $img = trim((string)($cert['image'] ?? ''));
                  $imgSrc = ($cms_url ? rtrim($cms_url,'/').'/' : '') . ltrim($img, '/');
                ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>" class="thumb" alt="logo" loading="lazy"
                     onerror="this.src='img/placeholder.png'; this.onerror=null;">
              </td>
              <td><?= htmlspecialchars($cert['title'] ?? '-') ?></td>
              <td><?= formatPeriode($cert['period'] ?? '-') ?></td>
              <td>
                <?php if (!empty($cert['link'])): ?>
                  <a href="<?= htmlspecialchars($cert['link']) ?>" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye"></i> Lihat
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($cert['published'])): ?>
                  <span class="badge bg-success">Published</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger fw-semibold">Draft</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-grid gap-1">
                  <a href="Edit-Sertifikat?id=<?= urlencode($keyIndex) ?>" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>
                  <button type="button" class="btn btn-sm btn-danger"
                          data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                          data-id="<?= htmlspecialchars($keyIndex) ?>">
                    <i class="bi bi-trash"></i> Hapus
                  </button>
                  <a href="toggle-certificate.php?id=<?= urlencode($keyIndex) ?>"
                     class="btn btn-sm <?= !empty($cert['published']) ? 'btn-secondary' : 'btn-success' ?>">
                    <i class="bi <?= !empty($cert['published']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                    <?= !empty($cert['published']) ? 'Unpublish' : 'Publish' ?>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Pagination Sertifikat" class="mt-3">
        <ul class="pagination justify-content-center">
          <?php $qs = $search !== '' ? '&search='.urlencode($search) : ''; ?>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i . $qs ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center border-0 shadow-sm">
      <div class="modal-body p-4">
        <div class="mb-3">
          <div class="bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center rounded-circle p-3">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-1"></i>
          </div>
        </div>
        <h5 class="mb-2 fw-bold text-danger">Hapus Sertifikat</h5>
        <p class="text-muted">Apakah Anda yakin ingin menghapus sertifikat ini?<br>Tindakan ini tidak dapat dibatalkan.</p>
        <div class="d-flex justify-content-center gap-2 mt-4">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
          <a href="#" id="deleteConfirmBtn" class="btn btn-danger px-4">Hapus</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('contextmenu', e => e.preventDefault());

// Toggle sidebar (mobile)
function toggleSidebar() {
  const sb = document.getElementById('sidebarMenu');
  const ov = document.querySelector('.sidebar-overlay');
  sb.classList.toggle('show');
  ov.classList.toggle('show');
}

// Isi link hapus dengan key asli saat modal dibuka
const confirmDeleteModal = document.getElementById('confirmDeleteModal');
confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const certId = button.getAttribute('data-id');
  const deleteBtn = document.getElementById('deleteConfirmBtn');
  deleteBtn.href = 'Hapus-Sertifikat?id=' + encodeURIComponent(certId);
});
</script>

</body>
</html>
