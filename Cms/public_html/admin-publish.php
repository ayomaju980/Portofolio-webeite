<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login-user');
    exit;
}

$dataFile = 'data.json';
if (!file_exists($dataFile)) {
    die('File data tidak ditemukan.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents($dataFile), true);
$updated = false;

foreach ($data as &$item) {
    if ((int)$item['id'] === $id) {
        $item['published'] = !($item['published'] ?? false); // toggle status
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $_SESSION['flash'] = "Status publish berhasil diperbarui.";
} else {
    $_SESSION['flash'] = "Data tidak ditemukan.";
}

header('Location: admin.php');
exit;
