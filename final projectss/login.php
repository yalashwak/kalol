<?php
require('database.php');
$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $get_username = $_POST['username'] ?? '';
    $get_password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $get_username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $hashed_password);
    if (mysqli_stmt_fetch($stmt) && password_verify($get_password, $hashed_password)) {
        session_start();
        $_SESSION['username'] = $get_username;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>login page</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="color.css">
</head>
<body>
  <div class="login-container">
    <form action="" method="post" id="loginForm">
      <h2>Login</h2>
      <?php if (!empty($error)): ?>
        <div class="error" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <div class="input-group">
        <input type="text" name="username" placeholder=" " required>
        <label for="username">Username</label>
      </div>
      <div class="input-group">
        <input type="password" name="password" placeholder=" " required>
        <label for="password">Password</label>
      </div>
      <input type="submit" value="Login">
      <div class="signup-link">
        <p>Don't have an account? </p><a href="signup.php">Sign Up</a>
      </div>
    </form>
  </div>
  <script src="script.js"></script>
</body>
</html>