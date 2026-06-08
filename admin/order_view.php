<?php
require_once __DIR__ . '/../config.php';
requireEmployee();
$pdo     = getDB();
$orderId = (int)($_GET['id'] ?? 0);

$order = $pdo->prepare('
    SELECT o.*, CONCAT(cu.last_name," ",cu.first_name) AS client_name,
           cu.email AS client_email, cu.phone AS client_phone,
           CONCAT(eu.last_name," ",eu.first_name) AS employee_name
    FROM orders o JOIN users cu ON cu.id=o.user_id
    LEFT JOIN users eu ON eu.id=o.employee_id
    WHERE o.id=?
');
$order->execute([$orderId]);
$order = $order->fetch();
if (!$order) { header('Location: '.BASE_URL.'admin/orders.php'); exit; }

$items = $pdo->prepare('
    SELECT oi.qty, oi.price, oi.qty*oi.price AS subtotal, p.name, p.article, p.id AS product_id
    FROM order_items oi JOIN products p ON p.id=oi.product_id
    WHERE oi.order_id=?
');
$items->execute([$orderId]);
$items = $items->fetchAll();

$statusLabels = ['new'=>'Новый','accepted'=>'Принят','assembling'=>'Комплектуется','shipped'=>'Отгружен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
$statusColors = ['new'=>'#e67e22','accepted'=>'#2980b9','assembling'=>'#8e44ad','shipped'=>'#16a085','delivered'=>'#27ae60','cancelled'=>'#c0392b'];
$st = $order['status'];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Заказ #<?= $orderId ?> — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <h1 class="admin-title" style="margin:0">Заказ #<?= $orderId ?></h1>
        <span class="badge" style="background:<?= $statusColors[$st]??'#666' ?>22;color:<?= $statusColors[$st]??'#666' ?>;font-size:13px;padding:4px 14px">
            <?= $statusLabels[$st]??$st ?>
        </span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
        <!-- Клиент -->
        <div class="section-card">
            <h3>Клиент</h3>
            <div style="font-size:14px;line-height:2">
                <strong><?= e($order['client_name']) ?></strong><br>
                📧 <?= e($order['client_email']) ?><br>
                📞 <?= e($order['client_phone']??'—') ?>
            </div>
        </div>
        <!-- Заказ -->
        <div class="section-card">
            <h3>Детали заказа</h3>
            <div style="font-size:14px;line-height:2">
                <strong>Дата:</strong> <?= date('d.m.Y H:i',strtotime($order['created_at'])) ?><br>
                <strong>Доставка:</strong> <?= match($order['delivery_method']) { 'transport'=>'Транспортная компания','courier'=>'Курьер',default=>'Самовывоз' } ?><br>
                <strong>Оплата:</strong> <?= match($order['payment_method']) { 'card'=>'Банковская карта','bank_transfer'=>'Банковский перевод',default=>'Наличными' } ?><br>
                <?php if ($order['delivery_address']): ?>
                <strong>Адрес:</strong> <?= e($order['delivery_address']) ?><br>
                <?php endif; ?>
                <?php if ($order['employee_name']): ?>
                <strong>Сотрудник:</strong> <?= e($order['employee_name']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($order['comment']): ?>
    <div class="alert alert-warning" style="margin-bottom:16px">💬 Комментарий: <?= e($order['comment']) ?></div>
    <?php endif; ?>

    <!-- Позиции -->
    <table class="items-table" style="margin-bottom:20px">
        <thead>
            <tr><th>Товар</th><th>Артикул</th><th style="text-align:center">Кол-во</th><th style="text-align:right">Цена</th><th style="text-align:right">Сумма</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><a href="<?= BASE_URL ?>product.php?id=<?= $item['product_id'] ?>" style="color:var(--navy);font-weight:600"><?= e($item['name']) ?></a></td>
            <td style="font-family:monospace;font-size:12px;color:var(--muted)"><?= e($item['article']) ?></td>
            <td style="text-align:center"><?= $item['qty'] ?> шт.</td>
            <td style="text-align:right"><?= fmtPrice($item['price']) ?></td>
            <td style="text-align:right;font-weight:600"><?= fmtPrice($item['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;border-top:2px solid var(--border);font-weight:700;padding:12px 14px">Итого:</td>
                <td style="text-align:right;border-top:2px solid var(--border);font-weight:700;font-size:16px;padding:12px 14px;color:var(--navy)"><?= fmtPrice($order['total_amount']) ?></td>
            </tr>
        </tfoot>
    </table>

    <a href="<?= BASE_URL ?>admin/orders.php" class="btn-outline-dark" style="display:inline-block">← Назад к заказам</a>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
