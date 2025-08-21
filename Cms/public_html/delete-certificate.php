<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$certificatesFile = 'certificates.json';
$certificates = file_exists($certificatesFile) ? json_decode(file_get_contents($certificatesFile), true) : [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;

if (isset($certificates[$id])) {
    unset($certificates[$id]);
    $certificates = array_values($certificates); // Reset indeks array agar tetap rapi
    file_put_contents($certificatesFile, json_encode($certificates, JSON_PRETTY_PRINT));
    header('Location: admin-certificates.php?success=deleted');
    exit;
} else {
    header('Location: admin-certificates.php?error=notfound');
    exit;
}
?>
