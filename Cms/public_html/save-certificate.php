<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $_POST['title'];
  $period = $_POST['period'];
  $link = $_POST['link'];
  $published = isset($_POST['published']) ? true : false;

  $uploadDir = 'img/';
  $filename = basename($_FILES['image']['name']);
  $targetPath = $uploadDir . $filename;
  move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);

  $newCert = [
    'title' => $title,
    'period' => $period,
    'link' => $link,
    'image' => $targetPath,
    'published' => $published
  ];

  $file = 'certificates.json';
  $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
  $data[] = $newCert;
  file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

  header('Location: admin-certificates.php');
  exit;
}
?>