<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: Login'); exit; }

/* ===== Session Timeout ===== */
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset(); session_destroy();
    header("Location: Login?timeout=1"); exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

/* ===== Helper: load JSON aman jadi array ===== */
function load_json_array($path) {
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* ===== Data Portofolio ===== */
$data = load_json_array('data.json');
$totalPortfolio  = count($data);
$totalPublished  = count(array_filter($data, fn($d) => !empty($d['published'])));
$totalDraft      = max(0, $totalPortfolio - $totalPublished);

/* ===== Data Sertifikat ===== */
$certData = load_json_array('certificates.json');
$totalCertificate = count($certData);

/* ===== Hitung yang baru (≤ 24 jam) ===== */
$now = time();
$totalRecent = 0;
$recentTypeLabel = 'Baru/Update (< 24 Jam)';

foreach ($data as $d) {
  $updatedUnix = isset($d['updated_unix']) ? (int)$d['updated_unix'] : null;
  if (!$updatedUnix) continue;
  $isRecent = ($now - $updatedUnix) <= 86400;
  if ($isRecent) {
    $totalRecent++;
    if (!empty($d['published_at'])) {
      $pub = strtotime($d['published_at']);
      if ($pub !== false) {
        $diff = $updatedUnix - $pub;
        $recentTypeLabel = ($diff <= 60) ? 'Baru (< 24 Jam)' : 'Edit (< 24 Jam)';
      } else {
        $recentTypeLabel = 'Baru/Update (< 24 Jam)';
      }
    } else {
      $recentTypeLabel = 'Baru (< 24 Jam)';
    }
  }
}
if ($totalRecent === 0) $recentTypeLabel = 'Baru/Update (< 24 Jam)';

/* ===== Log Aktivitas ===== */
$logs = load_json_array('logs.json');
$recentLogs = array_slice(array_reverse($logs), 0, 5);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Portofolio CMS — Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="img/Logo.png" type="image/png">
<style>
  :root{
    --sidebar-w: 240px;
    --topbar-h: 56px;
    --radius: 14px;
    --shadow: 0 10px 30px rgba(16,24,40,.06);
    --shadow-hover: 0 12px 36px rgba(16,24,40,.12);
    --surface: #ffffff;
    --bg: #f6f7fb;
    --text: #0f172a;
    --muted: #64748b;
    --accent-2: #ffc107;
  }
  html,body{height:100%}
  body{ background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.5; margin:0; }

  /* Topbar (mobile & tablet) */
  .topbar{
    position: fixed; inset: 0 0 auto 0; height: var(--topbar-h);
    display: none; align-items: center; gap: .75rem;
    background: var(--surface); border-bottom: 1px solid #e5e7eb;
    padding: 0 .75rem; z-index: 1045;
  }
  .topbar__brand{ display:flex; align-items:center; gap:.5rem; font-weight:700 }
  .topbar__brand img{ height: 28px }

  /* Sidebar */
  .sidebar{
    position: fixed; inset:0 auto 0 0; width: var(--sidebar-w);
    display: flex; flex-direction: column;
    background: #111827; color:#fff; z-index: 1050;
    transform: translateX(0); transition: transform .3s ease; will-change: transform;
  }
  .sidebar__head{ padding: 1rem; text-align:center; border-bottom:1px solid rgba(255,255,255,.08) }
  .sidebar__head img{ height: 80px; margin-bottom:.5rem }
  .sidebar__brand{ font-weight:700; letter-spacing:.2px; font-size: .98rem }
  .sidebar__nav{ padding: .5rem }
  .sidebar__link{
    display:flex; align-items:center; gap:.6rem;
    padding:.7rem .9rem; border-radius: 10px;
    color:#d1d5db; text-decoration:none; font-weight:600;
    transition: background .2s ease, color .2s ease, transform .15s ease;
  }
  .sidebar__link:hover{ background: rgba(255,255,255,.08); color:#fff; transform: translateX(2px); }
  .sidebar__link.active{ background: var(--accent-2); color:#111827; }
  .sidebar__user{ margin-top:auto; padding: 1rem; border-top:1px solid rgba(255,255,255,.08); font-size: .92rem; }

  /* Backdrop (mobile) */
  .backdrop{ position: fixed; inset:0; background: rgba(0,0,0,.38); opacity:0; visibility:hidden; transition: .2s ease; z-index: 1040; }
  .backdrop.show{ opacity:1; visibility:visible }

  /* Main */
  .main{ margin-left: var(--sidebar-w); padding: 2rem clamp(1rem, 2vw, 2rem); min-height: 100%; }
  .page-title{ font-size: clamp(1.15rem, 1.6vw, 1.5rem); font-weight: 800; letter-spacing:.2px; display:flex; align-items:center; gap:.6rem; }

  /* Cards */
  .kpi-card{ background: var(--surface); border: 1px solid #eef2f6; border-radius: 14px; padding: 1rem 1.1rem; box-shadow: var(--shadow); transition: box-shadow .2s ease, transform .15s ease; }
  .kpi-card:hover{ box-shadow: var(--shadow-hover); transform: translateY(-2px) }
  .kpi-label{ color: var(--muted); font-weight: 700; font-size: clamp(.78rem,.9vw,.9rem); letter-spacing:.2px; margin-bottom:.25rem }
  .kpi-value{ font-weight: 800; font-size: clamp(1.25rem, 2vw, 1.8rem); color:#0b1324; line-height:1.1 }
  .card-clean{ background: var(--surface); border: 1px solid #eef2f6; border-radius: 14px; box-shadow: var(--shadow); }
  .card-clean .card-header{ background: transparent; border-bottom:1px solid #eef2f6; padding: .9rem 1.1rem; font-weight:700; }
  .card-clean .card-body{ padding: 1.1rem }
  .btn{ border-radius: 12px; font-weight:700 }
  .btn i{ vertical-align:-2px }

  /* Responsive */
  @media (min-width: 992px){ body { padding-left: 0 } }
  @media (max-width: 1199.98px){ .main{ padding: 1.5rem 1rem } }
  @media (max-width: 991.98px){
    .topbar{ display:flex }
    .main{ margin-left: 0; padding-top: calc(var(--topbar-h) + 1rem) }
    .sidebar{ transform: translateX(-102%) }
    .sidebar.show{ transform: translateX(0) }
    .actions-scroll{ display:flex; gap:.75rem; overflow-x:auto; -webkit-overflow-scrolling:touch; scroll-behavior:smooth; }
    .actions-scroll > .btn{ flex: 0 0 auto; min-width: 180px }
  }
  @media (max-width: 767.98px){
    .toast-container{ top: var(--topbar-h) }
    .kpi-card{ padding: .9rem }
  }
  @media (max-width: 375px){ .actions-scroll > .btn{ min-width: 160px; font-size:.95rem } }
  @media (prefers-reduced-motion: reduce){ *{ transition: none !important; scroll-behavior: auto !important } }
</style>
</head>
<body>

<!-- Topbar (mobile/tablet) -->
<header class="topbar d-lg-none">
  <button id="btnMenu" class="btn btn-outline-secondary btn-sm" aria-label="Toggle menu"><i class="bi bi-list" style="font-size:1.2rem"></i></button>
  <div class="topbar__brand">
    <img src="img/ICON.png" alt="Logo">
    <span>Portofolio-CMS</span>
  </div>
</header>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar" aria-label="Samping">
  <div class="sidebar__head">
    <img src="img/ICON.png" alt="Logo">
    <div class="sidebar__brand">Portofolio-CMS</div>
  </div>
  <nav class="sidebar__nav">
    <a class="sidebar__link active" href="Dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
    <a class="sidebar__link" href="Remote"><i class="bi bi-pencil-square"></i><span>Editor Teks</span></a>
    <a class="sidebar__link" href="Ganti-Password"><i class="bi bi-key"></i><span>Ganti Password</span></a>
    <a class="sidebar__link" href="List-Sertifikat"><i class="bi bi-award-fill"></i><span>Sertifikat</span></a>
    <a class="sidebar__link" href="List-Portofolio"><i class="bi bi-folder2-open"></i><span>Portofolio</span></a>
    <a class="sidebar__link" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
  </nav>
  <div class="sidebar__user">
    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']) ?><br>
    <span class="badge text-bg-secondary rounded-pill mt-1">User</span>
  </div>
</aside>

<!-- Backdrop -->
<div id="backdrop" class="backdrop" hidden></div>

<!-- Toast -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="welcomeToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">Halo, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>! Selamat datang kembali.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<main class="main">
  <h1 class="page-title mb-4"><i class="bi bi-speedometer2 text-warning"></i>Dashboard</h1>

  <!-- KPI -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bi bi-collection-fill me-1 text-primary"></i>Total Portofolio</div>
        <div class="kpi-value"><?= $totalPortfolio ?></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bi bi-eye-fill me-1 text-success"></i>Published</div>
        <div class="kpi-value"><?= $totalPublished ?></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bi bi-file-earmark-diff-fill me-1 text-danger"></i>Total Draft</div>
        <div class="kpi-value"><?= $totalDraft ?></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bi bi-clock me-1 text-info"></i><?= htmlspecialchars($recentTypeLabel) ?></div>
        <div class="kpi-value"><?= $totalRecent ?></div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bi bi-award me-1 text-warning"></i>Total Sertifikat</div>
        <div class="kpi-value"><?= $totalCertificate ?></div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <section class="card-clean mb-4">
    <div class="card-header"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</div>
    <div class="card-body actions-scroll">
      <a href="Add-Portofolio" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Tambah Portofolio</a>
      <a href="Remote" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit Teks Berjalan</a>
      <a href="Ganti-Password" class="btn btn-warning text-dark"><i class="bi bi-key me-1"></i> Ganti Password</a>
      <a href="Add-Sertifikat" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Tambah Sertifikat</a>
    </div>
  </section>

  <!-- Aktivitas Terbaru -->
  <section class="card-clean">
    <div class="card-header">Aktivitas Terbaru</div>
    <div class="card-body">
      <?php if (!empty($recentLogs)): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentLogs as $log): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start px-0">
          <div>
            <div class="fw-bold"><?= htmlspecialchars($log['user'] ?? 'User') ?></div>
            <div class="text-muted"><?= htmlspecialchars($log['action'] ?? '-') ?></div>
          </div>
          <span class="badge text-bg-secondary">
            <?= date('d M Y H:i', strtotime($log['timestamp'] ?? 'now')) ?>
          </span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
        <p class="text-muted mb-0">Belum ada aktivitas baru.</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('backdrop');
  const btnMenu  = document.getElementById('btnMenu');

  const open = () => {
    sidebar.classList.add('show');
    backdrop.classList.add('show');
    backdrop.hidden = false;
    document.body.style.overflow = 'hidden';
  };
  const close = () => {
    sidebar.classList.remove('show');
    backdrop.classList.remove('show');
    setTimeout(() => { backdrop.hidden = true; }, 200);
    document.body.style.overflow = '';
  };
  const toggle = () => sidebar.classList.contains('show') ? close() : open();

  if (btnMenu) btnMenu.addEventListener('click', toggle);
  if (backdrop) backdrop.addEventListener('click', close);
  window.addEventListener('resize', () => { if (window.innerWidth >= 992) close(); });

  // Toast welcome
  document.addEventListener('DOMContentLoaded', () => {
    const t = document.getElementById('welcomeToast');
    if (t && window.bootstrap) new bootstrap.Toast(t, { delay: 2800 }).show();
  });

  // Non-essential: disable right click
  document.addEventListener('contextmenu', e => e.preventDefault());

  // Realtime log refresh (tiap 10 detik)
  setInterval(() => {
    fetch('logs.json', { cache: 'no-store' })
      .then(res => res.json())
      .then(data => {
        if (!Array.isArray(data)) return; // guard
        const listContainer = document.querySelector('.card-clean .list-group');
        const emptyText     = document.querySelector('.card-clean .text-muted.mb-0');
        if (!listContainer) return;

        // build last 5 reversed
        const items = data.slice(-5).reverse();
        if (items.length === 0) {
          if (listContainer) listContainer.innerHTML = '';
          if (emptyText) emptyText.style.display = '';
          return;
        }
        if (emptyText) emptyText.style.display = 'none';
        listContainer.innerHTML = '';
        items.forEach(item => {
          const li = document.createElement('li');
          li.className = 'list-group-item d-flex justify-content-between align-items-start px-0';
          const t = item.timestamp
            ? new Date(item.timestamp.replace(' ', 'T')).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
            : '—';
          li.innerHTML = `
            <div>
              <div class="fw-bold">${(item.user ?? 'User')}</div>
              <div class="text-muted">${(item.action ?? '-')}</div>
            </div>
            <span class="badge text-bg-secondary">${t}</span>
          `;
          listContainer.appendChild(li);
        });
      })
      .catch(() => {});
  }, 10000);
})();
</script>
</body>
</html>
