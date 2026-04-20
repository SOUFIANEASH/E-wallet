<?php
require_once __DIR__ . '/config/layout.php';
requireLogin();

$uid    = $_SESSION['user_id'];
$errors = [];

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['name'] ?? '');
    $type  = $_POST['type'] ?? '';
    $color = $_POST['color'] ?? '#1D9E75';

    if (!$name)                          $errors[] = 'Category name is required.';
    if (!in_array($type, ['income','expense'])) $errors[] = 'Invalid type.';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#1D9E75';

    if (!$errors) {
        $pdo->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)")
            ->execute([$uid, $name, $type, $color]);
        flash('Category added!', 'success');
        header('Location: /ewallet/categories.php'); exit;
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
    flash('Category deleted.', 'success');
    header('Location: /ewallet/categories.php'); exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = intval($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $color = $_POST['color'] ?? '#1D9E75';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#1D9E75';
    if ($name && $id) {
        $pdo->prepare("UPDATE categories SET name = ?, color = ? WHERE id = ? AND user_id = ?")
            ->execute([$name, $color, $id, $uid]);
        flash('Category updated!', 'success');
        header('Location: /ewallet/categories.php'); exit;
    }
}

// Fetch all categories with usage count
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(t.id) AS tx_count
    FROM categories c
    LEFT JOIN transactions t ON t.category_id = c.id
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.type, c.name
");
$stmt->execute([$uid]);
$categories = $stmt->fetchAll();

$income  = array_filter($categories, fn($c) => $c['type'] === 'income');
$expense = array_filter($categories, fn($c) => $c['type'] === 'expense');

pageHeader('Categories', 'categories');
?>
<div class="page">

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="grid-2" style="gap:24px;align-items:start;">

    <!-- Add category form -->
    <div class="card">
      <h2 class="font-medium mb-4" style="font-size:15px;">New category</h2>
      <form method="POST" action="">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="e.g. Groceries" required>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="type" class="form-control" required>
            <option value="income"  <?= ($_POST['type'] ?? '') === 'income'  ? 'selected' : '' ?>>Income</option>
            <option value="expense" <?= ($_POST['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Color</label>
          <input type="color" name="color" value="<?= htmlspecialchars($_POST['color'] ?? '#1D9E75') ?>"
                 style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:6px;padding:2px;cursor:pointer;">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Add category</button>
      </form>
    </div>

    <!-- Categories list -->
    <div>
      <div class="card mb-4">
        <h3 class="font-medium mb-4" style="font-size:14px;color:var(--green-700);">Income categories</h3>
        <?php if ($income): ?>
          <div class="tx-list">
            <?php foreach ($income as $cat): ?>
              <div class="tx-item" style="padding:10px 0;">
                <span class="color-dot" style="background:<?= htmlspecialchars($cat['color']) ?>;width:12px;height:12px;flex-shrink:0;"></span>
                <div class="tx-meta">
                  <div class="tx-name" style="font-size:13.5px;"><?= htmlspecialchars($cat['name']) ?></div>
                  <div class="tx-sub"><?= (int)$cat['tx_count'] ?> transaction<?= $cat['tx_count'] != 1 ? 's' : '' ?></div>
                </div>
                <div style="display:flex;gap:6px;">
                  <!-- Inline edit trigger -->
                  <button class="btn btn-outline btn-sm" onclick="toggleEdit(<?= $cat['id'] ?>)">Edit</button>
                  <form method="POST" action="" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="border:1px solid #f5c4b3;color:var(--red-600);">Del</button>
                  </form>
                </div>
              </div>
              <!-- Edit inline form -->
              <div id="edit-<?= $cat['id'] ?>" style="display:none;background:var(--gray-50);border-radius:8px;padding:12px;margin-bottom:6px;">
                <form method="POST" action="" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" style="flex:1;min-width:120px;">
                  <input type="color" name="color" value="<?= htmlspecialchars($cat['color']) ?>" style="width:38px;height:38px;border:1px solid var(--gray-200);border-radius:6px;padding:2px;cursor:pointer;">
                  <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-muted">No income categories yet.</p>
        <?php endif; ?>
      </div>

      <div class="card">
        <h3 class="font-medium mb-4" style="font-size:14px;color:var(--red-600);">Expense categories</h3>
        <?php if ($expense): ?>
          <div class="tx-list">
            <?php foreach ($expense as $cat): ?>
              <div class="tx-item" style="padding:10px 0;">
                <span class="color-dot" style="background:<?= htmlspecialchars($cat['color']) ?>;width:12px;height:12px;flex-shrink:0;"></span>
                <div class="tx-meta">
                  <div class="tx-name" style="font-size:13.5px;"><?= htmlspecialchars($cat['name']) ?></div>
                  <div class="tx-sub"><?= (int)$cat['tx_count'] ?> transaction<?= $cat['tx_count'] != 1 ? 's' : '' ?></div>
                </div>
                <div style="display:flex;gap:6px;">
                  <button class="btn btn-outline btn-sm" onclick="toggleEdit(<?= $cat['id'] ?>)">Edit</button>
                  <form method="POST" action="" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="border:1px solid #f5c4b3;color:var(--red-600);">Del</button>
                  </form>
                </div>
              </div>
              <div id="edit-<?= $cat['id'] ?>" style="display:none;background:var(--gray-50);border-radius:8px;padding:12px;margin-bottom:6px;">
                <form method="POST" action="" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" style="flex:1;min-width:120px;">
                  <input type="color" name="color" value="<?= htmlspecialchars($cat['color']) ?>" style="width:38px;height:38px;border:1px solid var(--gray-200);border-radius:6px;padding:2px;cursor:pointer;">
                  <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-muted">No expense categories yet.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
function toggleEdit(id) {
  const el = document.getElementById('edit-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php pageFooter(); ?>
