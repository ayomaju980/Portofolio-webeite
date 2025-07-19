<?php
if (isset($_POST['items1'])) {
  file_put_contents('cms/marquee1.json', json_encode($_POST['items1'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
if (isset($_POST['items2'])) {
  file_put_contents('cms/marquee2.json', json_encode($_POST['items2'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Redirect balik ke admin editor
header("Location: admin-footer.php");
exit;
