<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}
require 'db.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM users WHERE id=$id");
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $newPassword = $_POST['password'];

    if (!empty($newPassword)) {
        // Ubah password jika diisi
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $username, $hashedPassword, $role, $id);
    } else {
        // Jangan ubah password
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $full_name, $username, $role, $id);
    }

    $stmt->execute();
    header("Location: manage-users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="img/Logoo.png" type="image/png">
</head>
<body class="container py-5">
  <h2>Edit User</h2>
  <form method="POST">
    <div class="mb-3">
      <label>Nama Lengkap</label>
      <input name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Username</label>
      <input name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Password Baru <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
      <input name="password" type="password" class="form-control" placeholder="Masukkan password baru jika ingin reset">
    </div>
    <div class="mb-3">
      <label>Role</label>
      <select name="role" class="form-select">
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
        <option value="member" <?= $user['role'] === 'member' ? 'selected' : '' ?>>Member</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="manage-users.php" class="btn btn-secondary">Kembali</a>
  </form>
</body>
<script>
    document.addEventListener('contextmenu', function(e) {
      e.preventDefault();
    });
  </script>
</html>
