<?php
require_once __DIR__ . '/../config.php';
requireEmployee();
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action   = $_POST['action'] ?? '';
    $id       = (int)($_POST['id'] ?? 0);
    $article  = trim($_POST['article']     ?? '');
    $name     = trim($_POST['name']        ?? '');
    $catId    = (int)($_POST['category_id']?? 0);
    $price    = (float)str_replace(',','.',$_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock_qty']  ?? 0);
    $desc     = trim($_POST['description'] ?? '');
    $engines  = array_map('intval', $_POST['engines'] ?? []);

    if ($action==='add' && $article && $name && $catId && $price>0) {
        $pdo->prepare('INSERT INTO products (article,name,category_id,description,price,stock_qty) VALUES (?,?,?,?,?,?)')
            ->execute([$article,$name,$catId,$desc,$price,$stock]);
        $newId = (int)$pdo->lastInsertId();
        $insEng = $pdo->prepare('INSERT IGNORE INTO product_engines (product_id,engine_model_id) VALUES (?,?)');
        foreach ($engines as $eid) { $insEng->execute([$newId,$eid]); }
        $msg = 'Товар добавлен!';
    } elseif ($action==='edit' && $id) {
        $pdo->prepare('UPDATE products SET article=?,name=?,category_id=?,description=?,price=?,stock_qty=? WHERE id=?')
            ->execute([$article,$name,$catId,$desc,$price,$stock,$id]);
        // Синхронизация совместимости
        $pdo->prepare('DELETE FROM product_engines WHERE product_id=?')->execute([$id]);
        $insEng = $pdo->prepare('INSERT INTO product_engines (product_id,engine_model_id) VALUES (?,?)');
        foreach ($engines as $eid) { $insEng->execute([$id,$eid]); }
        $msg = 'Товар обновлён!';
    } elseif ($action==='toggle' && $id) {
        $pdo->prepare('UPDATE products SET is_active=NOT is_active WHERE id=?')->execute([$id]);
        header('Location: '.BASE_URL.'admin/products.php'); exit;
    }
}

$search = trim($_GET['q'] ?? '');
$sql = $search
    ? 'SELECT p.*,c.name AS cat FROM products p JOIN categories c ON c.id=p.category_id WHERE p.article LIKE ? OR p.name LIKE ? ORDER BY p.stock_qty ASC, p.id DESC'
    : 'SELECT p.*,c.name AS cat FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.stock_qty ASC, p.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($search ? ["%$search%","%$search%"] : []);
$products = $stmt->fetchAll();

$categories   = $pdo->query('SELECT id,name FROM categories WHERE parent_id IS NOT NULL ORDER BY name')->fetchAll();
$engineModels = $pdo->query('SELECT id,full_name FROM engine_models WHERE is_active=1 ORDER BY brand,model')->fetchAll();

// Совместимые двигатели для каждого товара
$engByProduct = [];
$allEngs = $pdo->query('SELECT product_id, engine_model_id FROM product_engines')->fetchAll();
foreach ($allEngs as $row) {
    $engByProduct[$row['product_id']][] = $row['engine_model_id'];
}
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Товары — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
        <h1 class="admin-title" style="margin:0">Товары и запчасти</h1>
        <button class="btn-primary" onclick="document.getElementById('addModal').classList.add('open')" style="font-size:13px;padding:9px 18px">+ Добавить товар</button>
    </div>

    <?php if ($msg): ?><div class="alert alert-success">✓ <?= e($msg) ?></div><?php endif; ?>

    <form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-input" style="max-width:280px;padding:7px 11px;font-size:13px" placeholder="Артикул или название...">
        <button type="submit" class="btn-cart btn-sm">Найти</button>
        <?php if ($search): ?><a href="<?= BASE_URL ?>admin/products.php" class="btn-reset">Сбросить</a><?php endif; ?>
    </form>

    <table class="admin-table">
        <thead><tr><th>Артикул</th><th>Название</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Статус</th><th>Действия</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
        <tr style="opacity:<?= $p['is_active']?'1':'0.5' ?>">
            <td style="font-family:monospace;font-size:11px"><?= e($p['article']) ?></td>
            <td style="font-size:13px;font-weight:600"><?= e(mb_substr($p['name'],0,38)) ?></td>
            <td style="font-size:11px;color:var(--muted)"><?= e($p['cat']) ?></td>
            <td style="font-weight:600"><?= fmtPrice($p['price']) ?></td>
            <td><span class="badge" style="background:<?= $p['stock_qty']===0?'#e74c3c22':($p['stock_qty']<=5?'#e67e2222':'#27ae6022') ?>;color:<?= $p['stock_qty']===0?'var(--danger)':($p['stock_qty']<=5?'#e67e22':'var(--success)') ?>"><?= $p['stock_qty'] ?> шт.</span></td>
            <td style="font-size:12px"><?= $p['is_active']?'<span style="color:var(--success)">Активен</span>':'<span style="color:var(--danger)">Скрыт</span>' ?></td>
            <td style="white-space:nowrap">
                <button onclick='openEdit(<?= json_encode(['id'=>$p['id'],'article'=>$p['article'],'name'=>$p['name'],'price'=>$p['price'],'stock_qty'=>$p['stock_qty'],'description'=>$p['description']??'','category_id'=>$p['category_id'],'engines'=>$engByProduct[$p['id']]??[]]) ?>)'
                        style="background:var(--navy);color:#fff;border:none;padding:5px 9px;border-radius:4px;cursor:pointer;font-size:11px;margin-right:4px">✏</button>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" style="background:<?= $p['is_active']?'var(--danger)':'var(--success)' ?>;color:#fff;border:none;padding:5px 9px;border-radius:4px;cursor:pointer;font-size:11px">
                        <?= $p['is_active']?'Скрыть':'Показать' ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>

