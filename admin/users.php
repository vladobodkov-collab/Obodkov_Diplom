<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action==='set_role' && $uid && $uid!==$_SESSION['user_id']) {
        $roleId = (int)$_POST['role_id'];
        $pdo->prepare('UPDATE users SET role_id=? WHERE id=?')->execute([$roleId,$uid]);
        $msg = 'Роль обновлена.';
    }
    if ($action==='toggle' && $uid && $uid!==$_SESSION['user_id']) {
        $pdo->prepare('UPDATE users SET is_active=NOT is_active WHERE id=?')->execute([$uid]);
        header('Location: '.BASE_URL.'admin/users.php'); exit;
    }
    if ($action==='set_role') header('Location: '.BASE_URL.'admin/users.php'); exit;
}

$users = $pdo->query('SELECT u.*,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.role_id,u.id')->fetchAll();
$roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
$roleColors = ['admin'=>'#e74c3c','employee'=>'#8e44ad','client'=>'#2980b9','guest'=>'#888'];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Пользователи — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <h1 class="admin-title">Пользователи системы</h1>
    <?php if ($msg): ?><div class="alert alert-success">✓ <?= e($msg) ?></div><?php endif; ?>

    <!-- Роли -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:14px 18px;margin-bottom:20px;display:flex;gap:14px;flex-wrap:wrap">
        <?php foreach ($roles as $r): ?>
        <div style="background:<?= $roleColors[$r['name']]??'#666' ?>15;border:1px solid <?= $roleColors[$r['name']]??'#666' ?>33;border-radius:6px;padding:8px 14px;min-width:180px">
            <div style="font-weight:700;color:<?= $roleColors[$r['name']]??'#666' ?>;font-size:13px;margin-bottom:2px"><?= e($r['name']) ?></div>
            <div style="font-size:11px;color:var(--muted)"><?= e($r['description']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <table class="admin-table">
        <thead>
            <tr><th>ID</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>Роль</th><th>Регистрация</th><th>Статус</th><th>Изменить роль</th><th>Блокировка</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr style="opacity:<?= $u['is_active']?'1':'0.55' ?>">
            <td style="color:var(--muted);font-size:12px"><?= $u['id'] ?></td>
            <td style="font-weight:600;font-size:13px"><?= e($u['last_name'].' '.$u['first_name']) ?></td>
            <td style="font-size:12px"><?= e($u['email']) ?></td>
            <td style="font-size:12px"><?= e($u['phone']??'—') ?></td>
            <td><span class="badge" style="background:<?= $roleColors[$u['role_name']]??'#666' ?>22;color:<?= $roleColors[$u['role_name']]??'#666' ?>"><?= e($u['role_name']) ?></span></td>
            <td style="font-size:11px;color:var(--muted)"><?= date('d.m.Y',strtotime($u['created_at'])) ?></td>
            <td style="font-size:12px"><?= $u['is_active']?'<span style="color:var(--success);font-weight:600">✓ Активен</span>':'<span style="color:var(--danger);font-weight:600">✗ Заблокирован</span>' ?></td>
            <td>
                <!-- Отдельная форма смены роли (не вложена!) -->
                <?php if ($u['id']!==$_SESSION['user_id']): ?>
                <form method="POST" style="display:flex;gap:5px;align-items:center">
                    <input type="hidden" name="action"  value="set_role">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role_id" style="border:1px solid var(--border);border-radius:4px;padding:4px 7px;font-size:12px;font-family:var(--font-b)">
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $u['role_id']===$r['id']?'selected':'' ?>><?= e($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-cart btn-sm">OK</button>
                </form>
                <?php else: ?><span style="font-size:11px;color:var(--muted)">Это вы</span><?php endif; ?>
            </td>
            <td>
                <!-- Отдельная форма блокировки (не вложена!) -->
                <?php if ($u['id']!==$_SESSION['user_id']): ?>
                <form method="POST">
                    <input type="hidden" name="action"  value="toggle">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn-sm" style="background:<?= $u['is_active']?'var(--danger)':'var(--success)' ?>;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:11px;font-family:var(--font-b)">
                        <?= $u['is_active']?'Заблокировать':'Разблокировать' ?>
                    </button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
