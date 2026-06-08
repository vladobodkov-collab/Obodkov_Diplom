<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();

$engines_list = $pdo->query('SELECT id,brand,model,full_name FROM engine_models WHERE is_active=1 ORDER BY brand,model')->fetchAll();
$cats_list    = $pdo->query('SELECT id,name FROM categories WHERE parent_id IS NULL ORDER BY sort_order')->fetchAll();

$filterEngines = $_GET['engine'] ?? [];
$filterCats    = $_GET['cat']    ?? [];
$filterStock   = !empty($_GET['instock']);
$search        = trim($_GET['q'] ?? '');
$sort          = $_GET['sort']   ?? 'default';

$where  = ['p.is_active=1'];
$params = [];

if ($search) {
    $where[]  = '(p.article LIKE ? OR p.name LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterEngines) {
    $in      = implode(',', array_fill(0, count($filterEngines), '?'));
    $where[] = "EXISTS(SELECT 1 FROM product_engines pe2 WHERE pe2.product_id=p.id AND pe2.engine_model_id IN ($in))";
    $params  = array_merge($params, array_map('intval', $filterEngines));
}
if ($filterCats) {
    $in      = implode(',', array_fill(0, count($filterCats), '?'));
    $where[] = "(c.parent_id IN ($in) OR c.id IN ($in))";
    $params  = array_merge($params, array_map('intval', $filterCats), array_map('intval', $filterCats));
}
if ($filterStock) $where[] = 'p.stock_qty>0';

$orderBy = match($sort) {
    'price-asc'  => 'p.price ASC',
    'price-desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.id ASC',
};

$sql = "SELECT p.id, p.article, p.name, p.price, p.stock_qty,
               (SELECT file_path FROM product_images WHERE product_id=p.id AND is_main=1 LIMIT 1) AS img,
               c.name AS cat,
               GROUP_CONCAT(CONCAT(em.brand,'-',em.model) ORDER BY em.brand,em.model SEPARATOR ', ') AS compat
        FROM products p
        JOIN categories c ON c.id=p.category_id
        LEFT JOIN product_engines pe ON pe.product_id=p.id
        LEFT JOIN engine_models em ON em.id=pe.engine_model_id
        WHERE ".implode(' AND ',$where)."
        GROUP BY p.id ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Каталог — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>"></head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="page-banner">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Главная</a> → <span>Каталог</span></div>
    <h1>Каталог запчастей</h1>
  </div>
</div>

<div class="catalog-layout container">
  <aside class="catalog-sidebar">
    <form method="GET" id="filterForm">
      <div class="filter-block">
        <h3 class="filter-title">Модель двигателя</h3>
        <?php foreach ($engines_list as $eng): ?>
        <label class="filter-check">
          <input type="checkbox" name="engine[]" value="<?= $eng['id'] ?>"
            <?= in_array($eng['id'], array_map('intval',$filterEngines))?'checked':'' ?>
            onchange="this.form.submit()">
          <?= e($eng['full_name']) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="filter-block">
        <h3 class="filter-title">Категория</h3>
        <?php foreach ($cats_list as $cat): ?>
        <label class="filter-check">
          <input type="checkbox" name="cat[]" value="<?= $cat['id'] ?>"
            <?= in_array($cat['id'], array_map('intval',$filterCats))?'checked':'' ?>
            onchange="this.form.submit()">
          <?= e($cat['name']) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="filter-block">
        <h3 class="filter-title">Наличие</h3>
        <label class="filter-check">
          <input type="checkbox" name="instock" value="1" <?= $filterStock?'checked':'' ?> onchange="this.form.submit()">
          Есть в наличии
        </label>
      </div>
      <input type="hidden" name="q"    value="<?= e($search) ?>">
      <input type="hidden" name="sort" value="<?= e($sort) ?>">
    </form>
    <a href="<?= BASE_URL ?>catalog.php" class="btn-reset" style="width:100%;margin-top:4px;display:block;text-align:center">Сбросить фильтры</a>
  </aside>

  <main class="catalog-main">
    <div class="catalog-toolbar">
      <form method="GET" style="display:flex;gap:8px;align-items:center;flex:1;flex-wrap:wrap">
        <?php foreach ($filterEngines as $eid): ?><input type="hidden" name="engine[]" value="<?= (int)$eid ?>"><?php endforeach; ?>
        <?php foreach ($filterCats as $cid): ?><input type="hidden" name="cat[]" value="<?= (int)$cid ?>"><?php endforeach; ?>
        <?php if ($filterStock): ?><input type="hidden" name="instock" value="1"><?php endif; ?>
        <input type="text" name="q" class="form-input" style="flex:1;min-width:160px;padding:7px 11px;font-size:13px"
               placeholder="Поиск по артикулу или названию..." value="<?= e($search) ?>">
        <button type="submit" class="btn-cart btn-sm">Найти</button>
        <select name="sort" class="sort-select" onchange="this.form.submit()">
          <option value="default"    <?= $sort==='default'?'selected':'' ?>>По умолчанию</option>
          <option value="price-asc"  <?= $sort==='price-asc'?'selected':'' ?>>Цена ↑</option>
          <option value="price-desc" <?= $sort==='price-desc'?'selected':'' ?>>Цена ↓</option>
          <option value="name"       <?= $sort==='name'?'selected':'' ?>>По названию</option>
        </select>
      </form>
      <span class="results-count">Найдено: <?= count($products) ?></span>
    </div>

    <?php if (empty($products)): ?>
    <div class="no-results" style="display:flex">
      <div class="no-results-icon">🔍</div>
      <p>Товары не найдены. Попробуйте изменить параметры.</p>
      <a href="<?= BASE_URL ?>catalog.php" class="btn-reset">Сбросить</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $p): ?>
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
          <div class="product-stock <?= $p['stock_qty']>0?'in-stock':'out-stock' ?>">
            <?= $p['stock_qty']>0 ? '✓ В наличии ('.$p['stock_qty'].' шт.)' : '✗ Под заказ' ?>
          </div>
          <div class="product-bottom">
            <span class="product-price"><?= fmtPrice($p['price']) ?></span>
            <?php if (isLoggedIn()): ?>
              <form method="POST" action="<?= BASE_URL ?>cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn-cart btn-sm">В корзину</button>
              </form>
            <?php else: ?>
              <a href="<?= BASE_URL ?>login.php" class="btn-cart btn-sm" style="text-decoration:none">Войти</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
