<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /Login');
    exit;
}

$conn = new mysqli('localhost', 'ayomajum_user_login', 'userlogin5436#', 'ayomajum_user_login');
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$success = $error = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Token CSRF tidak valid.";
    } else {
        $username = $_SESSION['user'];
        $old = trim($_POST['old_password'] ?? '');
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($old === '' || $new === '' || $confirm === '') {
            $error = "Semua kolom wajib diisi!";
        } elseif (strlen($new) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } elseif (!preg_match('/[a-zA-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $error = "Password baru harus mengandung huruf dan angka. Karakter spesial diperbolehkan.";
        } elseif ($new !== $confirm) {
            $error = "Password baru dan konfirmasi tidak cocok!";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
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
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    // Hapus sesi lama supaya user login ulang
                    session_destroy();
                    ?>
                    <!DOCTYPE html>
                    <html lang="id">
                    <head>
                        <meta charset="UTF-8">
                        <meta http-equiv="refresh" content="2;url=/Login">
                        <title>Password Berhasil Diubah</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <link rel="icon" href="img/Logo.png" type="image/png">
                        <style>
                            body {
                                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                min-height: 100vh;
                                font-family: 'Segoe UI', sans-serif;
                                margin: 0;
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
  <title>Portofolio CMS - Ganti Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="img/Logo.png" type="image/png">
  <style>
    :root{
      --sidebar-w: 240px;
      --topbar-h: 56px;
    }
    body {background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin:0;}

    /* Desktop: sisihkan ruang untuk sidebar */
    @media (min-width: 992px){
      body { padding-left: var(--sidebar-w); }
    }

    /* ===== Sidebar (desktop fixed, mobile off-canvas) ===== */
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

    /* Mobile behavior: sidebar off-canvas + konten padding top */
    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .sidebar .close-btn{ display:block; }
      .topbar{ display:flex; }
      .main-wrap{ padding-top: calc(var(--topbar-h) + 1rem); }
    }

    .form-container {
      max-width:500px;
      margin:auto;
      background:#fff;
      padding:30px;
      border-radius:12px;
      box-shadow:0 6px 30px rgba(0,0,0,0.08);
      animation: fadeIn .7s ease;
    }
    .btn-primary {
      background: linear-gradient(135deg, #4e73df, #224abe);
      border: none;
      transition: 0.3s;
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #224abe, #4e73df);
      transform: scale(1.03);
    }
    .btn-outline-secondary {
      border: 1px solid #6c757d;
      color: #6c757d;
      transition: 0.3s;
    }
    .btn-outline-secondary:hover {
      background-color: #6c757d;
      color: #fff;
      transform: scale(1.03);
    }
    @keyframes fadeIn {
      from {opacity:0; transform: translateY(10px);}
      to {opacity:1; transform: translateY(0);}
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
  <a href="Ganti-Password" class="nav-link active"><i class="bi bi-key"></i> Ganti Password</a>
  <a href="List-Sertifikat" class="nav-link"><i class="bi bi-award-fill me-1"></i> Sertifikat</a>
  <a href="List-Portofolio" class="nav-link"><i class="bi bi-folder2-open"></i> Portofolio</a>
  <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <div class="user-info">
    <i class="bi bi-person-circle me-1"></i>
    <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
    <span class="badge bg-secondary">User</span>
  </div>
</nav>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<main class="main-wrap p-4 w-100">
  <div class="form-container">
    <h4 class="text-center mb-4">🔒 Ubah Password</h4>
    <?php if($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="mb-3">
        <label class="form-label">Password Lama</label>
        <div class="input-group">
          <input type="password" name="old_password" id="old_password" class="form-control" required>
          <span class="input-group-text" role="button" aria-label="Tampilkan Password Lama"><i class="bi bi-eye-slash" id="toggleOld"></i></span>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password Baru</label>
        <div class="input-group">
          <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6" aria-describedby="pwHelp">
          <span class="input-group-text" role="button" aria-label="Tampilkan Password Baru"><i class="bi bi-eye-slash" id="toggleNew"></i></span>
        </div>
        <div id="pwHelp" class="form-text">Minimal 6 karakter, mengandung huruf & angka. Karakter spesial diperbolehkan.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Konfirmasi Password Baru</label>
        <div class="input-group">
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
          <span class="input-group-text" role="button" aria-label="Tampilkan Konfirmasi Password"><i class="bi bi-eye-slash" id="toggleConfirm"></i></span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary w-50">
          <i class="bi bi-shield-lock"></i> Simpan
        </button>
        <a href="Dashboard" class="btn btn-outline-secondary w-50">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
      </div>
    </form>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('contextmenu', e => e.preventDefault());

  // Toggle sidebar (mobile)
  function toggleSidebar() {
    const sb = document.getElementById('sidebarMenu');
    const ov = document.querySelector('.sidebar-overlay');
    sb.classList.toggle('show');
    ov.classList.toggle('show');
  }

  // Toggle visibility password fields
  function togglePassword(idInput, idToggle) {
    const input = document.getElementById(idInput);
    const icon = document.getElementById(idToggle);
    icon.addEventListener('click', function () {
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
      icon.classList.toggle('bi-eye');
      icon.classList.toggle('bi-eye-slash');
    });
  }

  togglePassword('old_password', 'toggleOld');
  togglePassword('new_password', 'toggleNew');
  togglePassword('confirm_password', 'toggleConfirm');
</script>
</body>
</html>
