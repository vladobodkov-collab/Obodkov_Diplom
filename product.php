<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: '.BASE_URL.'catalog.php'); exit; }

$product = $pdo->prepare('
    SELECT p.*, c.name AS category_name
    FROM products p JOIN categories c ON c.id=p.category_id
    WHERE p.id=? AND p.is_active=1
');
$product->execute([$id]);
$p = $product->fetch();
if (!$p) { header('Location: '.BASE_URL.'catalog.php'); exit; }

// Совместимые двигатели
$engines = $pdo->prepare('
    SELECT em.full_name, em.brand, em.model
    FROM product_engines pe JOIN engine_models em ON em.id=pe.engine_model_id
    WHERE pe.product_id=? ORDER BY em.brand, em.model
');
$engines->execute([$id]);
$engines = $engines->fetchAll();

// Изображения
$images = $pdo->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_main DESC');
$images->execute([$id]);
$images = $images->fetchAll();
$mainImg = $images[0] ?? null;

// Обработка «Купить в 1 клик» (быстрый заказ)
$quickMsg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='quick_buy') {
    requireLogin();
    // Добавить в корзину 1 шт и перейти к оформлению
    $pdo->prepare('INSERT INTO cart (user_id,product_id,qty) VALUES (?,?,1) ON DUPLICATE KEY UPDATE qty=qty+1')
        ->execute([$_SESSION['user_id'], $id]);
    resetCartCount();
    header('Location: '.BASE_URL.'checkout.php');
    exit;
}
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($p['name']) ?> — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>"></head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="page-banner">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>index.php">Главная</a> →
      <a href="<?= BASE_URL ?>catalog.php">Каталог</a> →
      <span><?= e($p['name']) ?></span>
    </div>
    <h1><?= e($p['name']) ?></h1>
  </div>
</div>

<div class="container product-page-layout">
  <!-- ФОТО -->
  <div class="product-gallery">
    <?php if ($mainImg && file_exists(ROOT.$mainImg['file_path'])): ?>
      <img src="<?= BASE_URL ?><?= e($mainImg['file_path']) ?>" alt="<?= e($p['name']) ?>">
    <?php else: ?>
      <div class="big-placeholder">🔩</div>
    <?php endif; ?>
  </div>

  <!-- ИНФОРМАЦИЯ -->
  <div class="product-details">
    <div class="product-meta">
      <span class="product-art-badge">Арт: <?= e($p['article']) ?></span>
      <span class="product-art-badge"><?= e($p['category_name']) ?></span>
      <?php if ($p['is_original']): ?>
        <span class="product-orig-badge">✓ Оригинал</span>
      <?php endif; ?>
    </div>

    <div class="product-big-price"><?= fmtPrice($p['price']) ?></div>

    <div class="product-stock-line <?= $p['stock_qty']>0?'in-stock':'out-stock' ?>">
      <?php if ($p['stock_qty']>0): ?>
        ✓ В наличии — <?= $p['stock_qty'] ?> <?= e($p['unit']) ?>
      <?php else: ?>
        ✗ Нет в наличии — можно заказать
      <?php endif; ?>
    </div>

    <?php if ($p['description']): ?>
    <div class="product-desc"><?= e($p['description']) ?></div>
    <?php endif; ?>

    <?php if ($engines): ?>
    <div class="product-compat-block">
      <h3>Совместимые двигатели</h3>
      <div class="compat-tags">
        <?php foreach ($engines as $eng): ?>
          <a href="<?= BASE_URL ?>catalog.php?q=<?= urlencode($eng['full_name']) ?>" class="compat-tag">
            <?= e($eng['full_name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="product-action-btns">
      <?php if (isLoggedIn()): ?>
        <form method="POST" action="<?= BASE_URL ?>cart.php">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <button type="submit" class="btn-primary">🛒 В корзину</button>
        </form>
        <form method="POST">
          <input type="hidden" name="action" value="quick_buy">
          <button type="submit" class="btn-outline-dark">⚡ Купить в 1 клик</button>
        </form>
      <?php else: ?>
        <a href="<?= BASE_URL ?>login.php" class="btn-primary">Войти для заказа</a>
      <?php endif; ?>
    </div>

    <div style="margin-top:20px;padding:14px;background:var(--bg);border-radius:6px;font-size:13px;color:var(--muted)">
      <strong style="color:var(--text)">Доставка и оплата:</strong><br>
      Самовывоз · Транспортная компания · Курьер<br>
      Наличные · Банковская карта · Банковский перевод
    </div>
  </div>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
