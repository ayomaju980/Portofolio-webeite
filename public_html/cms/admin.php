<?php
session_start();

$dataFile = 'data.json';
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
if (!is_array($data)) $data = [];
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Portofolio CMS - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="../img/Logoo.png" type="image/png">
  <style>
    body {
      background-color: #f8f9fa;
    }

    .clock {
      font-size: 14px;
      color: #666;
      font-family: monospace;
      margin-left: 10px;
    }

    .navbar {
      background-color: #fff;
      padding: 14px 30px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .navbar .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar .brand img {
      height: 36px;
    }

    .navbar .brand span {
      font-size: 20px;
      font-weight: bold;
      color: #FAAD1B;
    }

    .navbar .nav-links {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .navbar .nav-links a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
      transition: color 0.2s ease-in-out;
    }

    .navbar .nav-links a:hover {
      color: #FAAD1B;
    }

    .navbar .user-info {
      font-weight: 500;
      color: #555;
    }

    .table img {
      border-radius: 6px;
    }

    .table th {
      font-weight: 600;
      color: #333;
    }

    .badge {
      font-size: 0.75rem;
      margin-right: 4px;
    }

    .btn-sm {
      padding: 4px 12px;
      font-size: 0.75rem;
      border-radius: 6px;
    }

    .btn-success {
      background-color: #28a745;
      border-color: #28a745;
    }

    .btn-success:hover {
      background-color: #218838;
      border-color: #1e7e34;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<!-- Navbar -->
<nav class="navbar d-flex justify-content-between">
  <div class="brand">
    <img src="img/Logoo.png" alt="Logo">
    <span>Portfolio-CMS</span>
    <div class="clock" id="digitalClock"></div>
  </div>
  <div class="nav-links">
    <span class="user-info">
      👋 <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?>
    </span>
    <a href="Dashboard">Dashboard</a>
    <a href="Remote">Editor Teks</a>
    <a href="change-password.php">Edit User</a>
    <a href="logout.php">Logout</a>
  </div>
</nav>


<!-- Konten -->
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Daftar Portofolio</h3>
    <a href="admin-add.php" class="btn btn-success">+ Tambah Portofolio</a>
  </div>

  <?php if (empty($data)): ?>
    <div class="alert alert-info">Belum ada data portofolio.</div>
  <?php else: ?>
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-warning">
          <tr>
            <th>#</th>
            <th>Thumbnail</th>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Nama Projek</th>
            <th>Tanggal</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $index => $item): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td style="width: 90px;">
                <?php
                  $imgPath = $item['img'] ?? '';
                  $imgDisplay = file_exists($imgPath) ? $imgPath : 'img/default.png';
                ?>
                <img src="<?= htmlspecialchars($imgDisplay) ?>" alt="Img" class="img-thumbnail" style="height: 60px; object-fit: cover;">
              </td>
              <td><?= htmlspecialchars($item['title'] ?? '-') ?></td>
              <td>
                <?php foreach ((array)($item['type'] ?? []) as $tag): ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
              </td>
              <td><?= htmlspecialchars($item['client'] ?? '-') ?></td>
              <td><?= date('M Y', strtotime(($item['date'] ?? '1970-01') . '-01')) ?></td>
              <td class="text-center">
                <a href="admin-edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                <a href="admin-delete.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">Hapus</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
  function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('digitalClock').textContent = `${h}:${m}:${s}`;
  }

  setInterval(updateClock, 1000);
  updateClock();

  document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
  });
</script>
</body>
</html>
