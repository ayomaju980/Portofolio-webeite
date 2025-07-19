<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /Login');
    exit;
}

$conn = new mysqli('localhost', 'ayomajum_user_login', 'userlogin5436#', 'ayomajum_user_login');
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$success = $error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Token CSRF tidak valid.";
    } else {
        $username = $_SESSION['user'];
        $old = trim($_POST['old_password']);
        $new = trim($_POST['new_password']);
        $confirm = trim($_POST['confirm_password']);

        if ($old === '' || $new === '' || $confirm === '') {
            $error = "Semua kolom wajib diisi!";
        } elseif (strlen($new) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } elseif (!preg_match('/[a-zA-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $error = "Password baru harus mengandung huruf dan angka.";
        } elseif ($new !== $confirm) {
            $error = "Password baru dan konfirmasi tidak cocok!";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($old, $user['password'])) {
                $error = "Password lama salah!";
            } elseif (password_verify($new, $user['password'])) {
                $error = "Password baru tidak boleh sama dengan password lama.";
            } else {
                $hashedPassword = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->bind_param("ss", $hashedPassword, $username);
                if ($stmt->execute()) {
                    session_destroy();
                    ?>
                    <!DOCTYPE html>
                    <html lang="id">
                    <head>
                        <meta charset="UTF-8">
                        <meta http-equiv="refresh" content="2;url=Login">
                        <title>Password Berhasil Diubah</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <link rel="icon" href="img/Logoo.png" type="image/png">
                        <style>
                            body {
                                background: #f8f9fa;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                height: 100vh;
                                font-family: 'Segoe UI', sans-serif;
                            }
                            .toast-success {
                                padding: 30px;
                                border-radius: 12px;
                                background: #d1e7dd;
                                border: 1px solid #badbcc;
                                color: #0f5132;
                                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
                                text-align: center;
                                animation: fadeIn .5s ease;
                            }
                            @keyframes fadeIn {
                                from {opacity:0; transform: translateY(-20px);}
                                to {opacity:1; transform: translateY(0);}
                            }
                        </style>
                    </head>
                    <body>
                        <div class="toast-success">
                            <h5 class="mb-2">✅ Password Berhasil Diubah</h5>
                            <p>Anda akan dialihkan ke halaman login...</p>
                            <div class="spinner-border text-success mt-2" role="status"></div>
                        </div>
                    </body>
                    </html>
                    <?php
                    exit;
                } else {
                    $error = "Gagal mengubah password.";
                }
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ganti Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <style>
    body {background-color: #f8f9fa;}
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
}
    .sidebar .branding {
      padding:1rem; text-align:center;
      border-bottom:1px solid rgba(255,255,255,0.1);
    }
    .sidebar .branding img {height:50px; margin-bottom:.5rem;}
    .sidebar .nav-link {
      color:#ccc; padding:.75rem 1rem;
      display:flex; gap:.5rem; align-items:center;
    }
    .sidebar .nav-link:hover {
      background:rgba(255,255,255,0.1);
      color:#fff; text-decoration:none;
    }
    .sidebar .nav-link.active {
      background:#ffc107; color:#000; font-weight:600;
    }
    .sidebar .user-info {
      margin-top:auto; padding:1rem;
      border-top:1px solid rgba(255,255,255,0.1);
      font-size:.9rem;
    }
    main {flex-grow:1;}
    .form-container {
      max-width:500px;
      margin:auto; margin-top:50px;
      background:#fff;
      padding:30px;
      border-radius:12px;
      box-shadow:0 6px 30px rgba(0,0,0,0.06);
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
<nav class="sidebar d-none d-lg-flex flex-column">
  <div class="branding">
    <img src="img/ICON.png" alt="Logo">
    <div class="brand-name">Portofolio-CMS</div>
  </div>
  <a href="Dashboard"  class="nav-link">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>
  <a href="Remote" class="nav-link">
    <i class="bi bi-pencil-square"></i> Editor Teks
  </a>
  <a href="Ganti-Password" class="nav-link  active">
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
  <main class="p-4 w-100">
    <div class="form-container">
      <h4 class="text-center mb-4">🔒 Ubah Password</h4>
      <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
          <label class="form-label">Password Lama</label>
          <input type="password" name="old_password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password Baru</label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
          <label class="form-label">Konfirmasi Password Baru</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-shield-lock"></i> Simpan Perubahan
          </button>
          <a href="Dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
