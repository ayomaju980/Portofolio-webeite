<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('data.json'), true);
    if (!is_array($data)) $data = [];

    // Upload gambar
    $imgName = time() . '_' . basename($_FILES['img']['name']);
    $imgTmp = $_FILES['img']['tmp_name'];
    $uploadPath = 'img/' . $imgName;
    move_uploaded_file($imgTmp, $uploadPath);

    // Ambil tipe (bisa lebih dari satu)
    $typeInput = isset($_POST['type']) ? $_POST['type'] : [];

    // Buat ID baru
    $newId = count($data) > 0 ? max(array_column($data, 'id')) + 1 : 1;

    $new_item = [
        "id" => $newId,
        "title" => $_POST['title'],
        "date" => $_POST['date'],
        "type" => $typeInput,
        "img" => $uploadPath,
        "link" => $_POST['link'] ?? '#',
        "client" => $_POST['client'] ?? '',
        "description" => $_POST['description'] ?? ''
    ];

    $data[] = $new_item;
    file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Portofolio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <style>
    .clock {
      font-size: 14px;
      color: #666;
      font-family: monospace;
      margin-left: 20px;
      white-space: nowrap;
    }

    .navbar {
      background-color: #ffffff;
      padding: 14px 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .navbar .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar .brand img {
      height: 36px;
      width: auto;
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
      transition: color 0.2s;
    }

    .navbar .nav-links a:hover {
      color: #FAAD1B;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <div class="brand">
    <img src="img/Logoo.png" alt="Logo">
    <span>Portfolio-CMS</span>
    <div class="clock" id="digitalClock"></div>
  </div>

  <!-- kanan navbar -->
  <div class="nav-links">
    <span class="me-3 fw-semibold">
  👋 <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?>
</span>

    <a href="Dashboard" style="color: #FAAD1B;">Dashboard</a>
    <a href="Remote">Editor Teks</a>
    <a href="logout.php">Logout</a>
  </div>
</div>

<div class="container mt-4 mb-4">
  <h2>Tambah Data Portofolio</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-2">
      <label>Judul:*</label>
      <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-2">
      <label>Tanggal:*</label>
      <input type="month" name="date" class="form-control" required>
    </div>
    <div class="mb-2">
      <label>Kategori:</label><br>
      <?php
      $types = ['UI DESIGN', 'Mobile Apps', 'Landing Page', 'UX Design', 'UX Writer'];
      foreach ($types as $index => $type) {
          echo '<div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" name="type[]" value="'.$type.'" id="type'.$index.'">
                  <label class="form-check-label" for="type'.$index.'">'.$type.'</label>
                </div>';
      }
      ?>
    </div>
    <div class="mb-2">
      <label>Upload Gambar:*</label>
      <input type="file" name="img" class="form-control" accept="image/*" required>
    </div>
    <div class="mb-2">
      <label>Nama Projek:</label>
      <input type="text" name="client" class="form-control">
    </div>
    <div class="mb-2">
      <label>Link Proyek:</label>
      <input type="text" name="link" class="form-control">
    </div>
    <div class="mb-2">
      <label>Deskripsi:</label>
      <textarea name="description" class="form-control" rows="5" placeholder="Tuliskan deskripsi proyek..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="admin.php" class="btn btn-secondary">Kembali</a>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateField').value = today;
  });

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
