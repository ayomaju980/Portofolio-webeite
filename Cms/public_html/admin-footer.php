<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /Login');
    exit;
}

$data1 = json_decode(file_get_contents('marquee1.json'), true);
$data2 = json_decode(file_get_contents('marquee2.json'), true);
$status = file_exists('status_marquee.json') 
    ? json_decode(file_get_contents('status_marquee.json'), true)
    : ['header' => true, 'footer' => true];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editor Teks Berjalan</title>
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
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
      padding:1rem;
      text-align:center;
      border-bottom:1px solid rgba(255,255,255,0.1);
    }
    .sidebar .branding img {
      height:50px;
      margin-bottom:.5rem;
    }
    .sidebar .nav-link {
      color:#ccc;
      padding:.75rem 1rem;
      display:flex;
      gap:.5rem;
      align-items:center;
    }
    .sidebar .nav-link:hover {
      background:rgba(255,255,255,0.1);
      color:#fff;
      text-decoration:none;
    }
    .sidebar .nav-link.active {
      background:#ffc107;
      color:#000;
      font-weight:600;
    }
    .sidebar .user-info {
      margin-top:auto;
      padding:1rem;
      border-top:1px solid rgba(255,255,255,0.1);
      font-size:.9rem;
    }
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
    ul { list-style:none; padding:0; }
    ul li {
      display:flex;
      align-items:center;
      justify-content:space-between;
      background:#f1f1f1;
      padding:10px;
      border-radius:8px;
      margin-bottom:10px;
    }
    ul li input[type="text"] {
      flex-grow:1;
      margin-right:10px;
      padding:8px;
      border:1px solid #ccc;
      border-radius:6px;
    }
    ul li button {
      background:transparent;
      border:none;
      font-size:18px;
      color:#ff4d4d;
      cursor:pointer;
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
  <a href="Remote" class="nav-link  active">
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

    <div class="section">
      <h2>📢 Running Text Header</h2>
      <form action="save-marquee-status.php" method="POST" style="margin-bottom:20px;">
        <label style="display:flex; align-items:center; gap:10px;">
          <input type="hidden" name="footer" value="<?= !empty($status['footer']) ? 1 : 0 ?>">
          <input type="checkbox" name="header" value="1" <?= !empty($status['header']) ? 'checked' : '' ?>>
          <strong>Aktifkan Marquee Header</strong>
        </label>
        <button type="submit" class="mt-2">💾 Simpan Status</button>
      </form>
      <form action="save-footer.php" method="POST">
        <ul id="list1">
          <?php foreach ($data1 as $item): ?>
          <li>
            <input type="text" name="items1[]" value="<?= htmlspecialchars($item) ?>">
            <button type="button" onclick="removeItem(this)">✕</button>
          </li>
          <?php endforeach; ?>
        </ul>
        <div class="actions">
          <button type="button" onclick="addItem('list1','items1[]')">➕ Tambah</button>
          <button type="submit">💾 Simpan</button>
        </div>
      </form>
      <div class="footer-note">Teks ini tampil di atas website.</div>
    </div>

    <div class="section">
      <h2>📝 Running Text Footer</h2>
      <form action="save-marquee-status.php" method="POST" style="margin-bottom:20px;">
        <label style="display:flex; align-items:center; gap:10px;">
          <input type="hidden" name="header" value="<?= !empty($status['header']) ? 1 : 0 ?>">
          <input type="checkbox" name="footer" value="1" <?= !empty($status['footer']) ? 'checked' : '' ?>>
          <strong>Aktifkan Marquee Footer</strong>
        </label>
        <button type="submit" class="mt-2">💾 Simpan Status</button>
      </form>
      <form action="save-footer2.php" method="POST">
        <ul id="list2">
          <?php foreach ($data2 as $item): ?>
          <li>
            <input type="text" name="items2[]" value="<?= htmlspecialchars($item) ?>">
            <button type="button" onclick="removeItem(this)">✕</button>
          </li>
          <?php endforeach; ?>
        </ul>
        <div class="actions">
          <button type="button" onclick="addItem('list2','items2[]')">➕ Tambah</button>
          <button type="submit">💾 Simpan</button>
        </div>
      </form>
      <div class="footer-note">Teks ini tampil di bawah website.</div>
    </div>

  </main>
</div>

<script>
function addItem(listId, nameAttr) {
  const ul = document.getElementById(listId);
  const li = document.createElement('li');
  li.innerHTML = `<input type="text" name="${nameAttr}" placeholder="Tulis teks..."> <button type="button" onclick="removeItem(this)">✕</button>`;
  ul.appendChild(li);
}
function removeItem(btn) {
  btn.parentElement.remove();
}

document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
