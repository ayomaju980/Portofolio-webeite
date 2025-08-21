<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /Login');
    exit;
}

// Load data JSON aman (fallback array kosong bila file kosong/invalid)
function load_json_array($path, $default = []) {
    if (!file_exists($path)) return $default;
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return $default;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

$data1  = load_json_array('marquee1.json', []); // header
$data2  = load_json_array('marquee2.json', []); // footer
$status = load_json_array('status_marquee.json', ['header' => true, 'footer' => true]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portofolio CMS - Editor Teks Berjalan</title>
  <link rel="icon" href="img/Logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --sidebar-w: 240px;
      --topbar-h: 56px;
    }
    body { background-color: #f8f9fa; margin:0; }

    /* Desktop: beri ruang untuk sidebar */
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

    /* Mobile behavior: sidebar off-canvas + konten diberi padding top */
    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .sidebar .close-btn{ display:block; }
      .topbar{ display:flex; }
      .main-wrap{ padding-top: calc(var(--topbar-h) + 1rem); }
    }

    /* ===== Konten ===== */
    main { flex-grow:1; }
    .section {
      max-width:700px;
      margin:auto;
      background:#fff;
      padding:30px;
      border-radius:12px;
      box-shadow:0 8px 20px rgba(0,0,0,0.08);
      margin-top:30px;
    }
    .section h2 {
      color:#FAAD1B;
      text-align:center;
      margin-bottom:20px;
    }

    ul { list-style:none; padding:0; margin:0; }
    ul li {
      display:flex;
      align-items:center;
      justify-content:space-between;
      background:#f1f1f1;
      padding:10px;
      border-radius:8px;
      margin-bottom:10px;
      gap:10px;
    }
    ul li input[type="text"] {
      flex:1 1 auto;
      padding:8px;
      border:1px solid #ccc;
      border-radius:6px;
      min-width: 0; /* biar tidak overflow di mobile */
    }
    ul li button {
      background:transparent;
      border:none;
      font-size:18px;
      color:#ff4d4d;
      cursor:pointer;
      line-height:1;
    }
    button {
      display:inline-flex;
      align-items:center;
      gap:6px;
      background:#FAAD1B;
      color:black;
      border:none;
      padding:10px 16px;
      font-weight:600;
      border-radius:8px;
      cursor:pointer;
      transition:background 0.2s,transform 0.2s;
    }
    button:hover {
      background:#e19b17;
      transform:translateY(-1px);
    }
    .actions {
      display:flex;
      justify-content:space-between;
      margin-top:20px;
      flex-wrap:wrap;
      gap:10px;
    }
    .footer-note {
      text-align:center;
      font-size:14px;
      color:#777;
      margin-top:20px;
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
  <a href="Remote" class="nav-link active"><i class="bi bi-pencil-square"></i> Editor Teks</a>
  <a href="Ganti-Password" class="nav-link"><i class="bi bi-key"></i> Ganti Password</a>
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

<!-- Main Content -->
<main class="main-wrap p-4 w-100">

  <!-- SECTION: Header marquee -->
  <div class="section">
    <h2>📢 Running Text Header</h2>

    <!-- Status toggle header (kirim juga nilai footer saat ini agar tetap terjaga) -->
    <form action="save-marquee-status.php" method="POST" class="mb-3">
      <label class="d-flex align-items-center gap-2">
        <input type="hidden" name="footer" value="<?= !empty($status['footer']) ? 1 : 0 ?>">
        <input type="checkbox" name="header" value="1" <?= !empty($status['header']) ? 'checked' : '' ?>>
        <strong>Aktifkan Marquee Header</strong>
      </label>
      <button type="submit" class="mt-2">💾 Simpan Status</button>
    </form>

    <!-- List item header -->
    <form action="save-footer.php" method="POST">
      <ul id="list1">
        <?php foreach ($data1 as $item): ?>
          <li>
            <input type="text" name="items1[]" value="<?= htmlspecialchars((string)$item) ?>" placeholder="Tulis teks...">
            <button type="button" onclick="removeItem(this)" aria-label="Hapus item">✕</button>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="actions">
        <button type="button" onclick="addItem('list1','items1[]')">➕ Tambah</button>
        <button type="submit">💾 Simpan</button>
      </div>
      <div class="footer-note">Teks ini tampil di bagian atas website.</div>
    </form>
  </div>

  <!-- SECTION: Footer marquee -->
  <div class="section">
    <h2>📝 Running Text Footer</h2>

    <!-- Status toggle footer (kirim juga nilai header saat ini agar tetap terjaga) -->
    <form action="save-marquee-status.php" method="POST" class="mb-3">
      <label class="d-flex align-items-center gap-2">
        <input type="hidden" name="header" value="<?= !empty($status['header']) ? 1 : 0 ?>">
        <input type="checkbox" name="footer" value="1" <?= !empty($status['footer']) ? 'checked' : '' ?>>
        <strong>Aktifkan Marquee Footer</strong>
      </label>
      <button type="submit" class="mt-2">💾 Simpan Status</button>
    </form>

    <!-- List item footer -->
    <form action="save-footer2.php" method="POST">
      <ul id="list2">
        <?php foreach ($data2 as $item): ?>
          <li>
            <input type="text" name="items2[]" value="<?= htmlspecialchars((string)$item) ?>" placeholder="Tulis teks...">
            <button type="button" onclick="removeItem(this)" aria-label="Hapus item">✕</button>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="actions">
        <button type="button" onclick="addItem('list2','items2[]')">➕ Tambah</button>
        <button type="submit">💾 Simpan</button>
      </div>
      <div class="footer-note">Teks ini tampil di bagian bawah website.</div>
    </form>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle (mobile)
function toggleSidebar() {
  const sb = document.getElementById('sidebarMenu');
  const ov = document.querySelector('.sidebar-overlay');
  sb.classList.toggle('show');
  ov.classList.toggle('show');
}

// List handlers
function addItem(listId, nameAttr) {
  const ul = document.getElementById(listId);
  const li = document.createElement('li');
  li.innerHTML = `
    <input type="text" name="${nameAttr}" placeholder="Tulis teks...">
    <button type="button" onclick="removeItem(this)" aria-label="Hapus item">✕</button>`;
  ul.appendChild(li);
}
function removeItem(btn) {
  const li = btn.closest('li');
  if (li) li.remove();
}

// Opsional: nonaktifkan klik kanan
document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
