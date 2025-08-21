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
    // Simpan data.json terbaru
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // === Tambahkan update version.json ===
    $versionFile = 'version.json';
    if (file_exists($versionFile)) {
        $versionData = json_decode(file_get_contents($versionFile), true);
        if (!is_array($versionData)) $versionData = ["version" => 0];
        $versionData['version'] = $versionData['version'] + 1;
    } else {
        $versionData = ["version" => 1];
    }
    file_put_contents($versionFile, json_encode($versionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    // ====================================

    $_SESSION['flash'] = "Status publish berhasil diperbarui.";
} else {
    $_SESSION['flash'] = "Data tidak ditemukan.";
}

header('Location: admin.php');
exit;
