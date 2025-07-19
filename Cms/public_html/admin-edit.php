<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

// Ambil data dari file JSON
$data = json_decode(file_get_contents('data.json'), true);

// Ambil ID dari URL
$id = $_GET['id'] ?? null;
$index = array_search($id, array_column($data, 'id'));

// Kalau ID tidak valid
if ($id === null || $index === false) {
    die("Data tidak ditemukan.");
}

$item = $data[$index];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload gambar jika ada file baru
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
        $imgRelativePath = $item['img']; // Pakai gambar lama
    }

    // Format tanggal hanya ambil tahun-bulan
    $formattedDate = date('Y-m', strtotime($_POST['date']));

    // Simpan data hasil edit
    $data[$index] = [
        "id" => (int)$id,
        "title" => $_POST['title'],
        "date" => $formattedDate,
        "type" => $_POST['type'],
        "description" => $_POST['description'], // Biarkan HTML dari TinyMCE
        "img" => $imgRelativePath,
        "client" => $_POST['client'] ?? '',
        "link" => $_POST['link'] ?? ''
    ];

    // Pertahankan status publish dan waktu publish kalau ada
    if (isset($item['published'])) {
        $data[$index]['published'] = $item['published'];
    }
    if (isset($item['published_at'])) {
        $data[$index]['published_at'] = $item['published_at'];
    }

    // Update waktu edit
    $data[$index]['updated_at'] = date('Y-m-d H:i:s'); // Bacaable format
    $data[$index]['updated_unix'] = time(); // UNIX timestamp untuk logika < 24 jam

    // Simpan ke file JSON
    file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Portofolio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
  <script>
  tinymce.init({
    selector: '#tinymceEditor',
    height: 300,
    menubar: false,
    plugins: 'lists link image preview code',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link image | preview code',
    branding: false
  });
  </script>
  <style>
    body { background-color: #f5f7fa; }
    .sidebar {
        width: 230px;
        min-height: 100vh;
        background: #212529;
        color: #fff;
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0; left: 0;
        z-index: 1030;
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
    .main-content {
        margin-left: 230px;
        flex-grow: 1;
    }
    @media (max-width: 991.98px) {
        .sidebar { display: none; }
        .main-content { margin-left: 0; }
    }
    .toast { animation: slideIn .5s ease; }
    @keyframes slideIn {
        from { transform: translateY(-20px); opacity:0; }
        to { transform: translateY(0); opacity:1; }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar d-none d-lg-flex flex-column">
  <div class="branding">
    <img src="img/ICON.png" alt="Logo">
    <div class="brand-name">Portofolio-CMS</div>
  </div>
  <a href="Dashboard" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="Remote" class="nav-link"><i class="bi bi-pencil-square"></i> Editor Teks</a>
  <a href="Ganti-Password" class="nav-link"><i class="bi bi-key"></i> Ganti Password</a>
  <a href="List-Portofolio" class="nav-link"><i class="bi bi-folder2-open"></i> Portofolio</a>
  <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <div class="user-info">
    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
    <span class="badge bg-secondary">User</span>
  </div>
</nav>

<!-- Navbar kecil -->
<nav class="navbar navbar-dark bg-dark d-lg-none">
  <div class="container-fluid">
    <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
      <i class="bi bi-list"></i>
    </button>
    <span class="navbar-brand">Portofolio-CMS</span>
  </div>
</nav>
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

<!-- Konten Utama -->
<main class="main-content p-4">
  <h4 class="mb-4 fw-semibold"><i class="bi bi-pencil-square me-2"></i>Edit Data Portofolio</h4>
  <div class="card shadow-sm p-4">
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Judul:</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Tanggal:</label>
        <input type="month" name="date" class="form-control" value="<?= htmlspecialchars($item['date']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Kategori:</label><br>
        <div class="row">
        <?php
        $typeList = ['UI DESIGN', 'Mobile Apps', 'Landing Page', 'UX Design', 'UX Writer', 'Web Design'];
        foreach ($typeList as $i => $type):
          $checked = in_array($type, $item['type']) ? 'checked' : '';
        ?>
          <div class="form-check col-6">
            <input class="form-check-input" type="checkbox" name="type[]" value="<?= $type ?>" id="type<?= $i ?>" <?= $checked ?>>
            <label class="form-check-label" for="type<?= $i ?>"><?= $type ?></label>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Deskripsi:</label>
        <textarea id="tinymceEditor" name="description"><?= $item['description'] ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Nama Proyek:</label>
        <input type="text" name="client" class="form-control" value="<?= htmlspecialchars($item['client']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Link Proyek:</label>
        <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($item['link']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Gambar Saat Ini:</label><br>
        <img src="<?= htmlspecialchars($item['img']) ?>" class="img-fluid mb-2 rounded shadow-sm border" style="max-width: 200px;"><br>
        <input type="file" name="img" class="form-control">
        <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar</small>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="List-Portofolio" class="btn btn-secondary">Batal</a>
      </div>
    </form>
  </div>
</main>

<?php if ($success): ?>
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast align-items-center text-bg-success show shadow rounded-3" role="alert">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-check-circle-fill me-2"></i> Portofolio berhasil diperbarui!
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($success): ?>
setTimeout(() => {
  window.location.href = "dashboard.php";
}, 3000);
<?php endif; ?>
document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
