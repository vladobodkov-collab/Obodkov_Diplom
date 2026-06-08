<?php
require_once __DIR__ . '/../config.php';
requireEmployee();
$pdo  = getDB();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$salesByProduct = $pdo->prepare('
    SELECT p.article, p.name, c.name AS cat,
           SUM(oi.qty) AS total_qty, SUM(oi.qty*oi.price) AS total_sum
    FROM order_items oi
    JOIN orders o ON o.id=oi.order_id
    JOIN products p ON p.id=oi.product_id
    JOIN categories c ON c.id=p.category_id
    WHERE o.status NOT IN ("cancelled") AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id ORDER BY total_sum DESC
');
$salesByProduct->execute([$from,$to]);
$salesByProduct = $salesByProduct->fetchAll();

$ordersByStatus = $pdo->prepare('
    SELECT status, COUNT(*) AS cnt, SUM(total_amount) AS total
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
');
$ordersByStatus->execute([$from,$to]);
$ordersByStatus = $ordersByStatus->fetchAll();

$arrivalsSum = $pdo->prepare('SELECT COUNT(*) AS cnt, SUM(qty) AS tq, SUM(qty*price_per_unit) AS tc FROM stock_arrivals WHERE DATE(arrived_at) BETWEEN ? AND ?');
$arrivalsSum->execute([$from,$to]);
$arrivalsSum = $arrivalsSum->fetch();

// Блок «Работа сотрудников»
$employeeStats = $pdo->prepare('
    SELECT u.id, CONCAT(u.last_name," ",u.first_name) AS emp_name,
           COUNT(o.id) AS orders_handled,
           SUM(o.total_amount) AS total_amount
    FROM orders o
    JOIN users u ON u.id=o.employee_id
    WHERE o.employee_id IS NOT NULL AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY o.employee_id
    ORDER BY orders_handled DESC
');
$employeeStats->execute([$from,$to]);
$employeeStats = $employeeStats->fetchAll();

$totalRevenue = array_sum(array_map(fn($r)=>$r['status']!=='cancelled'?$r['total']:0, $ordersByStatus));
$totalOrders  = array_sum(array_column($ordersByStatus,'cnt'));
$statusLabels = ['new'=>'Новый','accepted'=>'Принят','assembling'=>'Комплектуется','shipped'=>'Отгружен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Отчёты — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <h1 class="admin-title">Отчёты и статистика</h1>

    <!-- Период -->
    <form method="GET" style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:14px 18px;margin-bottom:22px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0"><label>С</label><input type="date" name="from" value="<?= e($from) ?>" class="form-input" style="font-size:13px;padding:7px 10px"></div>
        <div class="form-group" style="margin:0"><label>По</label><input type="date" name="to"   value="<?= e($to) ?>"   class="form-input" style="font-size:13px;padding:7px 10px"></div>
        <button type="submit" class="btn-primary" style="padding:9px 22px">Применить</button>
    </form>

    <!-- Итоги -->
    <div class="stat-grid" style="margin-bottom:22px">
        <div class="stat-card"><div class="sv"><?= fmtPrice($totalRevenue) ?></div><div class="sl">Выручка за период</div></div>
        <div class="stat-card"><div class="sv"><?= $totalOrders ?></div><div class="sl">Всего заказов</div></div>
        <div class="stat-card"><div class="sv"><?= $arrivalsSum['cnt'] ?></div><div class="sl">Поступлений на склад</div></div>
    </div>

    <div class="two-col">
        <!-- Заказы по статусам -->
        <div class="section-card">
            <h3>Заказы по статусам</h3>
            <table class="admin-table">
                <thead><tr><th>Статус</th><th>Кол-во</th><th>Сумма</th></tr></thead>
                <tbody>
                <?php foreach ($ordersByStatus as $r): ?>
                <tr>
                    <td><?= $statusLabels[$r['status']]??$r['status'] ?></td>
                    <td style="font-weight:700"><?= $r['cnt'] ?></td>
                    <td><?= fmtPrice($r['total']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Работа сотрудников -->
        <div class="section-card">
            <h3>Работа сотрудников</h3>
            <?php if (empty($employeeStats)): ?>
            <p style="color:var(--muted);font-size:13px">За период нет обработанных заказов</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Сотрудник</th><th>Обработано заказов</th><th>Сумма</th></tr></thead>
                <tbody>
                <?php foreach ($employeeStats as $es): ?>
                <tr>
                    <td style="font-weight:600"><?= e($es['emp_name']) ?></td>
                    <td style="text-align:center;font-weight:700;color:var(--navy)"><?= $es['orders_handled'] ?></td>
                    <td><?= fmtPrice($es['total_amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Топ продаж -->
    <div class="section-card">
        <h3>Топ продаж за период</h3>
        <?php if (empty($salesByProduct)): ?>
        <p style="color:var(--muted);font-size:13px">За выбранный период продаж нет</p>
        <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>Артикул</th><th>Товар</th><th>Категория</th><th style="text-align:center">Продано (шт.)</th><th style="text-align:right">Выручка</th></tr></thead>
            <tbody>
            <?php foreach ($salesByProduct as $r): ?>
            <tr>
                <td style="font-family:monospace;font-size:11px"><?= e($r['article']) ?></td>
                <td style="font-size:13px"><?= e(mb_substr($r['name'],0,38)) ?></td>
                <td style="font-size:11px;color:var(--muted)"><?= e($r['cat']) ?></td>
                <td style="text-align:center;font-weight:700;color:var(--navy)"><?= $r['total_qty'] ?></td>
                <td style="text-align:right;font-weight:700"><?= fmtPrice($r['total_sum']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
