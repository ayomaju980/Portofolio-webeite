<?php
session_start();

// Koneksi ke database
$host = 'localhost';
$db   = 'ayomajum_user_login';
$user = 'ayomajum_user_login';
$pass = 'userlogin5436#';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil user dari database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Validasi password terenkripsi
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header("Location: Dashboard");
        exit;
    } else {
        $error = "Username atau password salah!";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - Portofolio CMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="img/Logoo.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #fa55cb, #43135e);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .login-container {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      backdrop-filter: blur(25px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 400px;
      padding: 40px 30px;
      color: #fff;
    }
    .logo {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    .logo img {
      width: 70px;
      height: 70px;
      object-fit: contain;
    }
    h2 {
      text-align: center;
      font-size: 24px;
      margin-bottom: 10px;
    }
    .subtext {
      text-align: center;
      font-size: 14px;
      margin-bottom: 25px;
      color: #ddd;
    }
    form {
      display: flex;
      flex-direction: column;
    }
    label {
      font-weight: 600;
      margin-bottom: 5px;
    }
    input[type="text"],
    input[type="password"] {
      padding: 10px 12px;
      font-size: 15px;
      border: none;
      border-radius: 8px;
      outline: none;
      background: rgba(255,255,255,0.08);
      color: white;
    }
    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 15px;
    }
    .password-wrapper input {
      flex: 1;
      padding: 10px 12px;
      border: none;
      outline: none;
      background: transparent;
      color: white;
      font-size: 15px;
    }
    .password-wrapper button {
      background: none;
      border: none;
      padding: 10px 12px;
      cursor: pointer;
      color: #ccc;
      transition: color 0.3s ease;
    }
    .password-wrapper button:hover {
      color: #FAAD1B;
    }
    button[type="submit"] {
      margin-top: 10px;
      padding: 12px;
      background-color: #FAAD1B;
      border: none;
      border-radius: 8px;
      color: white;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button[type="submit"]:hover {
      background-color: #e09c15;
    }
    .error {
      background: #ff6b6b;
      color: white;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      text-align: center;
      font-size: 14px;
    }
    @media (max-width: 500px) {
      .login-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <img src="img/ICON.png" alt="Logo CMS">
    </div>
    <h2><span style="color:#FAAD1B">Hai!</span> Selamat Datang</h2>
    <p class="subtext">Masuk ke akun Portofolio CMS kamu</p>

    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">
      <label for="username">Username</label>
      <input type="text" name="username" id="username" placeholder="Masukkan Username" required>

      <label for="password">Password</label>
      <div class="password-wrapper">
        <input type="password" name="password" id="passwordInput" placeholder="Masukkan Password" required>
        <button type="button" id="togglePassword" aria-label="Toggle Password">
          <i class="bi bi-eye-slash"></i>
        </button>
      </div>

      <button type="submit">Sign In</button>
    </form>
  </div>

  <script>
    // Nonaktifkan klik kanan
    document.addEventListener('contextmenu', function(e) {
      e.preventDefault();
    });

    // Toggle show/hide password
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("passwordInput");

    togglePassword.addEventListener("click", function () {
      const icon = this.querySelector("i");
      const type = passwordInput.type === "password" ? "text" : "password";
      passwordInput.type = type;
      icon.classList.toggle("bi-eye");
      icon.classList.toggle("bi-eye-slash");
    });
  </script>
</body>
</html>
