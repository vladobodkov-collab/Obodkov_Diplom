<?php
require_once __DIR__ . '/config.php';
requireLogin();
$pdo    = getDB();
$userId = $_SESSION['user_id'];

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

if ($action==='add' && $productId) {
    $ok = $pdo->prepare('SELECT id FROM products WHERE id=? AND is_active=1');
    $ok->execute([$productId]);
    if ($ok->fetch()) {
        $pdo->prepare('INSERT INTO cart (user_id,product_id,qty) VALUES (?,?,1) ON DUPLICATE KEY UPDATE qty=qty+1')
            ->execute([$userId,$productId]);
        resetCartCount();
    }
    // Вернуться на страницу товара или каталога
    $ref = $_SERVER['HTTP_REFERER'] ?? BASE_URL.'catalog.php';
    header('Location: '.$ref); exit;
}

if ($action==='update' && $productId) {
    $qty = max(1,(int)($_POST['qty']??1));
    $pdo->prepare('UPDATE cart SET qty=? WHERE user_id=? AND product_id=?')->execute([$qty,$userId,$productId]);
    resetCartCount();
    header('Location: '.BASE_URL.'cart.php'); exit;
}

if ($action==='remove' && $productId) {
    $pdo->prepare('DELETE FROM cart WHERE user_id=? AND product_id=?')->execute([$userId,$productId]);
    resetCartCount();
    header('Location: '.BASE_URL.'cart.php'); exit;
}

if ($action==='clear') {
    $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$userId]);
    resetCartCount();
    header('Location: '.BASE_URL.'cart.php'); exit;
}

// Загружаем корзину
$stmt = $pdo->prepare('
    SELECT c.product_id, c.qty,
           p.name, p.article, p.price, p.stock_qty,
           p.price*c.qty AS subtotal,
           (SELECT file_path FROM product_images WHERE product_id=p.id AND is_main=1 LIMIT 1) AS img
    FROM cart c JOIN products p ON p.id=c.product_id
    WHERE c.user_id=? ORDER BY c.added_at
');
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();
$total     = array_sum(array_column($cartItems,'subtotal'));
$totalQty  = array_sum(array_column($cartItems,'qty'));
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Корзина — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>"></head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="page-banner">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Главная</a> → <span>Корзина</span></div>
    <h1>Корзина</h1>
  </div>
</div>

<div class="container cart-layout">
  <div class="cart-items">
    <h2 class="cart-section-title">Товары в корзине</h2>

    <?php if (empty($cartItems)): ?>
    <div class="cart-empty" style="display:flex">
      <div class="empty-icon">🛒</div>
      <p>Корзина пуста</p>
      <a href="<?= BASE_URL ?>catalog.php" class="btn-primary">Перейти в каталог</a>
    </div>
    <?php else: ?>
    <?php foreach ($cartItems as $item): ?>
    <div class="cart-item">
      <a href="<?= BASE_URL ?>product.php?id=<?= $item['product_id'] ?>" class="cart-item-img">
        <?php if ($item['img'] && file_exists(ROOT.$item['img'])): ?>
          <img src="<?= BASE_URL ?><?= e($item['img']) ?>" alt="">
        <?php else: ?>
          🔩
        <?php endif; ?>
      </a>
      <div class="cart-item-info">
        <div class="cart-item-article">Арт: <?= e($item['article']) ?></div>
        <a href="<?= BASE_URL ?>product.php?id=<?= $item['product_id'] ?>" class="cart-item-name" style="color:inherit"><?= e($item['name']) ?></a>
        <div style="font-size:12px;color:<?= $item['stock_qty']>0?'var(--success)':'var(--danger)' ?>">
          <?= $item['stock_qty']>0 ? '✓ В наличии' : '✗ Под заказ' ?>
        </div>
      </div>
      <form method="POST" class="cart-item-qty">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
        <button type="submit" name="qty" value="<?= max(1,$item['qty']-1) ?>" class="qty-btn">−</button>
        <span class="qty-val"><?= $item['qty'] ?></span>
        <button type="submit" name="qty" value="<?= $item['qty']+1 ?>" class="qty-btn">+</button>
      </form>
      <div class="cart-item-price"><?= fmtPrice($item['subtotal']) ?></div>
      <form method="POST">
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
        <button type="submit" class="cart-remove" title="Удалить">✕</button>
      </form>
    </div>
    <?php endforeach; ?>

    <div style="text-align:right;margin-top:6px">
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn-reset" onclick="return confirm('Очистить корзину?')">Очистить корзину</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <aside class="cart-summary">
    <h2 class="cart-section-title">Итого</h2>
    <div class="summary-row"><span>Товаров:</span><span><?= $totalQty ?> шт.</span></div>
    <div class="summary-row"><span>Сумма:</span><span><?= fmtPrice($total) ?></span></div>
    <div class="summary-row"><span>Доставка:</span><span>Рассчитывается</span></div>
    <div class="summary-total"><span>К оплате:</span><span><?= fmtPrice($total) ?></span></div>

    <?php if (!empty($cartItems)): ?>
      <a href="<?= BASE_URL ?>checkout.php" class="btn-primary full-width">Оформить заказ</a>
    <?php else: ?>
      <button class="btn-primary full-width" disabled style="opacity:.5">Корзина пуста</button>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>catalog.php" class="btn-outline-dark full-width" style="margin-top:10px">Продолжить покупки</a>
    <div class="summary-note">✓ Оригинальные запчасти с гарантией завода</div>
  </aside>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
