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

$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: Login?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$search = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filteredData = array_filter($data, function ($item) use ($search, $filterCategory) {
    $matchesSearch = $search === '' || stripos($item['title'], $search) !== false;
    $matchesCategory = $filterCategory === '' || in_array($filterCategory, $item['type'] ?? []);
    return $matchesSearch && $matchesCategory;
});

$perPage = 5;
$totalData = count($filteredData);
$totalPages = ceil($totalData / $perPage);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $perPage;
$paginatedData = array_slice($filteredData, $start, $perPage);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Portofolio CMS - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="img/Logoo.png" type="image/png">
<style>
body { background-color: #f8f9fa; }
/* Tambahan style untuk modal konfirmasi */
.modal-header.bg-danger {
  background-color: #dc3545;
}
.modal-title {
  font-weight: 600;
}
.modal-body {
  font-size: 1rem;
  color: #333;
}
.modal-footer .btn {
  min-width: 100px;
}
.sidebar {
    width: 230px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: #212529;
    color: #fff;
    display: flex;
    flex-direction: column;
    z-index: 1040;
    transition: left 0.3s ease;
}
.sidebar .branding {
    padding: 1rem;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    position: relative;
}
.sidebar .branding img {
    height: 50px;
}
.sidebar .brand-name {
    font-weight: 600;
}
.sidebar .close-btn {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.25rem;
    cursor: pointer;
    color: #fff;
    display: none;
}
.sidebar .nav-link {
    color: #ccc;
    padding: .75rem 1rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.sidebar .nav-link.active {
    background: #ffc107;
    color: #000;
    font-weight: 600;
}
.sidebar .user-info {
    margin-top: auto;
    padding: 1rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: .9rem;
}
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1039;
}
main {
    margin-left: 230px;
    padding: 1rem;
}
@media (max-width: 991.98px) {
    .sidebar {
        left: -230px;
    }
    .sidebar.show {
        left: 0;
    }
    .sidebar .close-btn {
        display: block;
    }
    .sidebar-overlay.show {
        display: block;
    }
    main {
        margin-left: 0;
    }
}
</style>
</head>
<body>
<nav id="sidebarMenu" class="sidebar">
  <div class="branding">
    <img src="img/ICON.png" alt="Logo">
    <div class="brand-name">Portofolio-CMS</div>
    <span class="close-btn d-lg-none" onclick="toggleSidebar()">&times;</span>
  </div>
  <a href="Dashboard" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="Remote" class="nav-link"><i class="bi bi-pencil-square"></i> Editor Teks</a>
  <a href="Ganti-Password" class="nav-link"><i class="bi bi-key"></i> Ganti Password</a>
  <a href="List-Portofolio" class="nav-link active"><i class="bi bi-folder2-open"></i> Portofolio</a>
  <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <div class="user-info">
    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
    <span class="badge bg-secondary">User</span>
  </div>
</nav>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<button class="btn btn-dark d-lg-none position-fixed top-0 start-0 m-2" onclick="toggleSidebar()">
  <i class="bi bi-list"></i>
</button>
<main>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
  <h3 class="mb-2">Daftar Portofolio</h3>
  <a href="/Add-Portofolio" class="btn btn-success mb-2">+ Tambah Portofolio</a>
</div>
<button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterForm">
  <i class="bi bi-funnel"></i> Tampilkan Filter
</button>
<div class="collapse" id="filterForm">
  <form class="row g-2 mb-4" method="get">
    <div class="col-12 col-sm-4">
      <input type="text" name="search" class="form-control" placeholder="Cari judul..." value="<?= htmlspecialchars($search) ?>" />
    </div>
    <div class="col-12 col-sm-4">
      <select name="category" class="form-select">
        <option value="">Semua Kategori</option>
        <?php
        $allCategories = array_unique(array_merge(...array_map(fn($d) => $d['type'] ?? [], $data)));
        sort($allCategories);
        foreach ($allCategories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-sm-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary w-100">Terapkan</button>
      <a href="/Dashboard" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>
</div>
<div class="table-responsive bg-white shadow-sm">
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
  <tr>
    <td><?= $start + $index + 1 ?></td>
    <td>
      <img src="<?= htmlspecialchars(file_exists($item['img'] ?? '') ? $item['img'] : 'img/default.png') ?>" class="img-thumbnail" style="height:60px;object-fit:cover;">
    </td>
    <td><?= htmlspecialchars($item['title'] ?? '-') ?></td>
    <td>
      <?php foreach ((array)($item['type'] ?? []) as $tag): ?>
        <span class="badge bg-secondary"><?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </td>
    <td><?= htmlspecialchars($item['client'] ?? '-') ?></td>
    <td><?= date('M Y', strtotime(($item['date'] ?? '1970-01').'-01')) ?></td>
    <td>
      <span class="badge <?= !empty($item['published']) ? 'bg-success' : 'bg-danger' ?>">
        <?= !empty($item['published']) ? 'Published' : 'Draft' ?>
      </span>
    </td>
    <td class="text-center">
      <div class="d-grid gap-1">
        <a href="/Edit-Portofolio?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">
          <i class="bi bi-pencil-square"></i> Edit
        </a>
        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= $item['id'] ?>">
          <i class="bi bi-trash"></i> Hapus
        </button>
        <a href="admin-publish.php?id=<?= $item['id'] ?>" class="btn btn-sm <?= !empty($item['published']) ? 'btn-secondary' : 'btn-success' ?>">
          <i class="bi <?= !empty($item['published']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
          <?= !empty($item['published']) ? 'Unpublish' : 'Publish' ?>
        </a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
</tbody>
  </table>
</div>
<nav class="mt-4">
  <ul class="pagination justify-content-center">
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebarMenu');
  const overlay = document.querySelector('.sidebar-overlay');
  sidebar.classList.toggle('show');
  overlay.classList.toggle('show');
}
document.addEventListener('contextmenu', e => e.preventDefault());

const confirmModal = document.getElementById('confirmDeleteModal');
confirmModal.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button.getAttribute('data-id');
  const confirmBtn = confirmModal.querySelector('#confirmDeleteBtn');
  confirmBtn.href = 'admin-delete.php?id=' + encodeURIComponent(id);
});
</script>
</body>
</html>
