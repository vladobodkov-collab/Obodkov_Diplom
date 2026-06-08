<?php
require_once __DIR__ . '/../config.php';
requireEmployee();
$pdo = getDB();

$stats = [
    'orders_new'    => $pdo->query('SELECT COUNT(*) FROM orders WHERE status="new"')->fetchColumn(),
    'orders_today'  => $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
    'revenue_month' => $pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status!="cancelled"')->fetchColumn(),
    'products_low'  => $pdo->query('SELECT COUNT(*) FROM products WHERE stock_qty<=5 AND is_active=1')->fetchColumn(),
    'clients_total' => $pdo->query('SELECT COUNT(*) FROM users WHERE role_id=2')->fetchColumn(),
    'orders_total'  => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
];

$recentOrders = $pdo->query('
    SELECT o.id, o.status, o.total_amount, o.created_at,
           CONCAT(u.last_name," ",u.first_name) AS client_name
    FROM orders o JOIN users u ON u.id=o.user_id
    ORDER BY o.created_at DESC LIMIT 6
')->fetchAll();

$lowStock = $pdo->query('
    SELECT id, article, name, stock_qty FROM products
    WHERE stock_qty<=5 AND is_active=1 ORDER BY stock_qty ASC LIMIT 6
')->fetchAll();

$statusColors = ['new'=>'#e67e22','accepted'=>'#2980b9','assembling'=>'#8e44ad','shipped'=>'#16a085','delivered'=>'#27ae60','cancelled'=>'#c0392b'];
$statusLabels = ['new'=>'Новый','accepted'=>'Принят','assembling'=>'Комплектуется','shipped'=>'Отгружен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Дашборд — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <h1 class="admin-title">Панель управления</h1>
    <p style="color:var(--muted);margin-bottom:20px;font-size:14px">
        Добро пожаловать, <?= e($_SESSION['user']['first_name']) ?>! · <?= date('d.m.Y') ?>
    </p>

    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="stat-card"><span class="si">🆕</span><div class="sv"><?= $stats['orders_new'] ?></div><div class="sl">Новых заказов</div></div>
        <div class="stat-card"><span class="si">📅</span><div class="sv"><?= $stats['orders_today'] ?></div><div class="sl">Заказов сегодня</div></div>
        <div class="stat-card"><span class="si">💰</span><div class="sv"><?= number_format($stats['revenue_month'],0,'.',' ') ?></div><div class="sl">Выручка за месяц (₽)</div></div>
        <div class="stat-card"><span class="si">⚠️</span><div class="sv" style="color:<?= $stats['products_low']>0?'var(--danger)':'var(--success)' ?>"><?= $stats['products_low'] ?></div><div class="sl">Заканчивается на складе</div></div>
        <div class="stat-card"><span class="si">👥</span><div class="sv"><?= $stats['clients_total'] ?></div><div class="sl">Клиентов</div></div>
        <div class="stat-card"><span class="si">📦</span><div class="sv"><?= $stats['orders_total'] ?></div><div class="sl">Всего заказов</div></div>
    </div>

    <div class="two-col">
        <div>
            <div class="admin-section-title">Последние заказы</div>
            <table class="admin-table">
                <thead><tr><th>#</th><th>Клиент</th><th>Статус</th><th>Сумма</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>admin/order_view.php?id=<?= $o['id'] ?>" style="color:var(--orange);font-weight:600">#<?= $o['id'] ?></a></td>
                    <td><?= e($o['client_name']) ?></td>
                    <td><span class="badge" style="background:<?= $statusColors[$o['status']]??'#666' ?>22;color:<?= $statusColors[$o['status']]??'#666' ?>"><?= $statusLabels[$o['status']]??$o['status'] ?></span></td>
                    <td style="font-weight:600"><?= fmtPrice($o['total_amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <a href="<?= BASE_URL ?>admin/orders.php" style="display:block;text-align:center;margin-top:8px;color:var(--orange);font-size:13px;font-weight:600">Все заказы →</a>
        </div>
        <div>
            <div class="admin-section-title">⚠ Мало на складе</div>
            <table class="admin-table">
                <thead><tr><th>Артикул</th><th>Название</th><th>Остаток</th></tr></thead>
                <tbody>
                <?php foreach ($lowStock as $p): ?>
                <tr>
                    <td style="font-family:monospace;font-size:11px"><?= e($p['article']) ?></td>
                    <td style="font-size:13px"><?= e(mb_substr($p['name'],0,28)) ?>...</td>
                    <td><span class="badge" style="background:<?= $p['stock_qty']===0?'#e74c3c22':'#e67e2222' ?>;color:<?= $p['stock_qty']===0?'var(--danger)':'#e67e22' ?>"><?= $p['stock_qty'] ?> шт.</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <a href="<?= BASE_URL ?>admin/products.php" style="display:block;text-align:center;margin-top:8px;color:var(--orange);font-size:13px;font-weight:600">Все товары →</a>
        </div>
    </div>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
