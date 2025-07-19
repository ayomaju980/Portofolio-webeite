<?php
if (isset($_POST['items1'])) {
  file_put_contents('marquee1.json', json_encode($_POST['items1'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
header("Location: admin-footer.php"); // arahkan kembali ke halaman editor
exit;
