<?php
require_once __DIR__ . '/config/layout.php';
requireLogin();

$uid    = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get sender balance
$stmt = $pdo->prepare("SELECT balance, name FROM users WHERE id = ?");
$stmt->execute([$uid]);
$sender = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientEmail = trim($_POST['recipient_email'] ?? '');
    $amount         = floatval($_POST['amount'] ?? 0);
    $description    = trim($_POST['description'] ?? 'Transfer');

    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid recipient email.';
    if ($amount <= 0)                    $errors[] = 'Amount must be greater than 0.';
    if ($amount > $sender['balance'])    $errors[] = 'Insufficient balance.';

    if (!$errors) {
        // Find recipient
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$recipientEmail, $uid]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            $errors[] = 'No user found with that email address.';
        } else {
            try {
                $pdo->beginTransaction();

                // Deduct sender
                $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $uid]);
                // Credit recipient
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $recipient['id']]);

                // Log transfer_out
                $pdo->prepare("INSERT INTO transactions (user_id, type, amount, related_user_id, description) VALUES (?, 'transfer_out', ?, ?, ?)")
                    ->execute([$uid, $amount, $recipient['id'], $description ?: 'Transfer sent']);

                // Log transfer_in for recipient
                $pdo->prepare("INSERT INTO transactions (user_id, type, amount, related_user_id, description) VALUES (?, 'transfer_in', ?, ?, ?)")
                    ->execute([$recipient['id'], $amount, $uid, $description ?: 'Transfer received']);

                $pdo->commit();

                // Refresh balance
                $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->execute([$uid]);
                $sender['balance'] = $stmt->fetchColumn();

                flash("€ " . number_format($amount, 2) . " sent to " . $recipient['name'] . " successfully!", 'success');
                header('Location: /ewallet/transfer.php'); exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Transfer failed. Please try again.';
            }
        }
    }
}

pageHeader('Transfer money', 'transfer');
?>
<div class="page" style="max-width:520px;">

  <div class="card mb-6" style="background:var(--green-50);border-color:var(--green-200);">
    <div style="font-size:12px;color:var(--green-700);margin-bottom:4px;">Your current balance</div>
    <div style="font-size:28px;font-weight:600;color:var(--green-800);">€ <?= number_format($sender['balance'], 2) ?></div>
  </div>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="card">
    <h2 class="font-medium mb-4" style="font-size:15px;">Send money</h2>
    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Recipient email</label>
        <input type="email" name="recipient_email" class="form-control"
               value="<?= htmlspecialchars($_POST['recipient_email'] ?? '') ?>"
               placeholder="recipient@email.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Amount (€)</label>
        <input type="number" name="amount" class="form-control"
               value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
               placeholder="0.00" min="0.01" max="<?= $sender['balance'] ?>" step="0.01" required>
        <div class="text-sm text-muted mt-4">Max: € <?= number_format($sender['balance'], 2) ?></div>
      </div>
      <div class="form-group">
        <label class="form-label">Note (optional)</label>
        <input type="text" name="description" class="form-control"
               value="<?= htmlspecialchars($_POST['description'] ?? '') ?>"
               placeholder="What's this for?">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Send money ↗</button>
    </form>
  </div>

  <p class="text-sm text-muted mt-4">
    Transfers are instant and cannot be reversed. Make sure the email is correct before sending.
  </p>

</div>
<?php pageFooter(); ?>
