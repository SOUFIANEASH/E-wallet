<?php
// config/layout.php — shared layout helpers
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function pageHeader(string $title, string $active = '') {
    $user = currentUser();
    $initials = '';
    if ($user) {
        $parts = explode(' ', trim($user['name']));
        $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }
    $links = [
        ['href' => '/ewallet/dashboard.php',    'label' => 'Dashboard',    'icon' => 'dashboard', 'key' => 'dashboard'],
        ['href' => '/ewallet/transfer.php',      'label' => 'Transfer',     'icon' => 'transfer',  'key' => 'transfer'],
        ['href' => '/ewallet/transactions.php',  'label' => 'Transactions', 'icon' => 'list',      'key' => 'transactions'],
        ['href' => '/ewallet/add_funds.php',     'label' => 'Add funds',    'icon' => 'plus',      'key' => 'add_funds'],
        ['href' => '/ewallet/categories.php',    'label' => 'Categories',   'icon' => 'tag',       'key' => 'categories'],
    ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> – PayVault</title>
  <link rel="stylesheet" href="/ewallet/assets/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-logo">pay<span>vault</span></div>
    <?php foreach ($links as $l): ?>
      <a href="<?= $l['href'] ?>" class="nav-link <?= $active === $l['key'] ? 'active' : '' ?>">
        <?= navIcon($l['icon']) ?>
        <?= htmlspecialchars($l['label']) ?>
      </a>
    <?php endforeach; ?>
    <div class="nav-spacer"></div>
    <a href="/ewallet/auth/logout.php" class="nav-link" style="color:#993C1D;">
      <?= navIcon('logout') ?> Logout
    </a>
  </aside>
  <div class="main-content">
    <div class="topbar">
      <span class="topbar-title"><?= htmlspecialchars($title) ?></span>
      <div class="topbar-user">
        <span><?= htmlspecialchars($user['name'] ?? '') ?></span>
        <div class="avatar-sm"><?= $initials ?></div>
      </div>
    </div>
    <?php
    $flash = getFlash();
    if ($flash): ?>
    <div class="page" style="padding-bottom:0;">
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    </div>
    <?php endif;
}

function pageFooter() { ?>
  </div><!-- main-content -->
</div><!-- app-layout -->
</body>
</html>
<?php }

function navIcon(string $name): string {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'transfer'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>',
        'list'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
        'plus'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
        'tag'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        'logout'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function txIcon(string $type): string {
    return match($type) {
        'income'       => '💼',
        'expense'      => '🛒',
        'transfer_in'  => '↙',
        'transfer_out' => '↗',
        default        => '•'
    };
}

function formatMoney(float $amount, string $prefix = ''): string {
    return $prefix . '€ ' . number_format(abs($amount), 2);
}
