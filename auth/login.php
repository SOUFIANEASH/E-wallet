<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    header('Location: /ewallet/dashboard.php'); exit;
}

$error = '';
$email = '';

// Show flash from register
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            header('Location: /ewallet/dashboard.php'); exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – PayVault</title>
  <link rel="stylesheet" href="/ewallet/assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">pay<span>vault</span></div>
    <div class="auth-sub">Sign in to your wallet</div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($email) ?>"
               placeholder="joao@email.com" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="Your password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>
    <p class="text-sm text-muted mt-4" style="text-align:center;">
      No account? <a href="/ewallet/auth/register.php">Create one free</a>
    </p>
  </div>
</div>
</body>
</html>
