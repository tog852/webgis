<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Login & Registration Form</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <input type="checkbox" id="check">
    
    <!-- Login Form -->
    <div class="login form">
      <header>Login</header>
      <?php
      session_start();
      if (isset($_SESSION['login_error'])) {
          echo '<div class="error" style="color: red; margin-bottom: 10px;">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
          unset($_SESSION['login_error']);
      }
      if (isset($_SESSION['register_success'])) {
          echo '<div class="success" style="color: green; margin-bottom: 10px;">' . htmlspecialchars($_SESSION['register_success']) . '</div>';
          unset($_SESSION['register_success']);
      }
      if (isset($_SESSION['login_info'])) {
          echo '<div class="info" style="color: blue; margin-bottom: 10px;">' . htmlspecialchars($_SESSION['login_info']) . '</div>';
          unset($_SESSION['login_info']);
      }
      ?>
      <form action="process_login.php" method="POST">
        <input type="text" name="username" placeholder="Enter your username or email" required>
        <input type="password" name="password" placeholder="Enter your password" required>
        <a href="#">Forgot password?</a>
        <input type="submit" class="button" value="Login">
      </form>
      <div class="signup">
        <span class="signup">Don't have an account? 
          <label for="check">Signup</label>
        </span>
      </div>
    </div>
    
    <!-- Registration Form -->
    <div class="registration form">
      <header>Signup</header>
      <form action="process_register.php" method="POST">
        <input type="text" name="username" placeholder="Enter your username" required>
        <input type="text" name="email" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Create a password" required>
        <input type="password" name="confirm_password" placeholder="Confirm your password" required>
        <input type="submit" class="button" value="Signup">
      </form>
      <div class="signup">
        <span class="signup">Already have an account? 
          <label for="check">Login</label>
        </span>
      </div>
    </div>
  </div>
</body>
</html>
