<?php
require_once __DIR__ . '/config/layout.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Filters
$filterType = $_GET['type'] ?? '';
$filterCat  = intval($_GET['cat'] ?? 0);
$search     = trim($_GET['q'] ?? '');
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;

$where  = ['t.user_id = :uid'];
$params = [':uid' => $uid];

if ($filterType && in_array($filterType, ['income','expense','transfer_in','transfer_out'])) {
    $where[] = 't.type = :type';
    $params[':type'] = $filterType;
}
if ($filterCat) {
    $where[] = 't.category_id = :cat';
    $params[':cat'] = $filterCat;
}
if ($search) {
    $where[] = 't.description LIKE :q';
    $params[':q'] = '%' . $search . '%';
}

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t WHERE $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

// Fetch
$params[':limit']  = $perPage;
$params[':offset'] = $offset;
$stmt = $pdo->prepare("
    SELECT t.*, c.name AS cat_name, c.color AS cat_color, u2.name AS related_name
    FROM transactions t
    LEFT JOIN categories c ON c.id = t.category_id
    LEFT JOIN users u2 ON u2.id = t.related_user_id
    WHERE $whereSQL
    ORDER BY t.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$transactions = $stmt->fetchAll();

// Categories for filter
$cats = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
$cats->execute([$uid]);
$categories = $cats->fetchAll();

pageHeader('Transactions', 'transactions');
?>
<div class="page">

  <!-- Filters bar -->
  <div class="card mb-6" style="padding:14px 18px;">
    <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <label class="form-label">Type</label>
        <select name="type" class="form-control" style="width:150px;">
          <option value="">All types</option>
          <option value="income"       <?= $filterType === 'income'       ? 'selected' : '' ?>>Income</option>
          <option value="expense"      <?= $filterType === 'expense'      ? 'selected' : '' ?>>Expense</option>
          <option value="transfer_in"  <?= $filterType === 'transfer_in'  ? 'selected' : '' ?>>Transfer in</option>
          <option value="transfer_out" <?= $filterType === 'transfer_out' ? 'selected' : '' ?>>Transfer out</option>
        </select>
      </div>
      <div>
        <label class="form-label">Category</label>
        <select name="cat" class="form-control" style="width:160px;">
          <option value="">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filterCat === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex:1;min-width:160px;">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Search description…">
      </div>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="/ewallet/transactions.php" class="btn btn-outline">Reset</a>
    </form>
  </div>

  <!-- Results count -->
  <div class="flex items-center justify-between mb-4">
    <span class="text-sm text-muted"><?= $total ?> transaction<?= $total != 1 ? 's' : '' ?> found</span>
    <a href="/ewallet/add_funds.php" class="btn btn-primary btn-sm">+ Add</a>
  </div>

  <div class="card">
    <?php if ($transactions): ?>
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Category</th>
            <th>Type</th>
            <th style="text-align:right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx):
            $isPos = in_array($tx['type'], ['income','transfer_in']);
            $sign  = $isPos ? '+' : '−';
            $cls   = $isPos ? 'positive' : 'negative';
          ?>
            <tr>
              <td class="text-muted text-sm" style="white-space:nowrap;"><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:16px;"><?= txIcon($tx['type']) ?></span>
                  <span><?= htmlspecialchars($tx['description'] ?: ucfirst(str_replace('_',' ',$tx['type']))) ?></span>
                </div>
                <?php if ($tx['related_name']): ?>
                  <div class="text-sm text-muted"><?= $isPos ? 'From' : 'To' ?>: <?= htmlspecialchars($tx['related_name']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($tx['cat_name']): ?>
                  <span style="display:inline-flex;align-items:center;gap:5px;">
                    <span class="color-dot" style="background:<?= htmlspecialchars($tx['cat_color']) ?>"></span>
                    <?= htmlspecialchars($tx['cat_name']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= str_starts_with($tx['type'],'transfer') ? 'transfer' : $tx['type'] ?>">
                  <?= ucfirst(str_replace('_',' ',$tx['type'])) ?>
                </span>
              </td>
              <td style="text-align:right;" class="tx-amount <?= $cls ?> font-medium">
                <?= $sign ?> € <?= number_format($tx['amount'], 2) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display:flex;gap:6px;justify-content:center;padding-top:16px;border-top:1px solid var(--gray-100);margin-top:8px;">
          <?php for ($i = 1; $i <= $totalPages; $i++):
            $q = array_merge($_GET, ['page' => $i]);
          ?>
            <a href="?<?= http_build_query($q) ?>"
               class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <p class="text-muted text-sm" style="text-align:center;padding:32px 0;">
        No transactions found. <a href="/ewallet/add_funds.php">Add one</a>.
      </p>
    <?php endif; ?>
  </div>

</div>
<?php pageFooter(); ?>
