<?php
require_once __DIR__ . '/config/layout.php';
requireLogin();

$uid    = $_SESSION['user_id'];
$errors = [];
$type   = $_GET['type'] ?? 'income'; // income | expense
if (!in_array($type, ['income','expense'])) $type = 'income';

// Fetch categories for this type
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND type = ? ORDER BY name");
$stmt->execute([$uid, $type]);
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postType   = $_POST['type'] ?? 'income';
    $amount     = floatval($_POST['amount'] ?? 0);
    $catId      = intval($_POST['category_id'] ?? 0) ?: null;
    $desc       = trim($_POST['description'] ?? '');

    if (!in_array($postType, ['income','expense'])) $errors[] = 'Invalid type.';
    if ($amount <= 0) $errors[] = 'Amount must be greater than 0.';

    if (!$errors) {
        // Validate category belongs to user
        if ($catId) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$catId, $uid]);
            if (!$stmt->fetch()) $catId = null;
        }

        // Insert transaction
        $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category_id, description) VALUES (?, ?, ?, ?, ?)")
            ->execute([$uid, $postType, $amount, $catId, $desc]);

        // Update balance
        if ($postType === 'income') {
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $uid]);
        } else {
            $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $uid]);
        }

        flash("€ " . number_format($amount,2) . " " . $postType . " recorded!", 'success');
        header('Location: /ewallet/add_funds.php?type=' . $postType); exit;
    }
    $type = $postType; // keep tab
}

pageHeader('Add funds', 'add_funds');
?>
<div class="page" style="max-width:520px;">

  <!-- Tabs -->
  <div style="display:flex;gap:0;background:var(--gray-100);border-radius:8px;padding:3px;width:fit-content;margin-bottom:24px;">
    <a href="?type=income"  class="btn <?= $type === 'income'  ? 'btn-primary' : 'btn-outline' ?>" style="border-radius:6px;border:none;">+ Income</a>
    <a href="?type=expense" class="btn <?= $type === 'expense' ? 'btn-danger'  : 'btn-outline' ?>" style="border-radius:6px;border:none;margin-left:4px;">− Expense</a>
  </div>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="card">
    <h2 class="font-medium mb-4" style="font-size:15px;">
      <?= $type === 'income' ? 'Record income' : 'Record expense' ?>
    </h2>
    <form method="POST" action="">
      <input type="hidden" name="type" value="<?= $type ?>">

      <div class="form-group">
        <label class="form-label">Amount (€)</label>
        <input type="number" name="amount" class="form-control"
               value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
               placeholder="0.00" min="0.01" step="0.01" required>
      </div>

      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-control">
          <option value="">— No category —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"
              <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!$categories): ?>
          <div class="text-sm text-muted mt-4">
            No <?= $type ?> categories yet. <a href="/ewallet/categories.php">Add one</a>.
          </div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Description (optional)</label>
        <input type="text" name="description" class="form-control"
               value="<?= htmlspecialchars($_POST['description'] ?? '') ?>"
               placeholder="e.g. Grocery shopping">
      </div>

      <button type="submit" class="btn <?= $type === 'income' ? 'btn-primary' : 'btn-danger' ?> btn-block">
        <?= $type === 'income' ? 'Add income' : 'Add expense' ?>
      </button>
    </form>
  </div>

</div>
<?php pageFooter(); ?>
