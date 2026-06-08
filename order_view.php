<?php
require_once __DIR__ . '/config.php';
requireLogin();
$pdo     = getDB();
$orderId = (int)($_GET['id'] ?? 0);

// Только свой заказ
$order = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$order->execute([$orderId, $_SESSION['user_id']]);
$order = $order->fetch();
if (!$order) { header('Location: '.BASE_URL.'account.php'); exit; }

$items = $pdo->prepare('
    SELECT oi.qty, oi.price, oi.qty*oi.price AS subtotal,
           p.name, p.article, p.id AS product_id
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
<title>Заказ #<?= $orderId ?> — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="page-banner">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>index.php">Главная</a> →
            <a href="<?= BASE_URL ?>account.php">Личный кабинет</a> →
            <span>Заказ #<?= $orderId ?></span>
        </div>
        <h1>Заказ #<?= $orderId ?></h1>
    </div>
</div>

<div class="container" style="padding:28px 20px 48px">

    <!-- Заголовок заказа -->
    <div class="order-view-header">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:12px">
            <h2 style="font-family:var(--font-h);font-size:22px;color:var(--navy);margin:0">Заказ #<?= $orderId ?></h2>
            <span class="status-badge" style="background:<?= $statusColors[$st]??'#666' ?>22;color:<?= $statusColors[$st]??'#666' ?>">
                <?= $statusLabels[$st] ?? $st ?>
            </span>
        </div>
        <div class="order-meta-grid">
            <div class="order-meta-item">
                <label>Дата оформления</label>
                <span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="order-meta-item">
                <label>Способ доставки</label>
                <span><?= match($order['delivery_method']) { 'transport'=>'Транспортная компания','courier'=>'Курьер',default=>'Самовывоз' } ?></span>
            </div>
            <div class="order-meta-item">
                <label>Способ оплаты</label>
                <span><?= match($order['payment_method']) { 'card'=>'Банковская карта','bank_transfer'=>'Банковский перевод',default=>'Наличными' } ?></span>
            </div>
            <?php if ($order['delivery_address']): ?>
            <div class="order-meta-item" style="grid-column:span 2">
                <label>Адрес доставки</label>
                <span><?= e($order['delivery_address']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['comment']): ?>
            <div class="order-meta-item" style="grid-column:span 3">
                <label>Комментарий</label>
                <span><?= e($order['comment']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Позиции -->
    <table class="items-table" style="margin-top:16px">
        <thead>
            <tr>
                <th>Товар</th>
                <th>Артикул</th>
                <th style="text-align:center">Кол-во</th>
                <th style="text-align:right">Цена</th>
                <th style="text-align:right">Сумма</th>
            </tr>
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

    <div style="margin-top:20px">
        <a href="<?= BASE_URL ?>account.php" class="btn-outline-dark" style="display:inline-block">← Назад в личный кабинет</a>
    </div>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body>
</html>
