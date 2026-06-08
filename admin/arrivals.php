<?php
require_once __DIR__ . '/../config.php';
requireEmployee();
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $productId = (int)$_POST['product_id'];
    $qty       = (int)$_POST['qty'];
    $price     = (float)str_replace(',','.',$_POST['price_per_unit']);
    $supplier  = trim($_POST['supplier']   ?? '');
    $invoice   = trim($_POST['invoice_num']?? '');
    $note      = trim($_POST['note']       ?? '');
    if ($productId && $qty>0 && $price>0) {
        $pdo->prepare('INSERT INTO stock_arrivals (product_id,employee_id,qty,price_per_unit,supplier,invoice_num,note) VALUES (?,?,?,?,?,?,?)')
            ->execute([$productId,$_SESSION['user_id'],$qty,$price,$supplier,$invoice,$note]);
        $pdo->prepare('UPDATE products SET stock_qty=stock_qty+? WHERE id=?')->execute([$qty,$productId]);
        $msg = 'Поступление зарегистрировано! Остаток обновлён.';
    } else { $msg = 'error:Заполните обязательные поля.'; }
}

$arrivals = $pdo->query('
    SELECT sa.*, p.name AS pname, p.article,
           CONCAT(u.last_name," ",u.first_name) AS emp
    FROM stock_arrivals sa
    JOIN products p ON p.id=sa.product_id
    JOIN users u ON u.id=sa.employee_id
    ORDER BY sa.arrived_at DESC LIMIT 60
')->fetchAll();

$products = $pdo->query('SELECT id,article,name FROM products WHERE is_active=1 ORDER BY name')->fetchAll();
[$msgType,$msgText] = str_contains($msg,':') ? explode(':',$msg,2) : ['success',$msg];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Поступления — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <h1 class="admin-title">Поступления на склад</h1>
    <?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>">✓ <?= e($msgText) ?></div><?php endif; ?>

    <div class="form-card">
        <h3 style="font-family:var(--font-h);font-size:17px;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid var(--orange)">Зарегистрировать поступление</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group" style="margin:0"><label>Товар *</label>
                    <select name="product_id" class="form-input" required>
                        <option value="">— выберите товар —</option>
                        <?php foreach ($products as $pr): ?><option value="<?= $pr['id'] ?>"><?= e($pr['article'].' — '.mb_substr($pr['name'],0,40)) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0"><label>Кол-во (шт.) *</label><input type="number" name="qty" class="form-input" min="1" required></div>
                <div class="form-group" style="margin:0"><label>Закупочная цена (₽) *</label><input type="number" name="price_per_unit" class="form-input" step="0.01" min="0.01" required></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:12px;margin-bottom:14px">
                <div class="form-group" style="margin:0"><label>Поставщик</label><input type="text" name="supplier" class="form-input" placeholder="ПАО Автодизель..."></div>
                <div class="form-group" style="margin:0"><label>№ накладной</label><input type="text" name="invoice_num" class="form-input"></div>
                <div class="form-group" style="margin:0"><label>Примечание</label><input type="text" name="note" class="form-input"></div>
            </div>
            <button type="submit" class="btn-primary" style="padding:10px 26px">Зарегистрировать</button>
        </form>
    </div>

    <table class="admin-table">
        <thead><tr><th>Дата</th><th>Артикул</th><th>Товар</th><th>Кол-во</th><th>Цена закупки</th><th>Поставщик</th><th>Накладная</th><th>Сотрудник</th></tr></thead>
        <tbody>
        <?php foreach ($arrivals as $a): ?>
        <tr>
            <td style="font-size:11px;color:var(--muted)"><?= date('d.m.Y H:i',strtotime($a['arrived_at'])) ?></td>
            <td style="font-family:monospace;font-size:11px"><?= e($a['article']) ?></td>
            <td style="font-size:13px"><?= e(mb_substr($a['pname'],0,32)) ?></td>
            <td style="font-weight:700;color:var(--success)">+<?= $a['qty'] ?> шт.</td>
            <td><?= fmtPrice($a['price_per_unit']) ?></td>
            <td style="font-size:12px"><?= e($a['supplier']??'—') ?></td>
            <td style="font-size:12px"><?= e($a['invoice_num']??'—') ?></td>
            <td style="font-size:12px"><?= e($a['emp']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
