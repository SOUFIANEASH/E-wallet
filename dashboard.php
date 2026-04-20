<?php
require_once __DIR__ . '/config/layout.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Fetch user balance
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
$_SESSION['user']['name'] = $user['name'];

// Monthly totals
$month = date('Y-m');
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense,
        SUM(CASE WHEN type = 'transfer_out' THEN amount ELSE 0 END) AS sent,
        COUNT(*) AS tx_count
    FROM transactions
    WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
");
$stmt->execute([$uid, $month]);
$monthly = $stmt->fetch();

// Recent transactions (last 7)
$stmt = $pdo->prepare("
    SELECT t.*, c.name AS cat_name, c.color AS cat_color,
           u2.name AS related_name
    FROM transactions t
    LEFT JOIN categories c ON c.id = t.category_id
    LEFT JOIN users u2 ON u2.id = t.related_user_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 7
");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

pageHeader('Dashboard', 'dashboard');
?>
<div class="page">

  <!-- Balance card -->
  <div class="balance-card mb-6">
    <div style="font-size:12px;opacity:.7;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Total balance</div>
    <div style="font-size:40px;font-weight:600;letter-spacing:-1.5px;margin-bottom:22px;position:relative;z-index:1;">
      € <?= number_format($user['balance'], 2) ?>
    </div>
    <div style="display:flex;gap:10px;position:relative;z-index:1;">
      <a href="/ewallet/add_funds.php" class="btn btn-white">+ Add funds</a>
      <a href="/ewallet/transfer.php"  class="btn btn-white">↑ Send money</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid mb-6">
    <div class="stat-card">
      <div class="stat-label">Income this month</div>
      <div class="stat-value positive">+ € <?= number_format($monthly['income'] ?? 0, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Expenses this month</div>
      <div class="stat-value negative">− € <?= number_format($monthly['expense'] ?? 0, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Sent this month</div>
      <div class="stat-value" style="color:var(--gray-700)">↗ € <?= number_format($monthly['sent'] ?? 0, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Transactions</div>
      <div class="stat-value" style="color:var(--gray-700)"><?= (int)($monthly['tx_count'] ?? 0) ?></div>
    </div>
  </div>

  <!-- Recent transactions -->
  <div class="flex items-center justify-between mb-4">
    <h2 class="font-medium" style="font-size:15px;">Recent transactions</h2>
    <a href="/ewallet/transactions.php" class="btn btn-outline btn-sm">View all</a>
  </div>

  <div class="card">
    <?php if ($recent): ?>
      <div class="tx-list">
        <?php foreach ($recent as $tx):
          $isPos = in_array($tx['type'], ['income', 'transfer_in']);
          $sign  = $isPos ? '+' : '−';
          $cls   = $isPos ? 'positive' : 'negative';
        ?>
          <div class="tx-item">
            <div class="tx-icon <?= $tx['type'] ?>"><?= txIcon($tx['type']) ?></div>
            <div class="tx-meta">
              <div class="tx-name"><?= htmlspecialchars($tx['description'] ?: ucfirst(str_replace('_', ' ', $tx['type']))) ?></div>
              <div class="tx-sub">
                <?php if ($tx['cat_name']): ?>
                  <span class="badge badge-<?= str_starts_with($tx['type'],'transfer') ? 'transfer' : $tx['type'] ?>"><?= htmlspecialchars($tx['cat_name']) ?></span>
                <?php else: ?>
                  <span class="badge badge-<?= str_starts_with($tx['type'],'transfer') ? 'transfer' : $tx['type'] ?>"><?= ucfirst(str_replace('_',' ',$tx['type'])) ?></span>
                <?php endif; ?>
                <?php if ($tx['related_name']): ?>
                  · <?= htmlspecialchars($tx['related_name']) ?>
                <?php endif; ?>
              </div>
            </div>
            <div>
              <div class="tx-amount <?= $cls ?>"><?= $sign ?> € <?= number_format($tx['amount'], 2) ?></div>
              <div class="tx-date"><?= date('M d', strtotime($tx['created_at'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted text-sm" style="text-align:center;padding:24px 0;">No transactions yet. <a href="/ewallet/add_funds.php">Add your first one!</a></p>
    <?php endif; ?>
  </div>

</div>
<?php pageFooter(); ?>
