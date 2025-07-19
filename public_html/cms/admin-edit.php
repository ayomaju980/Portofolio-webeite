<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

$data = json_decode(file_get_contents('data.json'), true);
$id = $_GET['id'] ?? null;

$index = array_search($id, array_column($data, 'id'));

if ($id === null || $index === false) {
  die("Data tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Upload gambar baru jika ada
  if (isset($_FILES['img']) && $_FILES['img']['error'] === 0) {
    $imgTmp = $_FILES['img']['tmp_name'];
    $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
    $imgName = uniqid('img_') . '.' . $ext;
    $imgDir = 'img/';
    if (!is_dir($imgDir)) {
      mkdir($imgDir, 0777, true);
    }
    $imgRelativePath = $imgDir . basename($imgName);
    move_uploaded_file($imgTmp, $imgRelativePath);
  } else {
    $imgRelativePath = $data[$index]['img'];
  }

  // Format tanggal ke YYYY-MM
  $formattedDate = date('Y-m', strtotime($_POST['date']));

  // Update data
  $data[$index] = [
    "id" => (int)$id,
    "title" => $_POST['title'],
    "date" => $formattedDate,
    "type" => $_POST['type'], // array dari checkbox
    "description" => $_POST['description'],
    "img" => $imgRelativePath,
    "client" => $_POST['client'] ?? '',
    "link" => $_POST['link'] ?? ''
  ];

  file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
  header("Location: admin.php");
  exit;
}

$item = $data[$index];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Portofolio</title>
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
  <h2>Edit Data Portofolio</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-2">
      <label>Judul:</label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required>
    </div>
    <div class="mb-2">
      <label>Tanggal:</label>
      <input type="month" name="date" class="form-control" value="<?= htmlspecialchars($item['date']) ?>" required>
    </div>
    <div class="mb-2">
      <label>Kategori:</label><br>
      <?php
        $typeList = ['UI DESIGN', 'Mobile Apps', 'Landing Page', 'UX Design', 'UX Writer'];
        foreach ($typeList as $index => $type):
          $checked = in_array($type, $item['type']) ? 'checked' : '';
      ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="type[]" value="<?= $type ?>" id="type<?= $index ?>" <?= $checked ?>>
          <label class="form-check-label" for="type<?= $index ?>"><?= $type ?></label>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mb-2">
      <label>Deskripsi:</label>
      <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($item['description']) ?></textarea>
    </div>
    <div class="mb-2">
      <label>Nama Projek:</label>
      <input type="text" name="client" class="form-control" value="<?= htmlspecialchars($item['client']) ?>">
    </div>
    <div class="mb-2">
      <label>Link Proyek:</label>
      <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($item['link']) ?>">
    </div>
    <div class="mb-2">
      <label>Gambar Saat Ini:</label><br>
      <img src="<?= htmlspecialchars($item['img']) ?>" width="150" class="mb-2"><br>
      <input type="file" name="img" class="form-control">
      <small>Kosongkan jika tidak ingin mengganti gambar</small>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="admin.php" class="btn btn-secondary">Batal</a>
  </form>
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

  document.addEventListener('contextmenu', e => e.preventDefault());
</script>

</body>
</html>
