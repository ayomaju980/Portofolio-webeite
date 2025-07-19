<?php
if (isset($_GET['id'])) {
    $targetId = $_GET['id'];

    $data = json_decode(file_get_contents('data.json'), true);

    // Cari indeks berdasarkan ID
    $indexToDelete = null;
    foreach ($data as $i => $item) {
        if ((string)$item['id'] === (string)$targetId) {
            $indexToDelete = $i;
            break;
        }
    }

    // Jika ditemukan, hapus
    if ($indexToDelete !== null) {
        array_splice($data, $indexToDelete, 1);
        file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
    }
}

header("Location: admin.php");
exit;
?>
