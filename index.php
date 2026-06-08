<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$featured = $pdo->query('
    SELECT p.id, p.article, p.name, p.price, p.stock_qty,
           (SELECT file_path FROM product_images WHERE product_id=p.id AND is_main=1 LIMIT 1) AS img,
           GROUP_CONCAT(CONCAT(em.brand,"-",em.model) ORDER BY em.brand,em.model SEPARATOR ", ") AS compat
    FROM products p
    LEFT JOIN product_engines pe ON pe.product_id=p.id
    LEFT JOIN engine_models em ON em.id=pe.engine_model_id
    WHERE p.is_active=1
    GROUP BY p.id ORDER BY p.stock_qty DESC LIMIT 4
')->fetchAll();
$engines = $pdo->query('SELECT id,brand,model,full_name,description FROM engine_models WHERE is_active=1 ORDER BY brand,model')->fetchAll();
$total_products = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>МОТОРЫ И КОМПЛЕКТАЦИЯ — Запчасти ЯМЗ и ТМЗ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>"></head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<section class="hero">
  <div class="hero-split">
    <div class="container hero-inner">
      <div class="hero-content">
        <div class="hero-badge">Официальный дилер ЯМЗ и ТМЗ</div>
        <h1>Запчасти для двигателей <span class="accent">ЯМЗ и ТМЗ</span></h1>
        <p>Оригинальные детали от производителя. Гарантия завода. Доставка по России.</p>
        <div class="hero-btns">
          <a href="<?= BASE_URL ?>catalog.php" class="btn-primary">Перейти в каталог</a>
          <a href="<?= BASE_URL ?>contact.php" class="btn-outline">Задать вопрос</a>
        </div>
      </div>
    </div>
    <div class="hero-side">
      <div class="hero-stat"><strong><?= $total_products ?>+</strong><span>артикулов</span></div>
      <div class="hero-stat"><strong>15 лет</strong><span>на рынке</span></div>
      <div class="hero-stat"><strong>ЯМЗ · ТМЗ</strong><span>оригинал</span></div>
    </div>
  </div>
</section>

<section class="engines-section">
  <div class="container">
    <h2 class="section-title">Популярные модели двигателей</h2>
    <p class="section-sub">Запчасти для всех актуальных серий — в наличии на складе</p>
    <div class="engines-grid">
      <?php foreach ($engines as $i => $eng): ?>
      <a href="<?= BASE_URL ?>catalog.php?engine[]=<?= $eng['id'] ?>" class="engine-card <?= $i===4?'featured':'' ?>">
        <?php if ($i===4): ?><div class="engine-badge">Популярный</div><?php endif; ?>
        <div class="engine-icon">🔧</div>
        <div class="engine-name"><?= e($eng['full_name']) ?></div>
        <div class="engine-desc"><?= e($eng['description']??'') ?></div>
        <div class="engine-link">Смотреть запчасти →</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="products-section">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Рекомендуемые товары</h2>
      <a href="<?= BASE_URL ?>catalog.php" class="see-all">Весь каталог →</a>
    </div>
    <div class="products-grid">
      <?php foreach ($featured as $p): ?>
      <div class="product-card">
        <a href="<?= BASE_URL ?>product.php?id=<?= $p['id'] ?>" class="product-img">
          <?php if ($p['img'] && file_exists(ROOT.$p['img'])): ?>
            <img src="<?= BASE_URL ?><?= e($p['img']) ?>" alt="<?= e($p['name']) ?>">
          <?php else: ?>
            <div class="product-placeholder">🔩</div>
          <?php endif; ?>
        </a>
        <div class="product-info">
          <div class="product-article">Арт: <?= e($p['article']) ?></div>
          <a href="<?= BASE_URL ?>product.php?id=<?= $p['id'] ?>" class="product-name" style="color:inherit"><?= e($p['name']) ?></a>
          <div class="product-compat"><?= e($p['compat']??'—') ?></div>
          <div class="product-bottom">
            <span class="product-price"><?= fmtPrice($p['price']) ?></span>
            <?php if (isLoggedIn()): ?>
              <form method="POST" action="<?= BASE_URL ?>cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn-cart">В корзину</button>
              </form>
            <?php else: ?>
              <a href="<?= BASE_URL ?>login.php" class="btn-cart" style="text-decoration:none">Войти</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="advantages-section">
  <div class="container">
    <h2 class="section-title white">Почему выбирают нас</h2>
    <div class="advantages-grid">
      <div class="advantage-item"><div class="adv-icon">✅</div><h3>Оригинальные детали</h3><p>Прямые поставки от ПАО «Автодизель» (ЯМЗ) и ПАО «Тутаевский моторный завод»</p></div>
      <div class="advantage-item"><div class="adv-icon">🚚</div><h3>Быстрая доставка</h3><p>Отправка в день заказа. Доставка по всей России транспортными компаниями</p></div>
      <div class="advantage-item"><div class="adv-icon">🛡</div><h3>Гарантия завода</h3><p>Все запчасти сертифицированы и поставляются с официальной гарантией производителя</p></div>
      <div class="advantage-item"><div class="adv-icon">📞</div><h3>Техподдержка</h3><p>Квалифицированные специалисты помогут подобрать запчасть по модели и артикулу</p></div>
    </div>
  </div>
</section>

<section class="about-section" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="about-text">
        <h2 class="section-title">О компании</h2>
        <p>ООО «МОТОРЫ И КОМПЛЕКТАЦИЯ» занимается производством и продажей запасных частей для двигателей ЯМЗ и ТМЗ. Компания расположена в г. Тутаев Ярославской области.</p>
        <p>Мы сотрудничаем с частными автовладельцами, автосервисами и организациями, эксплуатирующими грузовую и специальную технику.</p>
        <ul class="about-list">
          <li>Официальный партнёр ПАО «Автодизель» (ЯМЗ)</li>
          <li>Официальный дилер ПАО «Тутаевский моторный завод» (ТМЗ)</li>
          <li>Свыше <?= $total_products ?> позиций в наличии на складе</li>
          <li>Оптовые и розничные цены</li>
        </ul>
      </div>
      <div class="about-contacts">
        <h3>Контакты</h3>
        <div class="contact-item"><span class="contact-icon">📍</span><span>Ярославская обл., г. Тутаев, ул. Строителей, 12</span></div>
        <div class="contact-item"><span class="contact-icon">📞</span><span>+7 (485) 123-45-67</span></div>
        <div class="contact-item"><span class="contact-icon">✉</span><span>info@motors-k.ru</span></div>
        <div class="contact-item"><span class="contact-icon">🕐</span><span>Пн–Пт: 8:00–17:00</span></div>
        <a href="<?= BASE_URL ?>contact.php" class="btn-primary" style="margin-top:16px;display:inline-block">Написать нам</a>
      </div>
    </div>
  </div>
</section>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
