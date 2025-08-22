<?php
// Ambil ID dari parameter URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data JSON dari CMS (pakai cache buster ?t=)
$raw = @file_get_contents('https://cms.aksanazachri.my.id/data.json?t=' . time());
$data = $raw ? json_decode($raw, true) : [];

// Cari portofolio berdasarkan ID
$portfolio = null;
if (is_array($data)) {
    foreach ($data as $item) {
        if (isset($item['id']) && (int)$item['id'] === $id) {
            $portfolio = $item;
            break;
        }
    }
}

// Jika tidak ditemukan
if (!$portfolio) {
    echo "<h3 class='text-center mt-5'>Portofolio tidak ditemukan.</h3>";
    exit;
}

// Format tanggal ke Month Year (English)
$formattedDate = '';
if (!empty($portfolio['date'])) {
    $monthMap = [
        '01'=>'January','02'=>'February','03'=>'March','04'=>'April',
        '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
        '09'=>'September','10'=>'October','11'=>'November','12'=>'December'
    ];
    $parts = explode('-', $portfolio['date']);
    if (count($parts) === 2 && isset($monthMap[$parts[1]])) {
        $formattedDate = $monthMap[$parts[1]] . ' ' . $parts[0];
    } else {
        $formattedDate = htmlspecialchars($portfolio['date']);
    }
}

// Fungsi path gambar
function resolveImagePath($path) {
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    return 'https://cms.aksanazachri.my.id/' . ltrim($path, '/');
}

// Simple sanitizer untuk URL eksternal
function safeUrl($url) {
    $u = trim((string)$url);
    if ($u === '' || $u === '#') return '';
    // hanya ijinkan http(s)
    if (!preg_match('~^https?://~i', $u)) return '';
    return $u;
}

// Fungsi format deskripsi agar rapi
function formatDescription($text) {
    // Izinkan hanya tag HTML aman
    $allowedTags = '<p><br><strong><b><i><em><ul><ol><li>';
    $text = strip_tags($text, $allowedTags);

    // Kalau tidak ada <p>, bikin otomatis berdasarkan enter
    if (strpos($text, '<p>') === false) {
        $paragraphs = preg_split("/\n\s*\n/", $text);
        $html = '';
        foreach ($paragraphs as $p) {
            $p = nl2br(trim($p));
            if (!empty($p)) $html .= "<p>$p</p>";
        }
        return $html;
    }
    return $text;
}

// Siapkan link proyek & PRD yang aman
$projectLink = safeUrl($portfolio['link'] ?? '');
$prdLink     = safeUrl($portfolio['prd_link'] ?? '');

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Portofolio - <?= htmlspecialchars($portfolio['title']) ?></title>
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
/* =========================
   THEME TOKENS
   ========================= */