<!-- МОДАЛ ДОБАВИТЬ -->
<div class="modal-bg" id="addModal">
  <div class="modal">
    <h3 style="font-family:var(--font-h);font-size:18px;color:var(--navy);margin-bottom:18px">Добавить товар</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Артикул *</label><input type="text" name="article" class="form-input" required></div>
      <div class="form-group"><label>Название *</label><input type="text" name="name" class="form-input" required></div>
      <div class="form-group"><label>Категория *</label>
        <select name="category_id" class="form-input" required>
          <option value="">— выберите —</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label>Цена (₽) *</label><input type="number" name="price" class="form-input" step="0.01" required></div>
        <div class="form-group"><label>Остаток (шт.)</label><input type="number" name="stock_qty" class="form-input" value="0" min="0"></div>
      </div>
      <div class="form-group"><label>Описание</label><textarea name="description" class="form-input" rows="2"></textarea></div>
      <div class="form-group">
        <label>Совместимые двигатели (зажать Ctrl для выбора нескольких)</label>
        <select name="engines[]" class="form-input" multiple style="height:130px">
          <?php foreach ($engineModels as $em): ?><option value="<?= $em['id'] ?>"><?= e($em['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn-primary" style="flex:1">Добавить</button>
        <button type="button" onclick="document.getElementById('addModal').classList.remove('open')" class="btn-reset" style="flex:1">Отмена</button>
      </div>
    </form>
  </div>
</div>

<!-- МОДАЛ РЕДАКТИРОВАТЬ -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <h3 style="font-family:var(--font-h);font-size:18px;color:var(--navy);margin-bottom:18px">Редактировать товар</h3>
    <form method="POST" id="editForm">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="eId">
      <div class="form-group"><label>Артикул *</label><input type="text" name="article" id="eArticle" class="form-input" required></div>
      <div class="form-group"><label>Название *</label><input type="text" name="name" id="eName" class="form-input" required></div>
      <div class="form-group"><label>Категория *</label>
        <select name="category_id" id="eCat" class="form-input" required>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label>Цена (₽) *</label><input type="number" name="price" id="ePrice" class="form-input" step="0.01" required></div>
        <div class="form-group"><label>Остаток (шт.)</label><input type="number" name="stock_qty" id="eStock" class="form-input" min="0"></div>
      </div>
      <div class="form-group"><label>Описание</label><textarea name="description" id="eDesc" class="form-input" rows="2"></textarea></div>
      <div class="form-group">
        <label>Совместимые двигатели (Ctrl для мульти)</label>
        <select name="engines[]" id="eEngines" class="form-input" multiple style="height:130px">
          <?php foreach ($engineModels as $em): ?><option value="<?= $em['id'] ?>"><?= e($em['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn-primary" style="flex:1">Сохранить</button>
        <button type="button" onclick="document.getElementById('editModal').classList.remove('open')" class="btn-reset" style="flex:1">Отмена</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(p) {
    document.getElementById('eId').value      = p.id;
    document.getElementById('eArticle').value = p.article;
    document.getElementById('eName').value    = p.name;
    document.getElementById('ePrice').value   = p.price;
    document.getElementById('eStock').value   = p.stock_qty;
    document.getElementById('eDesc').value    = p.description;
    document.getElementById('eCat').value     = p.category_id;
    // Выделить совместимые двигатели
    var sel = document.getElementById('eEngines');
    for (var i=0; i<sel.options.length; i++) {
        sel.options[i].selected = p.engines.includes(parseInt(sel.options[i].value));
    }
    document.getElementById('editModal').classList.add('open');
}
document.querySelectorAll('.modal-bg').forEach(function(bg) {
    bg.addEventListener('click', function(e) { if(e.target===this) this.classList.remove('open'); });
});
</script>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
