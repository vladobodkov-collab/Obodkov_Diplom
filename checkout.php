<?php
require_once __DIR__ . '/config.php';
requireLogin();
$pdo    = getDB();
$userId = $_SESSION['user_id'];

// Загружаем корзину
$stmt = $pdo->prepare('
    SELECT c.product_id, c.qty,
           p.name, p.article, p.price, p.stock_qty,
           p.price*c.qty AS subtotal
    FROM cart c JOIN products p ON p.id=c.product_id
    WHERE c.user_id=?
');
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: '.BASE_URL.'cart.php');
    exit;
}

$total   = array_sum(array_column($cartItems, 'subtotal'));
$errors  = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $address  = trim($_POST['address']         ?? '');
    $delivery = $_POST['delivery_method']      ?? 'pickup';
    $payment  = $_POST['payment_method']       ?? 'cash';
    $comment  = trim($_POST['comment']         ?? '');

    $allowedDelivery = ['pickup','transport','courier'];
    $allowedPayment  = ['cash','card','bank_transfer'];

    if (!in_array($delivery, $allowedDelivery)) $delivery = 'pickup';
    if (!in_array($payment,  $allowedPayment))  $payment  = 'cash';

    // Проверка остатков
    foreach ($cartItems as $item) {
        if ($item['stock_qty'] < $item['qty']) {
            $errors[] = 'Недостаточно товара «'.e($item['name']).'» на складе. '
                       .'В наличии: '.$item['stock_qty'].' шт., в корзине: '.$item['qty'].' шт.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Создаём заказ
            $pdo->prepare('
                INSERT INTO orders (user_id, status, total_amount, delivery_address, delivery_method, payment_method, comment)
                VALUES (?, "new", ?, ?, ?, ?, ?)
            ')->execute([$userId, $total, $address, $delivery, $payment, $comment]);

            $orderId  = $pdo->lastInsertId();
            $insItem  = $pdo->prepare('INSERT INTO order_items (order_id,product_id,qty,price) VALUES (?,?,?,?)');
            $updStock = $pdo->prepare('UPDATE products SET stock_qty=stock_qty-? WHERE id=? AND stock_qty>=?');

            foreach ($cartItems as $item) {
                $insItem->execute([$orderId, $item['product_id'], $item['qty'], $item['price']]);
                $affected = $updStock->execute([$item['qty'], $item['product_id'], $item['qty']]);
                // Двойная проверка — если строк не обновилось (гонка), откатить
                if ($updStock->rowCount() === 0) {
                    throw new Exception('Товар «'.$item['name'].'» только что закончился на складе.');
                }
            }

            // Очищаем корзину
            $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$userId]);
            resetCartCount();

            $pdo->commit();
            header('Location: '.BASE_URL.'order_success.php?id='.$orderId);
            exit;

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = $ex->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Оформление заказа — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
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
            <a href="<?= BASE_URL ?>cart.php">Корзина</a> →
            <span>Оформление заказа</span>
        </div>
        <h1>Оформление заказа</h1>
    </div>
</div>

<div class="container checkout-layout">

    <!-- ФОРМА -->
    <div>
        <?php if ($errors): ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= $err ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST">
            <div class="checkout-form-card">
                <h2>Доставка</h2>
                <div class="form-group">
                    <label>Способ доставки</label>
                    <select name="delivery_method" class="form-input">
                        <option value="pickup"    <?= ($_POST['delivery_method']??'')==='pickup'    ?'selected':'' ?>>Самовывоз (г. Тутаев, ул. Строителей, 12)</option>
                        <option value="transport" <?= ($_POST['delivery_method']??'')==='transport' ?'selected':'' ?>>Транспортная компания</option>
                        <option value="courier"   <?= ($_POST['delivery_method']??'')==='courier'   ?'selected':'' ?>>Курьер</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Адрес доставки</label>
                    <input type="text" name="address" class="form-input"
                           placeholder="Город, улица, дом, квартира"
                           value="<?= e($_POST['address'] ?? '') ?>">
                </div>
            </div>

            <div class="checkout-form-card" style="margin-top:16px">
                <h2>Оплата</h2>
                <div class="form-group">
                    <label>Способ оплаты</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash"          <?= ($_POST['payment_method']??'')==='cash'          ?'selected':'' ?>>Наличными при получении</option>
                        <option value="card"          <?= ($_POST['payment_method']??'')==='card'          ?'selected':'' ?>>Банковская карта</option>
                        <option value="bank_transfer" <?= ($_POST['payment_method']??'')==='bank_transfer' ?'selected':'' ?>>Банковский перевод</option>
                    </select>
                </div>
            </div>

            <div class="checkout-form-card" style="margin-top:16px">
                <h2>Комментарий к заказу</h2>
                <div class="form-group" style="margin:0">
                    <textarea name="comment" class="form-input" rows="3"
                              placeholder="Укажите пожелания по доставке или комплектации..."><?= e($_POST['comment'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-primary full-width" style="margin-top:16px;font-size:18px;padding:16px">
                ✓ Подтвердить заказ
            </button>
        </form>
    </div>

    <!-- СВОДКА -->
    <aside class="cart-summary">
        <h2 class="cart-section-title">Ваш заказ</h2>

        <?php foreach ($cartItems as $item): ?>
        <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px;gap:8px">
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;color:var(--text)"><?= e(mb_substr($item['name'],0,35)) ?></div>
                <div style="color:var(--muted);font-size:11px"><?= $item['qty'] ?> шт. × <?= fmtPrice($item['price']) ?></div>
            </div>
            <div style="font-weight:600;white-space:nowrap"><?= fmtPrice($item['subtotal']) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="summary-row" style="margin-top:4px"><span>Товаров:</span><span><?= array_sum(array_column($cartItems,'qty')) ?> шт.</span></div>
        <div class="summary-row"><span>Доставка:</span><span>Рассчитывается</span></div>
        <div class="summary-total"><span>Итого:</span><span><?= fmtPrice($total) ?></span></div>

        <a href="<?= BASE_URL ?>cart.php" class="btn-outline-dark full-width">← Изменить корзину</a>
        <div class="summary-note" style="margin-top:14px">✓ Оригинальные запчасти с гарантией завода</div>
    </aside>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body>
</html>
