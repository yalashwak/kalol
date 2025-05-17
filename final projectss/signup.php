<?php
require('database.php');
$message = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $send_username = $_POST['name'] ?? '';
  $first_password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if (!empty($send_username) && !empty($first_password) && $first_password === $confirm_password) {
      $hashed_password = password_hash($first_password, PASSWORD_DEFAULT);
      $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
      mysqli_stmt_bind_param($stmt, "ss", $send_username, $hashed_password);
      if (mysqli_stmt_execute($stmt)) {
          header("Location: login.php");
          exit;
      } else {
          $message = "Username already exists or error occurred.";
      }
      mysqli_stmt_close($stmt);
  } else {
    $message = "Please fill all fields and make sure passwords match.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nord Theme Login Page</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="color.css">
</head>
<body>
  <div class="signup-container">
    <form action="" method="post" id="signupForm" enctype="multipart/form-data">
      <h2>Sign Up</h2>
      <?php if (!empty($message)): ?>
        <div class="error" style="display:block;"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>
      <div class="input-group">
        <input type="text" name="name" id="username" placeholder=" " required>
        <label for="username">Username</label>
      </div>
      <div class="input-group">
        <input type="password" name="password" id="password" placeholder=" " required>
        <label for="password">Password</label>
      </div>
      <div class="input-group">
        <input type="password" name="confirm_password" id="confirm_password" placeholder=" " required>
        <label for="confirm_password">Confirm Password</label>
      </div>
      <input type="submit" value="Sign Up">
      <div class="login-link">
        <p>Already have an account? </p><a href="login.php">Login</a>
      </div>
    </form>
  </div>
  <script src="script.js"></script>
</body>
</html>