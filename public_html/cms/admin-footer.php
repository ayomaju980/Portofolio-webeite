<?php
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
  <title>Admin - Dual Text Editor</title>
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
    }

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

    .section {
      max-width: 700px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 14px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    h2 {
      color: #FAAD1B;
      text-align: center;
    }

    ul {
      list-style: none;
      padding: 0;
    }

    ul li {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #f1f1f1;
      padding: 10px 12px;
      border-radius: 8px;
      margin-bottom: 10px;
      transition: background-color 0.3s;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    }

    ul li:hover {
      background-color: #eaeaea;
    }

    ul li input[type="text"] {
      flex-grow: 1;
      margin-right: 10px;
      padding: 10px;
      font-size: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      background-color: white;
    }

    ul li button {
      background-color: transparent;
      color: #ff4d4d;
      font-size: 18px;
      padding: 4px 8px;
      border: none;
      cursor: pointer;
      transition: color 0.2s;
    }

    ul li button:hover {
      color: #cc0000;
    }

    button {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background-color: #FAAD1B;
      color: black;
      border: none;
      padding: 10px 16px;
      font-size: 15px;
      font-weight: bold;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s, transform 0.2s;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    button:hover {
      background-color: #e19b17;
      transform: translateY(-1px);
    }

    .actions {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }

    .footer-note {
      text-align: center;
      font-size: 14px;
      color: #777;
      margin-top: 20px;
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

<div class="section">
  <h2>📢 Running Text Header</h2>
  <form action="save-marquee-status.php" method="POST" style="margin-bottom: 20px;">
    <label style="display: flex; align-items: center; gap: 10px;">
      <input type="hidden" name="footer" value="<?= isset($status['footer']) && $status['footer'] ? 1 : 0 ?>">
      <input type="checkbox" name="header" value="1" <?= !empty($status['header']) ? 'checked' : '' ?> style="transform: scale(1.3);">
      <strong>Aktifkan Marquee Header</strong>
    </label>
    <button type="submit" style="margin-top: 10px;">📏 Simpan Status</button>
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
      <button type="button" onclick="addItem('list1', 'items1[]')">➕ Tambah</button>
      <button type="submit">📏 Simpan</button>
    </div>
  </form>
  <div class="footer-note">Running text ini akan muncul di bagian atas website.</div>
</div>

<div class="section">
  <h2>📝 Running Text Footer</h2>
  <form action="save-marquee-status.php" method="POST" style="margin-bottom: 20px;">
    <label style="display: flex; align-items: center; gap: 10px;">
      <input type="hidden" name="header" value="<?= isset($status['header']) && $status['header'] ? 1 : 0 ?>">
      <input type="checkbox" name="footer" value="1" <?= !empty($status['footer']) ? 'checked' : '' ?> style="transform: scale(1.3);">
      <strong>Aktifkan Marquee Footer</strong>
    </label>
    <button type="submit" style="margin-top: 10px;">💾 Simpan Status</button>
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
      <button type="button" onclick="addItem('list2', 'items2[]')">➕ Tambah</button>
      <button type="submit">📏 Simpan</button>
    </div>
  </form>
  <div class="footer-note">Running text ini akan muncul di bagian bawah website.</div>
</div>

<script>
  function addItem(listId, nameAttr) {
    const ul = document.getElementById(listId);
    const li = document.createElement('li');
    li.innerHTML = `<input type="text" name="${nameAttr}" placeholder="Tulis teks..."> <button type="button" onclick="removeItem(this)">✕</button>`;
    ul.appendChild(li);
  }

  function removeItem(button) {
    button.parentElement.remove();
  }

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
