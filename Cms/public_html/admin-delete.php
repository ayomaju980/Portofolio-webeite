<?php
session_start();

if (isset($_GET['id'])) {
    $targetId = $_GET['id'];
    $data = json_decode(file_get_contents('data.json'), true);

    // Cari dan hapus data
    foreach ($data as $i => $item) {
        if ((string)$item['id'] === (string)$targetId) {
            array_splice($data, $i, 1);
            file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
            $_SESSION['flash'] = "Data berhasil dihapus.";
            break;
        }
    }
}

header("Location: admin.php");
exit;
