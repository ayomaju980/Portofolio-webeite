<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

$dataFile = 'data.json';
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
if (!is_array($data)) $data = [];

usort($data, function ($a, $b) {
    return strtotime(($b['date'] ?? '1970-01') . '-01') - strtotime(($a['date'] ?? '1970-01') . '-01');
});

// Timeout
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: Login?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Filter + pagination
$search = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filteredData = array_filter($data, function ($item) use ($search, $filterCategory) {
    $matchesSearch = $search === '' || stripos($item['title'] ?? '', $search) !== false;
    $matchesCategory = $filterCategory === '' || in_array($filterCategory, $item['type'] ?? []);
    return $matchesSearch && $matchesCategory;
});

$perPage = 5;
$totalData = count($filteredData);
$totalPages = max(1, ceil($totalData / $perPage));
$page = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$start = ($page - 1) * $perPage;
$paginatedData = array_slice($filteredData, $start, $perPage);

// Kategori unik
$allCategories = [];
foreach ($data as $d) {
  foreach (($d['type'] ?? []) as $t) { $allCategories[$t] = true; }
}
$allCategories = array_keys($allCategories);
sort($allCategories);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Portofolio CMS - Portofolio List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="img/Logo.png" type="image/png">
<style>
  :root{
    --sidebar-w: 230px;
    --topbar-h: 56px;
    --radius: 12px;
    --shadow: 0 10px 28px rgba(16,24,40,.08);
  }
  body{ background-color:#f8f9fa; }

  /* ===== Sidebar ===== */
  .sidebar {
    width: var(--sidebar-w);
    height: 100vh;
    position: fixed; inset:0 auto 0 0;
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

  /* ===== Topbar (mobile) ===== */
  .topbar{
    display:none; position:fixed; inset:0 0 auto 0; height:var(--topbar-h);
    background:#fff; border-bottom:1px solid #e9ecef; z-index:1035;
    align-items:center; padding:0 .75rem; gap:.75rem;
  }
  .topbar .brand{ display:flex; align-items:center; gap:.5rem; font-weight:700; }
  .topbar img{ height:28px; }

  /* ===== Main ===== */
  main{ margin-left: var(--sidebar-w); padding: 1.25rem; }
  .page-head{ display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; }
  .page-head h3{ margin:0; }

  /* ===== Filter ===== */
  #filterForm .form-control, #filterForm .form-select{ border-radius: 10px; }
  #filterForm .btn{ border-radius: 10px; }

  /* ===== Table default (desktop) ===== */
  .table-wrap{ background:#fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow:hidden; }
  .table thead th{ white-space:nowrap; }
  .img-thumb{ height:60px; width:90px; object-fit:cover; border-radius:8px; }

  /* ===== Actions ===== */
  .actions .btn{ border-radius: 10px; }
  .actions .btn-sm{ padding:.35rem .55rem; }

  /* ===== Pagination ===== */
  .pagination .page-link{ padding:.6rem .9rem; border-radius:10px; }
  .pagination .page-item + .page-item{ margin-left:.25rem; }

  /* ===== Breakpoints ===== */
  @media (max-width: 991.98px){
    .topbar{ display:flex; }
    .sidebar{ transform: translateX(-105%); }
    .sidebar.show{ transform: translateX(0); }
    .sidebar .close-btn{ display:block; }
    main{ margin-left: 0; padding-top: calc(var(--topbar-h) + 1rem); }
  }

  /* ===== Transform table -> cards (phones & small tablets) ===== */
  @media (max-width: 767.98px){
    /* sembunyikan tabel, tampilkan card list */
    .table-wrap{ border-radius:0; box-shadow:none; background:transparent; }
    table.table{ display:none; }

    .list-cards{ display:grid; gap: .75rem; }
    .card-item{
      background:#fff; border:1px solid #eef2f6; border-radius:12px; box-shadow: var(--shadow);
      padding:.9rem;
    }
    .card-item .row1{
      display:grid; grid-template-columns: 100px 1fr; gap:.75rem; align-items:center;
    }
    .card-item .thumb{ width:100px; height:70px; border-radius:10px; object-fit:cover; }
    .card-item .title{ font-weight:700; margin-bottom:.15rem; }
    .card-item .meta{ font-size:.9rem; color:#6c757d; display:flex; flex-wrap:wrap; gap:.35rem .5rem; }
    .card-item .badges{ display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.35rem; }
    .card-item .badges .badge{ background:#eef2f7; color:#334155; }

    .card-item .row2{ display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-top:.75rem; }
    .card-item .status .badge{ font-size:.85rem; padding:.45em .6em; }
    .card-item .actions{ display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:.4rem; }
    .card-item .actions .btn{ font-size:.9rem; }

    /* Pagination bigger tap area */
    .pagination .page-link{ padding:.6rem .9rem; }
  }

  /* Very small phones */
  @media (max-width: 375px){
    .card-item .row1{ grid-template-columns: 88px 1fr; }
    .card-item .thumb{ width:88px; height:64px; }
    .card-item .actions{ grid-template-columns: 1fr; } /* stack all buttons */
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

<main>
  <div class="page-head mb-3">
    <h3>Daftar Portofolio</h3>
    <a href="/Add-Portofolio" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Tambah Portofolio</a>
  </div>

  <button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterForm">
    <i class="bi bi-funnel"></i> Tampilkan Filter
  </button>

  <div class="collapse" id="filterForm">
    <form class="row g-2 mb-4" method="get">
      <div class="col-12 col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Cari judul..." value="<?= htmlspecialchars($search) ?>" />
      </div>
      <div class="col-12 col-md-4">
        <select name="category" class="form-select">
          <option value="">Semua Kategori</option>
          <?php foreach ($allCategories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4 d-grid d-sm-flex gap-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle me-1"></i>Terapkan</button>
        <a href="/List-Portofolio" class="btn btn-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
      </div>
    </form>
  </div>

  <!-- Desktop/tablet: Table -->
  <div class="table-wrap d-none d-md-block">
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered mb-0">
        <thead class="table-warning">
          <tr>
            <th>#</th>
            <th>Thumbnail</th>
            <th>Title</th>
            <th>Category</th>
            <th>Project Name</th>
            <th>Date</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paginatedData as $index => $item): ?>
          <?php
            $num = $start + $index + 1;
            $thumb = (isset($item['img']) && file_exists($item['img'])) ? $item['img'] : 'img/default.png';
            $isPub = !empty($item['published']);
            $dateStr = date('M Y', strtotime(($item['date'] ?? '1970-01').'-01'));
          ?>
          <tr>
            <td><?= $num ?></td>
            <td><img src="<?= htmlspecialchars($thumb) ?>" alt="thumb" class="img-thumb"></td>
            <td><?= htmlspecialchars($item['title'] ?? '-') ?></td>
            <td>
              <?php foreach ((array)($item['type'] ?? []) as $tag): ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($tag) ?></span>
              <?php endforeach; ?>
            </td>
            <td><?= htmlspecialchars($item['client'] ?? '-') ?></td>
            <td><?= $dateStr ?></td>
            <td>
              <span class="badge <?= $isPub ? 'bg-success' : 'bg-danger' ?>">
                <?= $isPub ? 'Published' : 'Draft' ?>
              </span>
            </td>
            <td class="text-center actions">
              <div class="d-grid gap-1">
                <a href="/Edit-Portofolio?id=<?= urlencode($item['id'] ?? '') ?>" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= htmlspecialchars($item['id'] ?? '') ?>">
                  <i class="bi bi-trash"></i> Hapus
                </button>
                <a href="admin-publish.php?id=<?= urlencode($item['id'] ?? '') ?>" class="btn btn-sm <?= $isPub ? 'btn-secondary' : 'btn-success' ?>">
                  <i class="bi <?= $isPub ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                  <?= $isPub ? 'Unpublish' : 'Publish' ?>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($paginatedData)): ?>
          <tr><td colspan="8" class="text-center text-muted">Data tidak ditemukan.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Phones: Cards -->
  <div class="list-cards d-md-none">
    <?php foreach ($paginatedData as $index => $item): ?>
    <?php
      $num = $start + $index + 1;
      $thumb = (isset($item['img']) && file_exists($item['img'])) ? $item['img'] : 'img/default.png';
      $isPub = !empty($item['published']);
      $dateStr = date('M Y', strtotime(($item['date'] ?? '1970-01').'-01'));
      $tags = (array)($item['type'] ?? []);
    ?>
    <div class="card-item">
      <div class="row1">
        <img class="thumb" src="<?= htmlspecialchars($thumb) ?>" alt="thumb">
        <div>
          <div class="title"><?= htmlspecialchars($item['title'] ?? '-') ?></div>
          <div class="meta">
            <span>#<?= $num ?></span>
            <span>• <?= htmlspecialchars($item['client'] ?? '-') ?></span>
            <span>• <?= $dateStr ?></span>
          </div>
          <?php if ($tags): ?>
          <div class="badges">
            <?php foreach ($tags as $t): ?>
              <span class="badge"><?= htmlspecialchars($t) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="row2">
        <div class="status">
          <span class="badge <?= $isPub ? 'text-bg-success' : 'text-bg-danger' ?>">
            <?= $isPub ? 'Published' : 'Draft' ?>
          </span>
        </div>
        <div class="actions">
          <a href="/Edit-Portofolio?id=<?= urlencode($item['id'] ?? '') ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= htmlspecialchars($item['id'] ?? '') ?>">
            <i class="bi bi-trash"></i> Hapus
          </button>
          <a href="admin-publish.php?id=<?= urlencode($item['id'] ?? '') ?>" class="btn btn-sm <?= $isPub ? 'btn-secondary' : 'btn-success' ?>">
            <i class="bi <?= $isPub ? 'bi-eye-slash' : 'bi-eye' ?>"></i> <?= $isPub ? 'Unpublish' : 'Publish' ?>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($paginatedData)): ?>
      <div class="text-center text-muted">Data tidak ditemukan.</div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <nav class="mt-4">
    <ul class="pagination justify-content-center flex-wrap gap-1">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link px-3 py-2" href="?search=<?= urlencode($search) ?>&category=<?= urlencode($filterCategory) ?>&page=<?= $i ?>">
          <?= $i ?>
        </a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>
</main>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-body text-center p-4">
        <div class="mb-3">
          <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width:60px; height:60px;">
            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
          </div>
        </div>
        <h5 class="fw-semibold mb-2">Hapus Portofolio</h5>
        <p class="text-muted mb-4">Apakah Anda yakin ingin menghapus portofolio ini?<br>Tindakan ini tidak dapat dibatalkan.</p>
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
          <a href="#" id="confirmDeleteBtn" class="btn btn-danger rounded-3 px-4">Hapus</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Floating Menu Button (jaga-jaga selain topbar) -->
<button class="btn btn-dark d-lg-none position-fixed" style="top: .75rem; left:.75rem; z-index:1060;" onclick="toggleSidebar()" aria-label="Open menu">
  <i class="bi bi-list"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebarMenu');
  const overlay = document.querySelector('.sidebar-overlay');
  const isShown = sidebar.classList.toggle('show');
  overlay.classList.toggle('show', isShown);
  document.body.style.overflow = isShown ? 'hidden' : '';
}
document.addEventListener('contextmenu', e => e.preventDefault());

// Modal konfirmasi hapus
const confirmModal = document.getElementById('confirmDeleteModal');
confirmModal.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button?.getAttribute('data-id') || '';
  const confirmBtn = confirmModal.querySelector('#confirmDeleteBtn');
  confirmBtn.href = 'admin-delete.php?id=' + encodeURIComponent(id);
});
</script>
</body>
</html>
