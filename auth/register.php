<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    header('Location: /ewallet/dashboard.php'); exit;
}

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name)                        $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6)         $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)        $errors[] = 'Passwords do not match.';

    if (!$errors) {
        // Check duplicate
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, balance) VALUES (?, ?, ?, 0.00)");
            $stmt->execute([$name, $email, $hash]);
            $userId = $pdo->lastInsertId();

            // Insert default categories
            $defaults = [
                ['Salary',       'income',  '#1D9E75'],
                ['Freelance',    'income',  '#0F6E56'],
                ['Other income', 'income',  '#9FE1CB'],
                ['Food',         'expense', '#D85A30'],
                ['Housing',      'expense', '#993C1D'],
                ['Transport',    'expense', '#BA7517'],
                ['Health',       'expense', '#185FA5'],
                ['Entertainment','expense', '#534AB7'],
                ['Shopping',     'expense', '#D4537E'],
            ];
            $catStmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
            foreach ($defaults as [$n, $t, $c]) {
                $catStmt->execute([$userId, $n, $t, $c]);
            }

            flash('Account created! Please log in.', 'success');
            header('Location: /ewallet/auth/login.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register – PayVault</title>
  <link rel="stylesheet" href="/ewallet/assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">pay<span>vault</span></div>
    <div class="auth-sub">Create your free account</div>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Full name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" placeholder="João Silva" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="joao@email.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm password</label>
        <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>
    <p class="text-sm text-muted mt-4" style="text-align:center;">
      Already have an account? <a href="/ewallet/auth/login.php">Sign in</a>
    </p>
  </div>
</div>
</body>
</html>
