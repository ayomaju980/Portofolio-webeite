<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['csrf']) {
        die('CSRF token tidak valid.');
    }

    $title = trim($_POST['title']);
    $date = trim($_POST['date']);

    if (empty($title) || empty($date)) {
        $error = "Judul dan tanggal wajib diisi.";
    } elseif (!isset($_FILES['img']) || $_FILES['img']['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload gambar gagal.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($_FILES['img']['tmp_name']);
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.";
        } elseif ($_FILES['img']['size'] > $maxSize) {
            $error = "Ukuran gambar maksimal 2MB.";
        } else {
            $data = json_decode(file_get_contents('data.json'), true);
            if (!is_array($data)) $data = [];

            $imgName = time() . '_' . basename($_FILES['img']['name']);
            $uploadPath = 'img/' . $imgName;
            move_uploaded_file($_FILES['img']['tmp_name'], $uploadPath);

            $typeInput = $_POST['type'] ?? [];
            $newId = count($data) > 0 ? max(array_column($data, 'id')) + 1 : 1;

            $new_item = [
                "id" => $newId,
                "title" => htmlspecialchars($title),
                "date" => $date,
                "type" => $typeInput,
                "img" => $uploadPath,
                "link" => htmlspecialchars($_POST['link'] ?? '#'),
                "client" => htmlspecialchars($_POST['client'] ?? ''),
                "description" => $_POST['description'] ?? ''
            ];

            $data[] = $new_item;
            file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
            $success = true;
            unset($_SESSION['csrf']);
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
<link rel="icon" href="img/Logoo.png" type="image/png">
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
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
    top: 0;
    left: 0;
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
.main-content {
    margin-left: 230px;
    flex-grow: 1;
}
@media (max-width: 991.98px) {
    .sidebar {
        display: none;
    }
    .main-content {
        margin-left: 0;
    }
}
@media (max-width: 576px) {
  .form-check {
    flex: 1 1 100%;
  }
  .form-label {
    font-size: 0.9rem;
  }
  .main-content {
    padding: 1rem;
  }
  .card {
    padding: 1rem !important;
  }
  .d-flex.gap-2.mt-4 {
    flex-direction: column;
  }
  .toast-container {
    width: 100%;
    left: 0;
    right: 0;
  }
}
.toast { animation: slideIn .5s ease; }
@keyframes slideIn {
    from { transform: translateY(-20px); opacity:0; }
    to { transform: translateY(0); opacity:1; }
}
</style>
</head>
<body>

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

<nav class="navbar navbar-dark bg-dark d-lg-none">
  <div class="container-fluid">
    <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
      <i class="bi bi-list"></i>
    </button>
    <span class="navbar-brand">CMS Panel</span>
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

<main class="main-content p-4">
  <h4 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Tambah Portofolio</h4>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <div class="card shadow-sm p-4">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="token" value="<?= $_SESSION['csrf'] ?>">
      <div class="mb-3">
        <label class="form-label">Judul*</label>
        <input type="text" name="title" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Tanggal*</label>
        <input type="month" name="date" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Kategori:</label>
        <div class="row">
        <?php
        $types = ['UI DESIGN','Mobile Apps','Landing Page','UX Design','UX Writer', 'Web Design'];
        foreach($types as $i=>$type): ?>
          <div class="col-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="type[]" value="<?= $type ?>" id="type<?= $i ?>">
              <label class="form-check-label" for="type<?= $i ?>"><?= $type ?></label>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Upload Gambar*</label>
        <input type="file" name="img" class="form-control" accept="image/*" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nama Projek:</label>
        <input type="text" name="client" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Link Proyek:</label>
        <input type="text" name="link" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Deskripsi:</label>
        <textarea name="description"></textarea>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="List-Portofolio" class="btn btn-secondary">Kembali</a>
      </div>
    </form>
  </div>
</main>

<?php if ($success): ?>
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast align-items-center text-bg-success show shadow rounded-3" role="alert">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-check-circle-fill me-2"></i> Portofolio berhasil ditambahkan!
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  tinymce.init({
    selector: 'textarea[name="description"]',
    height: 300,
    menubar: false,
    plugins: 'lists link code',
    toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link | code',
    branding: false
  });

  <?php if ($success): ?>
    setTimeout(() => { window.location.href = "dashboard.php"; }, 3000);
  <?php endif; ?>

  document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
