<?php
// Ambil parameter ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if ($id === null) {
    header('Location: certificates.php');
    exit;
}

// Baca file JSON
$file = 'certificates.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($data) || !isset($data[$id])) {
    header('Location: admin-certificates.php');
    exit;
}

// Toggle status publish
$data[$id]['published'] = !($data[$id]['published'] ?? false);

// Simpan ulang
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

// Redirect kembali
header('Location: admin-certificates.php');
exit;
