<?php
require_once __DIR__ . '/../config.php';
requireEmployee();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['set_status'])) {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowed   = ['new','accepted','assembling','shipped','delivered','cancelled'];

    if (in_array($newStatus, $allowed)) {
        // Получаем текущий статус
        $cur = $pdo->prepare('SELECT status FROM orders WHERE id=?');
        $cur->execute([$orderId]);
        $currentStatus = $cur->fetchColumn();

        if ($newStatus !== $currentStatus) {
            try {
                $pdo->beginTransaction();

                // При отмене — вернуть товары на склад
                if ($newStatus === 'cancelled' && $currentStatus !== 'cancelled') {
                    $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id=?');
                    $items->execute([$orderId]);
                    $restoreStmt = $pdo->prepare('UPDATE products SET stock_qty=stock_qty+? WHERE id=?');
                    foreach ($items->fetchAll() as $item) {
                        $restoreStmt->execute([$item['qty'], $item['product_id']]);
                    }
                }

                // При снятии отмены — снова списать (если товара хватает)
                if ($currentStatus === 'cancelled' && $newStatus !== 'cancelled') {
                    $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id=?');
                    $items->execute([$orderId]);
                    $deductStmt = $pdo->prepare('UPDATE products SET stock_qty=stock_qty-? WHERE id=? AND stock_qty>=?');
                    foreach ($items->fetchAll() as $item) {
                        $deductStmt->execute([$item['qty'], $item['product_id'], $item['qty']]);
                        if ($deductStmt->rowCount()===0) {
                            throw new Exception('Недостаточно товара на складе для восстановления заказа.');
                        }
                    }
                }

                $empId = ($newStatus==='accepted') ? $_SESSION['user_id'] : null;
                if ($empId) {
                    $pdo->prepare('UPDATE orders SET status=?,employee_id=?,updated_at=NOW() WHERE id=?')->execute([$newStatus,$empId,$orderId]);
                } else {
                    $pdo->prepare('UPDATE orders SET status=?,updated_at=NOW() WHERE id=?')->execute([$newStatus,$orderId]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $flashError = $e->getMessage();
            }
        }
    }
    header('Location: '.BASE_URL.'admin/orders.php'.($_GET['status']??''?'?status='.$_GET['status']:''));
    exit;
}

$filterStatus = $_GET['status'] ?? '';
$where  = '1=1'; $params = [];
if ($filterStatus) { $where = 'o.status=?'; $params[] = $filterStatus; }

$orders = $pdo->prepare("
    SELECT o.id, o.status, o.total_amount, o.delivery_method, o.payment_method,
           o.created_at, o.delivery_address,
           CONCAT(cu.last_name,' ',cu.first_name) AS client_name, cu.phone AS client_phone,
           CONCAT(eu.last_name,' ',eu.first_name) AS employee_name
    FROM orders o
    JOIN users cu ON cu.id=o.user_id
    LEFT JOIN users eu ON eu.id=o.employee_id
    WHERE $where ORDER BY o.created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

$statusColors = ['new'=>'#e67e22','accepted'=>'#2980b9','assembling'=>'#8e44ad','shipped'=>'#16a085','delivered'=>'#27ae60','cancelled'=>'#c0392b'];
$statusLabels = ['new'=>'Новый','accepted'=>'Принят','assembling'=>'Комплектуется','shipped'=>'Отгружен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Заказы — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <h1 class="admin-title">Управление заказами</h1>

    <!-- Фильтр -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        <a href="<?= BASE_URL ?>admin/orders.php" style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;background:<?= !$filterStatus?'var(--navy)':'#eee' ?>;color:<?= !$filterStatus?'#fff':'#333' ?>;text-decoration:none">Все</a>
        <?php foreach ($statusLabels as $val=>$lbl): ?>
        <a href="?status=<?= $val ?>" style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;background:<?= $filterStatus===$val?$statusColors[$val].'33':'#eee' ?>;color:<?= $filterStatus===$val?$statusColors[$val]:'#333' ?>;text-decoration:none">
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>

    <table class="admin-table">
        <thead>
            <tr><th>#</th><th>Клиент</th><th>Сумма</th><th>Доставка</th><th>Дата</th><th>Статус</th><th>Действие</th></tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--muted)">Заказов нет</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="<?= BASE_URL ?>admin/order_view.php?id=<?= $o['id'] ?>" style="color:var(--orange);font-weight:700">#<?= $o['id'] ?></a></td>
            <td>
                <div style="font-weight:600;font-size:13px"><?= e($o['client_name']) ?></div>
                <div style="font-size:11px;color:var(--muted)"><?= e($o['client_phone']??'') ?></div>
            </td>
            <td style="font-weight:700"><?= fmtPrice($o['total_amount']) ?></td>
            <td style="font-size:12px"><?= match($o['delivery_method']??'') { 'transport'=>'ТК','courier'=>'Курьер',default=>'Самовывоз' } ?></td>
            <td style="font-size:11px;color:var(--muted)"><?= date('d.m.Y H:i',strtotime($o['created_at'])) ?></td>
            <td><span class="badge" style="background:<?= $statusColors[$o['status']]??'#666' ?>22;color:<?= $statusColors[$o['status']]??'#666' ?>"><?= $statusLabels[$o['status']]??$o['status'] ?></span></td>
            <td>
                <form method="POST" style="display:flex;gap:5px;align-items:center">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="status" style="border:1px solid var(--border);border-radius:4px;padding:4px 7px;font-size:12px;font-family:var(--font-b)">
                        <?php foreach ($statusLabels as $val=>$lbl): ?>
                        <option value="<?= $val ?>" <?= $o['status']===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="set_status" value="1" class="btn-cart btn-sm">OK</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