:root{
  --mainColor:#00142E;
  --secondaryColor:#32012F;
  --acsentColor:#DD7900;
  --highlightColor:#FAAD1B;
  --cardLight:#f5f5f5;
  --sectionGradient:linear-gradient(135deg,#00142E,#32012F);

  --radius:12px;
  --radiusPill:999px;
  --shadow:0 8px 24px rgba(0,0,0,.18);
  --shadowHover:0 12px 28px rgba(0,0,0,.24);
  --ring:0 0 0 3px rgba(250,173,27,.35);
}

/* =========================
   GLOBAL ENHANCEMENTS
   ========================= */
*{box-sizing:border-box}
img{max-width:100%;height:auto}
a{color:inherit}
a.no-link{color:inherit;text-decoration:none;font-weight:normal}
@media (prefers-reduced-motion:no-preference){
  :root{scroll-behavior:smooth}
}

/* =========================
   COVER IMAGE
   ========================= */
.cover-image{
  width:100%;
  max-height:520px;
  object-fit:cover;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  transition:transform .5s ease, box-shadow .5s ease;
  outline:1px solid rgba(255,255,255,.12);
  outline-offset:-1px;
}
.cover-image:hover{
  transform:scale(1.01);
  box-shadow:var(--shadowHover);
}

/* =========================
   BADGE
   ========================= */
.badge-custom{
  display:inline-block; /* fix typo */
  font-size:.88rem;
  font-weight:600;
  padding:.42em .8em;
  margin-right:6px;
  border-radius:var(--radiusPill);
  letter-spacing:.2px;
  color:#1a1a1a;
  background:
    radial-gradient(120% 120% at 0% 0%, rgba(255,255,255,.7), transparent 40%),
    linear-gradient(135deg,#ffd56a,#FAAD1B);
  box-shadow:0 2px 8px rgba(250,173,27,.35);
  border:1px solid rgba(250,173,27,.4);
  transform:translateZ(0);
  transition:transform .25s ease, box-shadow .25s ease;
}
.badge-custom:hover{
  transform:translateY(-1px);
  box-shadow:0 6px 14px rgba(250,173,27,.45);
}

/* =========================
   DESCRIPTION
   ========================= */
.description{line-height:1.6;padding:0 5px}
.description p{margin-bottom:10px;text-align:justify}
.description p:last-child{margin-bottom:0}

/* =========================
   BUTTONS
   ========================= */
.btn-keren{
  display:inline-flex; align-items:center; gap:.55rem;
  padding:12px 22px; font-size:16px; font-weight:700; color:#111;
  background:linear-gradient(135deg,#ffce3c 0%, #FAAD1B 100%);
  border:none; border-radius:var(--radiusPill); text-decoration:none;
  box-shadow:0 8px 18px rgba(250,173,27,.35), inset 0 -2px 0 rgba(0,0,0,.08);
  transition:transform .25s ease, box-shadow .25s ease, filter .25s ease;
  will-change:transform;
}
.btn-keren:hover{ transform:translateY(-2px); box-shadow:0 12px 22px rgba(250,173,27,.5), inset 0 -2px 0 rgba(0,0,0,.08); color:#000; filter:saturate(1.05) }
.btn-keren:active{ transform:translateY(0); box-shadow:0 6px 12px rgba(250,173,27,.4) inset }
.btn-keren:focus-visible{ outline:none; box-shadow:var(--ring), 0 8px 18px rgba(250,173,27,.35) }

/* Varian tombol outline agar selaras tema */
.btn-outline-brand{
  display:inline-flex; align-items:center; gap:.5rem;
  padding:11px 20px; font-weight:700; border-radius:var(--radiusPill);
  border:1.5px solid var(--highlightColor); color:var(--highlightColor);
  text-decoration:none; transition:.25s ease;
}
.btn-outline-brand:hover{
  background:var(--highlightColor);
  color:#00142E;
  box-shadow:0 10px 22px rgba(250,173,27,.35);
}

/* =========================
   UPDATE INFO
   ========================= */
.update-info{ font-size:.9rem; font-style:italic; opacity:.75; letter-spacing:.2px }

/* =========================
   FOOTER
   ========================= */
.footer-custom{
  position:relative;
  background:linear-gradient(0deg, rgba(0,0,0,.25), rgba(0,0,0,.25)), var(--mainColor);
  color:#FAFAFA; padding:3rem 0 2.5rem; border-top:1px solid rgba(255,255,255,.12);
  backdrop-filter:saturate(140%) blur(6px);
}
.footer-custom::before{
  content:""; position:absolute; inset:0 0 auto 0; height:3px;
  background:linear-gradient(90deg, transparent, var(--highlightColor), var(--acsentColor), transparent);
  opacity:.6;
}
.footer-title{ font-weight:700; margin:0 0 1rem; color:#FFC107; letter-spacing:.2px }
.footer-desc{ font-size:.98rem; line-height:1.75; color:#EAEAEA; opacity:.95 }
.footer-col.about{text-align:left}
.footer-col.centered{text-align:center}
.footer-contact{ font-size:.95rem; margin:0 0 .6rem; color:#D8D8D8; display:flex; align-items:center; gap:.5rem }
.footer-custom a:not(.no-link){ position:relative; text-decoration:none }
.footer-custom a:not(.no-link)::after{
  content:""; position:absolute; left:0; right:0; bottom:-2px; height:1.5px;
  background:currentColor; transform:scaleX(0); transform-origin:left; transition:transform .25s ease; opacity:.7;
}
.footer-custom a:not(.no-link):hover::after{ transform:scaleX(1) }
.social-icons{margin-top:1rem;text-align:center}
.social-icons a{
  display:inline-flex; align-items:center; justify-content:center; width:42px;height:42px;
  border-radius:50%; border:1px solid #FFC107; color:#FFC107; background:rgba(255,193,7,.05);
  margin:0 6px; transition:transform .25s ease, box-shadow .25s ease, background .25s ease, color .25s ease;
  box-shadow:0 3px 12px rgba(255,193,7,.15);
}
.social-icons a:hover{ background:#FFC107; color:#00142E; transform:translateY(-3px); box-shadow:0 10px 22px rgba(255,193,7,.35) }
.footer-custom .border-top{border-color:#666 !important}

/* =========================
   RESPONSIVE
   ========================= */
@media (max-width:768px){
  .cover-image{max-height:380px}
  .description{max-width:unset}
  .btn-keren, .btn-outline-brand{width:100%; justify-content:center}
}
  </style>
</head>
<body>

<div class="container py-5">
  <a href="/Portofolio" class="btn-keren mb-4" aria-label="Back to portfolio list">Back</a>

  <div class="row g-4">
    <div class="col-md-6">
      <img src="<?= resolveImagePath($portfolio['img']) ?>" class="cover-image shadow-sm" alt="<?= htmlspecialchars($portfolio['title']) ?>">
    </div>
    <div class="col-md-6">
      <h2 class="fw-bold"><?= htmlspecialchars($portfolio['title']) ?></h2>

      <?php if (!empty($portfolio['type'])): ?>
        <div class="mb-3">
          <?php foreach ((array)$portfolio['type'] as $type): ?>
            <span class="badge bg-warning text-dark badge-custom"><?= htmlspecialchars($type) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($portfolio['client'])): ?>
        <p><strong>Project Name:</strong> <?= htmlspecialchars($portfolio['client']) ?></p>
      <?php endif; ?>

      <?php if ($formattedDate): ?>
        <p><strong>Created:</strong> <?= $formattedDate ?></p>
      <?php endif; ?>

      <?php if (!empty($portfolio['updated_at'])): ?>
        <p class="update-info"><strong>Last Updated:</strong> <?= date('d M Y', strtotime($portfolio['updated_at'])) ?></p>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 mt-3">
        <?php if ($projectLink): ?>
          <a href="<?= htmlspecialchars($projectLink) ?>" target="_blank" rel="noopener" class="btn-outline-brand">View Project</a>
        <?php endif; ?>

        <?php if ($prdLink): ?>
          <a href="<?= htmlspecialchars($prdLink) ?>" target="_blank" rel="noopener" class="btn-outline-brand" title="Product Requirements Document">View PRD</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($portfolio['description'])): ?>
    <div class="row mt-5">
      <div class="col">
        <h4>Summary</h4>
        <div class="description"><?= formatDescription($portfolio['description']) ?></div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Footer Start -->
<footer class="footer-custom mt-5 py-5 border-top">
  <div class="container">
    <div class="row gy-4 justify-content-between">

      <!-- About -->
      <div class="col-md-4 footer-col text-md-start text-center">
        <h6 class="footer-title">AKSANA ZACHRI SATRIA</h6>
        <p class="footer-desc">
          UI/UX & Product Designer based in Indonesia.<br>
          Passionate about creating intuitive digital experiences and storytelling through design.
        </p>
      </div>

      <!-- Social Media -->
      <div class="col-md-4 col-lg-3 text-center text-md-start">
        <h6 class="footer-title">Connect With Me</h6>
        <div class="d-flex justify-content-center justify-content-md-start gap-3 social-icons">
          <!-- Instagram -->
          <a href="https://www.instagram.com/aksanazachri.my.id" target="_blank" aria-label="Instagram" title="Instagram">
            <!-- SVG Instagram -->
            <svg width="20" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#i)"><path d="M26 0C18.9442 0 18.057 0.0325 15.2848 0.156C12.5125 0.286 10.6242 0.7215 8.97 1.365C7.23399 2.01606 5.66196 3.04004 4.36475 4.36475C3.04004 5.66196 2.01606 7.23399 1.365 8.97C0.7215 10.621 0.28275 12.5125 0.156 15.275C0.0325 18.0537 0 18.9378 0 26.0033C0 33.0623 0.0325 33.9463 0.156 36.7185C0.286 39.4875 0.7215 41.3757 1.365 43.03C2.03125 44.7395 2.9185 46.189 4.36475 47.6352C5.80775 49.0815 7.25725 49.972 8.96675 50.635C10.6243 51.2785 12.5093 51.7172 15.2783 51.844C18.0538 51.9675 18.9377 52 26 52C33.0623 52 33.943 51.9675 36.7185 51.844C39.4843 51.714 41.379 51.2785 43.0333 50.635C44.7681 49.9835 46.339 48.9596 47.6352 47.6352C49.0815 46.189 49.9687 44.7395 50.635 43.03C51.2752 41.3757 51.714 39.4875 51.844 36.7185C51.9675 33.9463 52 33.0623 52 26C52 18.9377 51.9675 18.0538 51.844 15.2783C51.714 12.5125 51.2752 10.621 50.635 8.97C49.9839 7.23399 48.96 5.66196 47.6352 4.36475C46.338 3.04004 44.766 2.01606 43.03 1.365C41.3725 0.7215 39.481 0.28275 36.7152 0.156C33.9397 0.0325 33.059 0 25.9935 0H26Z" fill="#ffffff"/></g><defs><clipPath id="i"><rect width="52" height="52" fill="#ffffff"/></clipPath></defs></svg>
          </a>
          <!-- LinkedIn -->
          <a href="https://www.linkedin.com/in/aksanazachri" target="_blank" aria-label="LinkedIn" title="LinkedIn">
            <svg width="20" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#l)"><path d="M0 3.7245C0 1.66725 1.7095 0 3.81875 0H48.1812C50.2905 0 52 1.66725 52 3.7245V48.2755C52 50.3328 50.2905 52 48.1812 52H3.81875C1.7095 52 0 50.3328 0 48.2755V3.7245ZM16.0647 43.5305V20.0493H8.2615V43.5305H16.0647ZM12.1648 16.8415C14.885 16.8415 16.5782 15.041 16.5782 12.7855C16.5295 10.4813 14.8883 8.7295 12.2168 8.7295C9.54525 8.7295 7.8 10.4845 7.8 12.7855C7.8 15.041 9.49325 16.8415 12.1127 16.8415H12.1648ZM28.1158 43.5305V30.4168C28.1158 29.7148 28.1677 29.0127 28.3757 28.5122C28.938 27.1115 30.2218 25.6587 32.3798 25.6587C35.204 25.6587 36.3317 27.8103 36.3317 30.9693V43.5305H44.135V30.0625C44.135 22.8475 40.287 19.4935 35.152 19.4935C31.0115 19.4935 29.1558 21.7685 28.1158 23.3707V23.452H28.0638L28.1158 23.3707V20.0493H20.3157C20.4132 22.2528 20.3157 43.5305 20.3157 43.5305H28.1158Z" fill="#ffffff"/></g><defs><clipPath id="l"><rect width="52" height="52" fill="#ffffff"/></clipPath></defs></svg>
          </a>
          <!-- WhatsApp -->
          <a href="https://api.whatsapp.com/send/?phone=62085157753658" target="_blank" aria-label="WhatsApp" title="WhatsApp">
            <svg width="20" viewBox="0 0 52 52" fill="white" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#w)"><path d="M44.2032 7.55968C41.8197 5.15304 38.9808 3.24513 35.8521 1.94723C32.7235 0.649341 29.3677 -0.0125477 25.9805 0.000180131C11.7877 0.000180131 0.221 11.5637 0.208 25.7597C0.208 30.3064 1.3975 34.7297 3.64325 38.6459L0 52.0002L13.663 48.4187C17.4411 50.4799 21.6767 51.5584 25.9805 51.5549H25.9935C40.1895 51.5549 51.753 39.9914 51.766 25.7824C51.7689 22.3961 51.1019 19.0427 49.8034 15.9152C48.5049 12.7878 46.6038 9.9481 44.2032 7.55968ZM25.9805 47.1934C22.1428 47.1904 18.3762 46.1578 15.0735 44.2034L14.2935 43.7354L6.188 45.8609L8.3525 37.9537L7.8455 37.1379C5.69987 33.7265 4.5651 29.7768 4.57275 25.7467C4.57275 13.9622 14.183 4.34868 25.9935 4.34868C28.8072 4.34363 31.594 4.89557 34.1933 5.97268C36.7926 7.04978 39.1531 8.63075 41.1385 10.6244C43.1304 12.6107 44.7096 14.9714 45.785 17.5707C46.8604 20.17 47.4108 22.9565 47.4045 25.7694C47.3915 37.5962 37.7812 47.1934 25.9805 47.1934Z" fill="#ffffff"/></g><defs><clipPath id="w"><rect width="52" height="52" fill="#ffffff"/></clipPath></defs></svg>
          </a>
          <!-- Behance -->
          <a href="https://www.behance.net/aksanazachri_" target="_blank" aria-label="Behance" title="Behance">
            <svg width="20" viewBox="0 0 52 52" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M15.1255 9.75C16.6237 9.75 18.0083 9.86375 19.279 10.205C20.5465 10.4325 21.5897 10.907 22.516 11.4757C23.4422 12.0445 24.1312 12.8602 24.5992 13.9035C25.0542 14.9435 25.3012 16.2142 25.3012 17.5987C25.3012 19.2107 24.9567 20.5953 24.1442 21.6353C23.4455 22.6753 22.3048 23.6047 20.904 24.2905C22.8735 24.8625 24.3718 25.9025 25.285 27.287C26.1983 28.6715 26.7833 30.3972 26.7833 32.3667C26.7833 33.9787 26.442 35.3633 25.857 36.5203C25.2755 37.6862 24.3994 38.68 23.3155 39.403C22.2755 40.1017 21.0048 40.6705 19.6203 41.015C18.2642 41.3741 16.8694 41.5662 15.4667 41.587L0 41.6098V9.75H15.1255ZM14.1992 22.6785C15.4667 22.6785 16.5068 22.3372 17.3095 21.7522C18.109 21.1672 18.4633 20.137 18.4633 18.8695C18.4633 18.1675 18.3495 17.485 18.122 17.0267C17.8737 16.5638 17.5168 16.1679 17.082 15.873C16.6112 15.6145 16.1071 15.422 15.5838 15.301C15.0118 15.1873 14.4268 15.1872 13.741 15.1872H7.0525V22.6947C7.0525 22.6785 14.1992 22.6785 14.1992 22.6785ZM14.5405 36.3057C15.2392 36.3057 15.925 36.192 16.51 36.0782C17.082 35.9645 17.667 35.7338 18.122 35.3763C18.577 35.0188 18.9345 34.6775 19.279 34.1087C19.5065 33.5367 19.734 32.838 19.734 32.0255C19.734 30.4135 19.279 29.2565 18.3495 28.444C17.4232 27.7452 16.1525 27.404 14.6542 27.404H7.0525V36.2895H14.5437L14.5405 36.3057ZM36.829 36.192C37.7563 37.1172 39.1408 37.5787 40.9825 37.5765C42.25 37.5765 43.407 37.232 44.3365 36.647C45.2595 35.9537 45.8337 35.2603 46.059 34.567H51.714C50.7845 37.3327 49.4 39.3023 47.5605 40.573C45.721 41.7268 43.5208 42.4125 40.8655 42.4125C39.1673 42.4228 37.4829 42.1084 35.9027 41.4863C34.4783 40.9606 33.2079 40.0869 32.2075 38.9447C31.1376 37.8823 30.3433 36.5748 29.8935 35.1357C29.3247 33.6375 29.081 32.0255 29.081 30.1697C29.081 28.444 29.3085 26.8157 29.8935 25.3175C30.4785 23.8225 31.278 22.5485 32.3212 21.3948C33.3612 20.3548 34.632 19.4253 36.0165 18.8533C37.5598 18.2372 39.207 17.9228 40.8687 17.927C42.8382 17.927 44.564 18.2682 46.0623 19.0807C47.5573 19.8932 48.7175 20.8097 49.6437 22.1942C50.57 23.4617 51.2558 24.96 51.727 26.5753C51.9545 28.1873 52.0683 29.8122 51.9545 31.655H35.217C35.217 33.54 35.8995 35.2625 36.829 36.192ZM33.9462 11.947H46.8748V15.0572H33.9462V11.947Z" /></svg>
          </a>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="col-md-4 col-lg-4 text-center text-md-start">
        <h6 class="footer-title">Contact Info</h6>
        <ul class="list-unstyled footer-contact">
          <li><strong>Email:</strong> <a href="mailto:hi@aksanazachri.my.id" class="no-link">hi@aksanazachri.my.id</a></li>
          <li><strong>Based in:</strong> Jakarta, Indonesia</li>
        </ul>
      </div>

    </div>

    <!-- Copyright -->
    <div class="text-center mt-4 pt-3 border-top small">
      &copy; <span id="year"></span> Aksana Zachri Satria. All rights reserved.
    </div>
  </div>
</footer>
<!-- Footer End -->

<script>
  document.getElementById("year").textContent = new Date().getFullYear();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
