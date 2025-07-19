<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

// Cek timeout session
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: Login?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Hitung data
$dataFile = 'data.json';
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$totalPortfolio = count($data);
$totalPublished = count(array_filter($data, fn($d) => !empty($d['published'])));
$totalDraft = $totalPortfolio - $totalPublished;

// Hitung yang baru diupdate/diposting < 24 jam
$now = time();
$totalRecent = 0;
$recentTypeLabel = '';

foreach ($data as $d) {
    if (!isset($d['updated_unix'])) continue;

    $isRecent = ($now - $d['updated_unix']) <= 86400;
    if ($isRecent) {
        $totalRecent++;

        if (isset($d['published_at'])) {
            $published_unix = strtotime($d['published_at']);
            $diff = $d['updated_unix'] - $published_unix;

            if ($diff <= 60) {
                $recentTypeLabel = 'Baru (< 24 Jam)';
            } else {
                $recentTypeLabel = 'Edit (< 24 Jam)';
            }
        } else {
            $recentTypeLabel = 'Baru (< 24 Jam)';
        }
    }
}

if ($totalRecent === 0) {
    $recentTypeLabel = 'Baru/Update (< 24 Jam)';
}

// Aktivitas dummy
$recentActivities = [
    "Login oleh " . htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']),
    "Cek portofolio terbaru",
    "Terakhir update data"
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <style>
    body { background-color: #f5f7fa; }
    .sidebar {
      width: 230px;
      min-height: 100vh;
      background: #212529;
      color: #fff;
      display: flex;
      flex-direction: column;
    }
    .sidebar .branding {
      padding: 1rem;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar .branding img {
      height: 50px;
      margin-bottom: .5rem;
    }
    .sidebar .brand-name {
      font-weight: 600;
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
      text-decoration: none;
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
    .card-summary {
      border-radius: .75rem;
    }
    canvas#portfolioChart {
      max-height: 120px;
    }
  </style>
</head>
<body>

<!-- Navbar untuk Mobile -->
<nav class="navbar navbar-dark bg-dark d-lg-none">
  <div class="container-fluid">
    <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
      <i class="bi bi-list"></i>
    </button>
    <span class="navbar-brand">Portofolio-CMS</span>
  </div>
</nav>

<!-- Sidebar Offcanvas untuk Mobile -->
<div class="offcanvas offcanvas-start bg-dark text-white" id="offcanvasSidebar">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <a href="Dashboard" class="nav-link text-white"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="Remote" class="nav-link text-white"><i class="bi bi-pencil-square"></i> Editor Teks</a>
    <a href="Ganti-Password" class="nav-link text-white"><i class="bi bi-key"></i> Ganti Password</a>
    <a href="List-Portofolio" class="nav-link text-white"><i class="bi bi-folder2-open"></i> Portofolio</a>
    <a href="logout.php" class="nav-link text-white mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div>

<div class="d-flex">
  <!-- Sidebar Desktop -->
  <nav class="sidebar d-none d-lg-flex flex-column">
    <div class="branding">
      <img src="img/ICON.png" alt="Logo">
      <div class="brand-name">Portofolio-CMS</div>
    </div>
    <a href="Dashboard" class="nav-link active">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="Remote" class="nav-link">
      <i class="bi bi-pencil-square"></i> Editor Teks
    </a>
    <a href="Ganti-Password" class="nav-link">
      <i class="bi bi-key"></i> Ganti Password
    </a>
    <a href="List-Portofolio" class="nav-link">
      <i class="bi bi-folder2-open"></i> Portofolio
    </a>
    <a href="logout.php" class="nav-link mt-auto">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
    <div class="user-info">
      <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
      <span class="badge bg-secondary">User</span>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="p-4 w-100">
    <div class="toast-container position-fixed top-0 end-0 p-3">
      <div id="welcomeToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            Halo, <?= htmlspecialchars($_SESSION['full_name']) ?>! Selamat datang kembali.
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>

    <h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card card-summary shadow-sm p-3">
          <h6><i class="bi bi-collection-fill me-2 text-primary"></i>Total Portofolio</h6>
          <h3><?= $totalPortfolio ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-summary shadow-sm p-3">
          <h6><i class="bi bi-eye-fill me-2 text-success"></i>Published</h6>
          <h3><?= $totalPublished ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-summary shadow-sm p-3">
          <h6><i class="bi bi-file-earmark-diff-fill me-2 text-danger"></i>Total Draft</h6>
          <h3><?= $totalDraft ?></h3>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-summary shadow-sm p-3">
          <h6><i class="bi bi-clock me-2 text-info"></i><?= $recentTypeLabel ?></h6>
          <h3><?= $totalRecent ?></h3>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-lightning-charge me-2"></i>Quick Actions
      </div>
      <div class="card-body d-flex gap-3 flex-wrap">
        <a href="/Add-Portofolio" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i> Tambah Portofolio
        </a>
        <a href="Remote" class="btn btn-primary">
          <i class="bi bi-pencil me-1"></i> Edit Teks Berjalan
        </a>
        <a href="Ganti-Password" class="btn btn-warning">
          <i class="bi bi-key me-1"></i> Ganti Password
        </a>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentActivities as $activity): ?>
        <li class="list-group-item"><?= htmlspecialchars($activity) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toast = new bootstrap.Toast(document.getElementById('welcomeToast'), { delay: 3000 });
  toast.show();
});
</script>
</body>
</html>
