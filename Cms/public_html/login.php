<?php
session_start();

// ====== Koneksi ke database ======
$host = 'localhost';
$db   = 'ayomajum_user_login';
$user = 'ayomajum_user_login';
$pass = 'userlogin5436#';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ====== Log aktivitas ke logs.json ======
function logActivity($name, $action) {
    $logFile = 'logs.json';
    $currentLogs = [];
    if (file_exists($logFile)) {
        $json = file_get_contents($logFile);
        $currentLogs = json_decode($json, true);
        if (!is_array($currentLogs)) $currentLogs = [];
    }
    $currentLogs[] = [
        'user' => $name,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents($logFile, json_encode($currentLogs, JSON_PRETTY_PRINT));
}

// ====== Proses login ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT username, full_name, role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $u['username'];
        $_SESSION['full_name'] = $u['full_name'];
        $_SESSION['role'] = $u['role'];
        logActivity($u['full_name'], 'Login ke sistem');
        header("Location: Dashboard");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Login — Portofolio CMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" href="img/Logo.png" type="image/png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />

  <style>
    :root{
      --main:#0A1224;         /* dasar gelap */
      --accent:#DD7900;       /* oranye brand */
      --accentHover:#C56C00;
      --cardGlass: rgba(15, 18, 28, 0.6);
      --stroke: rgba(255,255,255,0.08);
      --ring: rgba(221,121,0,0.35);
      --text:#F3F5F7;
      --muted:#98A2B3;
      --radius:16px;
      --shadow: 0 25px 80px rgba(0,0,0,0.45);
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family:'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color:var(--text);
      background:
        radial-gradient(80rem 80rem at -10% -10%, #132449 0%, transparent 55%),
        radial-gradient(80rem 80rem at 110% 10%, #2a0f3e 0%, transparent 60%),
        linear-gradient(160deg, #0B1530 0%, #1A0F2A 100%);
      display:grid; place-items:center;
      padding:24px; overflow:hidden;
    }
    /* grid halus */
    body::before{
      content:"";
      position:absolute; inset:0;
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 34px 34px;
      pointer-events:none;
    }

    .layout{
      width:min(1040px, 100%);
      display:grid;
      grid-template-columns: 1.05fr 1fr;
      gap:0;
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--stroke);
      border-radius: 22px;
      overflow:hidden;
      box-shadow: var(--shadow);
      position:relative;
      isolation:isolate;
    }

    /* ====== Panel kiri (form) ====== */
    .left{
      padding:32px;
      background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
      backdrop-filter: blur(14px);
    }
    .brand-mini{
      display:flex; align-items:center; gap:12px; margin-bottom:20px;
    }
    .brand-mini img{
      width:42px; height:42px; object-fit:contain; border-radius:10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      background:#fff;
    }
    .brand-mini .t{
      font-weight:700; letter-spacing:.3px;
    }
    .card{
      background: var(--cardGlass);
      border:1px solid var(--stroke);
      border-radius: var(--radius);
      padding:28px 24px;
      box-shadow: 0 18px 50px rgba(0,0,0,0.35);
    }
    h1{
      margin:0 0 6px 0; font-size:26px;
    }
    .sub{margin:0 0 18px 0; color:var(--muted); font-size:14px}

    .alert{
      display:flex; gap:10px; align-items:flex-start;
      background: rgba(255, 67, 67, 0.14);
      color:#FFD3D3;
      border:1px solid rgba(255, 67, 67, 0.35);
      border-radius:12px;
      padding:10px 12px; margin:0 0 14px 0; font-size:14px;
    }

    form{display:grid; gap:14px}
    .field{display:grid; gap:6px}
    label{font-weight:600; font-size:13px; color:#D9DEE6}

    .control{position:relative}
    .icon{
      position:absolute; left:12px; top:50%; transform:translateY(-50%);
      font-size:18px; color:#8b94a5;
    }
    .input{
      width:100%; padding:12px 12px 12px 42px;
      border:1px solid rgba(255,255,255,0.12);
      border-radius:12px; background: rgba(255,255,255,0.06);
      color:var(--text); font-size:15px; outline:none;
      transition:border-color .2s, box-shadow .2s, transform .05s, background .2s;
    }
    .input::placeholder{color:#b9c0cc}
    .input:hover{background: rgba(255,255,255,0.08)}
    .input:focus{
      border-color: var(--accent);
      box-shadow: 0 0 0 4px var(--ring);
      background: rgba(255,255,255,0.1);
    }
    .password .toggle{
      position:absolute; right:8px; top:50%; transform:translateY(-50%);
      background:transparent; border:0; padding:8px; cursor:pointer;
      color:#b9c0cc; border-radius:10px;
    }
    .password .toggle:hover{background: rgba(255,255,255,0.06); color:#fff}

    .submit{
      margin-top:6px;
      padding:12px 14px;
      width:100%;
      border:0; border-radius:14px;
      background: linear-gradient(180deg, var(--accent) 0%, var(--accentHover) 100%);
      color:#fff; font-weight:700; letter-spacing:.3px; font-size:16px;
      cursor:pointer; transition: transform .06s ease, filter .2s ease;
      box-shadow: 0 14px 30px rgba(221,121,0,0.35);
    }
    .submit:hover{filter:brightness(1.03)}
    .submit:active{transform:translateY(1px)}
    .submit[aria-busy="true"]{
      position:relative; pointer-events:none; opacity:.9;
    }
    .submit[aria-busy="true"]::after{
      content:""; position:absolute; right:14px; top:50%; width:16px; height:16px;
      border:2px solid rgba(255,255,255,0.9); border-left-color:transparent; border-radius:50%;
      transform:translateY(-50%); animation:spin .8s linear infinite;
    }
    @keyframes spin{to{transform:translateY(-50%) rotate(360deg)}}

    .foot{margin-top:10px; font-size:12px; color:var(--muted); text-align:center}

    /* ====== Panel kanan (ilustrasi) ====== */
    .right{
      position:relative;
      background:
        radial-gradient(60rem 40rem at 80% 10%, rgba(221,121,0,0.25) 0%, transparent 55%),
        linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04));
      border-left:1px solid var(--stroke);
      overflow:hidden;
      display:flex; align-items:center; justify-content:center;
      padding:32px;
    }
    .right::before{
      /* pola bintik */
      content:"";
      position:absolute; inset:0;
      background-image:
        radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
      background-size: 20px 20px;
      mask-image: radial-gradient(60% 60% at 50% 50%, #000 60%, transparent 100%);
      pointer-events:none;
    }
    .hero{
      position:relative; z-index:1;
      display:grid; gap:18px; text-align:center; max-width: 420px;
    }
    .hero h2{margin:0; font-size:28px}
    .hero p{margin:0; color:#CFD6E4}
    .points{display:grid; gap:10px; margin-top:8px; text-align:left}
    .pt{
      display:flex; gap:10px; align-items:flex-start;
      background: rgba(255,255,255,0.06);
      border:1px solid var(--stroke); border-radius:12px; padding:10px 12px;
    }
    .pt i{color:var(--accent); margin-top:2px}

    /* ====== Responsive ====== */
    @media (max-width: 980px){
      .layout{grid-template-columns: 1fr}
      .right{display:none} /* panel ilustrasi disembunyikan di mobile/tablet kecil */
      .left{padding:22px}
      .card{padding:22px 18px}
    }
    @media (prefers-reduced-motion: reduce){
      .submit, .input{transition:none}
      .submit[aria-busy="true"]::after{animation:none}
    }
  </style>
</head>
<body>
  <main class="layout" role="main">
    <!-- ====== KIRI: FORM LOGIN ====== -->
    <section class="left">
      <div class="brand-mini">
        <img src="img/ICON.png" alt="Logo CMS">
        <div class="t">Portofolio CMS</div>
      </div>

      <div class="card" aria-labelledby="title">
        <h1 id="title"><span style="color:var(--accent)">Hai!</span> Selamat Datang</h1>
        <p class="sub">Masuk ke akun untuk kelola konten portofolio kamu.</p>

        <?php if (isset($error)): ?>
          <div class="alert" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>
          <div class="field">
            <label for="username">Username</label>
            <div class="control">
              <i class="bi bi-person icon" aria-hidden="true"></i>
              <input class="input" type="text" id="username" name="username" placeholder="Nama pengguna" autocomplete="username" required />
            </div>
          </div>

          <div class="field">
            <label for="passwordInput">Password</label>
            <div class="control password">
              <i class="bi bi-shield-lock icon" aria-hidden="true"></i>
              <input class="input" type="password" id="passwordInput" name="password" placeholder="••••••••" autocomplete="current-password" required />
              <button class="toggle" type="button" id="togglePassword" aria-label="Tampilkan/sembunyikan password">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <button class="submit" type="submit" id="submitBtn">Sign In</button>
        </form>

        <p class="foot">Lupa akses? Hubungi admin untuk reset password.</p>
      </div>
    </section>

    <!-- ====== KANAN: PANEL ILUSTRASI/BRAND ====== -->
    <aside class="right" aria-hidden="true">
      <div class="hero">
        <h2>Kelola Portofolio Lebih Cepat</h2>
        <p>Satu dashboard untuk artikel, karya, sertifikat, dan status publish/unpublish.</p>
        <div class="points">
          <div class="pt"><i class="bi bi-lightning-charge-fill"></i><div>UI responsif & cepat untuk workflow harian.</div></div>
          <div class="pt"><i class="bi bi-shield-check"></i><div>Login aman dengan password hash + session regen.</div></div>
          <div class="pt"><i class="bi bi-graph-up"></i><div>Aktivitas tercatat otomatis.</div></div>
        </div>
      </div>
    </aside>
  </main>

  <script>
    // (Opsional) Nonaktifkan klik kanan
    document.addEventListener('contextmenu', e => e.preventDefault());

    // Toggle show/hide password
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput  = document.getElementById("passwordInput");
    togglePassword.addEventListener("click", function(){
      const icon = this.querySelector("i");
      const type = passwordInput.type === "password" ? "text" : "password";
      passwordInput.type = type;
      icon.classList.toggle("bi-eye");
      icon.classList.toggle("bi-eye-slash");
    });

    // Loading state & cegah double submit
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    form.addEventListener('submit', () => {
      submitBtn.setAttribute('aria-busy','true');
      submitBtn.textContent = 'Signing in...';
    });
  </script>
</body>
</html>
