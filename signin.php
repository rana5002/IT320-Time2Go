<?php
session_start();
require_once 'db.php';

// If already logged in, send them to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$loginError    = '';
$registerError = '';
$activeTab     = 'login'; // which tab to show by default

// ─── Handle LOGIN ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginError = 'Email and password are required';
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, email, password FROM User WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            header("Location: index.php");
            exit;
        } else {
            $loginError = 'Wrong email or password';
        }
    }
}

// ─── Handle REGISTER ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $activeTab = 'register';
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $registerError = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Invalid email';
    } elseif (strlen($password) < 8) {
        $registerError = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm) {
        $registerError = "Passwords don't match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM User WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $registerError = 'Email is already registered';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO User (name, email, password, notifications_enabled) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $name, $email, $hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['name']    = $name;
                $_SESSION['email']   = $email;
                header("Location: index.php");
                exit;
            } else {
                $registerError = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — Time2Go</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .tab-bar {
      display: flex;
      gap: 4px;
      background: var(--gray-100);
      border-radius: var(--radius-md);
      padding: 4px;
      margin-bottom: 28px;
    }
    .tab-btn {
      flex: 1;
      padding: 10px;
      border: none;
      background: transparent;
      border-radius: var(--radius-sm);
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      font-weight: 600;
      color: var(--gray-400);
      cursor: pointer;
      transition: var(--transition);
    }
    .tab-btn.active {
      background: var(--white);
      color: var(--gray-800);
      box-shadow: var(--shadow-sm);
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-box">

      <div class="auth-logo">
        <img src="logoF.png" alt="Time2Go" />
        <span>Time2Go</span>
      </div>

      <div class="tab-bar">
        <button class="tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" id="tabLogin">Log in</button>
        <button class="tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" id="tabRegister">Create account</button>
      </div>

      <!-- Login -->
      <div id="loginForm" style="<?= $activeTab === 'login' ? '' : 'display:none;' ?>">
        <?php if ($loginError): ?>
          <div class="alert alert-error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST" action="signin.php" novalidate>
          <input type="hidden" name="action" value="login" />
          <div class="form-group">
            <label for="loginEmail">Email</label>
            <input class="form-input" type="email" name="email" id="loginEmail" placeholder="you@example.com" autocomplete="email" required />
          </div>
          <div class="form-group">
            <label for="loginPassword">Password</label>
            <input class="form-input" type="password" name="password" id="loginPassword" placeholder="Your password" autocomplete="current-password" required />
          </div>
          <button type="submit" class="btn btn-primary btn-full">Log in</button>
        </form>
      </div>

      <!-- Register -->
      <div id="registerForm" style="<?= $activeTab === 'register' ? '' : 'display:none;' ?>">
        <?php if ($registerError): ?>
          <div class="alert alert-error"><?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>
        <form method="POST" action="signin.php" novalidate>
          <input type="hidden" name="action" value="register" />
          <div class="form-group">
            <label for="regName">Full name</label>
            <input class="form-input" type="text" name="name" id="regName" placeholder="Your name" autocomplete="name" required />
          </div>
          <div class="form-group">
            <label for="regEmail">Email</label>
            <input class="form-input" type="email" name="email" id="regEmail" placeholder="you@example.com" autocomplete="email" required />
          </div>
          <div class="form-group">
            <label for="regPassword">Password</label>
            <input class="form-input" type="password" name="password" id="regPassword" placeholder="At least 8 characters" autocomplete="new-password" required />
          </div>
          <div class="form-group">
            <label for="regConfirm">Confirm password</label>
            <input class="form-input" type="password" name="confirm" id="regConfirm" placeholder="Same password again" autocomplete="new-password" required />
          </div>
          <button type="submit" class="btn btn-primary btn-full">Create account</button>
        </form>
      </div>

    </div>
  </div>

  <script>
    // Tab switching (pure JS, no fetch needed)
    const tabLogin     = document.getElementById('tabLogin');
    const tabRegister  = document.getElementById('tabRegister');
    const loginForm    = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    tabLogin.addEventListener('click', () => {
      tabLogin.classList.add('active');
      tabRegister.classList.remove('active');
      loginForm.style.display = '';
      registerForm.style.display = 'none';
    });

    tabRegister.addEventListener('click', () => {
      tabRegister.classList.add('active');
      tabLogin.classList.remove('active');
      registerForm.style.display = '';
      loginForm.style.display = 'none';
    });
  </script>
</body>
</html>