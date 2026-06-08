<?php
require_once __DIR__ . '/config.php';
requireLogin();
$pdo     = getDB();
$orderId = (int)($_GET['id'] ?? 0);

$order = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$order->execute([$orderId, $_SESSION['user_id']]);
$order = $order->fetch();
if (!$order) { header('Location: '.BASE_URL.'index.php'); exit; }
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Заказ оформлен — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="container" style="max-width:580px;padding:80px 20px;text-align:center">
    <div style="font-size:80px;margin-bottom:16px">✅</div>
    <h1 style="font-family:var(--font-h);font-size:34px;color:var(--navy);margin-bottom:10px">Заказ оформлен!</h1>
    <p style="color:var(--muted);font-size:17px;margin-bottom:6px">
        Номер заказа: <strong style="color:var(--navy)">#<?= $orderId ?></strong>
    </p>
    <p style="color:var(--muted);font-size:17px;margin-bottom:28px">
        Сумма: <strong><?= fmtPrice($order['total_amount']) ?></strong>
    </p>
    <p style="color:#999;margin-bottom:32px;font-size:15px">
        Наш менеджер свяжется с вами для подтверждения заказа.<br>
        Среднее время обработки — 1 рабочий день.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>order_view.php?id=<?= $orderId ?>" class="btn-primary">Посмотреть заказ</a>
        <a href="<?= BASE_URL ?>catalog.php" class="btn-outline-dark">Продолжить покупки</a>
    </div>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body>
</html>
