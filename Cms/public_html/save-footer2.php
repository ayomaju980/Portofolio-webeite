<?php
if (isset($_POST['items2'])) {
  file_put_contents('marquee2.json', json_encode($_POST['items2'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
header("Location: admin-footer.php"); // arahkan kembali ke halaman editor
exit;
