<?php
require_once __DIR__ . '/config.php';
requireLogin();
$pdo    = getDB();
$userId = $_SESSION['user_id'];
$msg    = '';
$msgType= 'success';

// ── Сохранение профиля ─────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    if ($action==='update_profile') {
        $phone = trim($_POST['phone'] ?? '');
        $fn    = trim($_POST['first_name']  ?? '');
        $ln    = trim($_POST['last_name']   ?? '');
        if ($fn && $ln) {
            $pdo->prepare('UPDATE users SET first_name=?,last_name=?,phone=? WHERE id=?')
                ->execute([$fn,$ln,$phone,$userId]);
            $_SESSION['user']['first_name'] = $fn;
            $_SESSION['user']['last_name']  = $ln;
            $msg = 'Профиль обновлён.';
        } else { $msg='Заполните имя и фамилию.'; $msgType='error'; }
    }

    if ($action==='change_password') {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $newPass2= $_POST['new_password2']?? '';
        $user = $pdo->prepare('SELECT password FROM users WHERE id=?');
        $user->execute([$userId]);
        $user = $user->fetch();
        if (!password_verify($oldPass, $user['password'])) {
            $msg='Текущий пароль неверный.'; $msgType='error';
        } elseif (strlen($newPass)<6) {
            $msg='Новый пароль — минимум 6 символов.'; $msgType='error';
        } elseif ($newPass!==$newPass2) {
            $msg='Пароли не совпадают.'; $msgType='error';
        } else {
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')
                ->execute([password_hash($newPass,PASSWORD_BCRYPT),$userId]);
            $msg='Пароль изменён.';
        }
    }
}

$user = $pdo->prepare('SELECT * FROM users WHERE id=?');
$user->execute([$userId]);
$user = $user->fetch();

$orders = $pdo->prepare('
    SELECT o.id, o.status, o.total_amount, o.created_at,
           COUNT(oi.id) AS items_count
    FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id
    WHERE o.user_id=? GROUP BY o.id ORDER BY o.created_at DESC
');
$orders->execute([$userId]);
$orders = $orders->fetchAll();

$statusColors = ['new'=>'#e67e22','accepted'=>'#2980b9','assembling'=>'#8e44ad','shipped'=>'#16a085','delivered'=>'#27ae60','cancelled'=>'#c0392b'];
$statusLabels = ['new'=>'Новый','accepted'=>'Принят','assembling'=>'Комплектуется','shipped'=>'Отгружен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Личный кабинет — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="page-banner">
    <div class="container">
        <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Главная</a> → <span>Личный кабинет</span></div>
        <h1>Личный кабинет</h1>
    </div>
</div>

<div class="container account-layout">
    <!-- БОКОВАЯ ПАНЕЛЬ -->
    <aside class="account-sidebar">
        <div class="account-avatar">👤</div>
        <div class="account-name"><?= e($user['last_name'].' '.$user['first_name']) ?></div>
        <div class="account-email"><?= e($user['email']) ?></div>
        <div class="account-role-badge">Клиент</div>
        <nav class="account-nav">
            <a href="#orders">📦 Мои заказы</a>
            <a href="#profile">✏ Редактировать профиль</a>
            <a href="#password">🔒 Сменить пароль</a>
            <a href="<?= BASE_URL ?>logout.php" style="color:var(--danger)">🚪 Выйти</a>
        </nav>
    </aside>

    <!-- ОСНОВНАЯ ЧАСТЬ -->
    <main>
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= e($msg) ?></div>
        <?php endif; ?>

        <!-- ЗАКАЗЫ -->
        <div id="orders" style="margin-bottom:36px">
            <h2 style="font-family:var(--font-h);font-size:22px;color:var(--navy);margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--border)">
                История заказов
            </h2>
            <?php if (empty($orders)): ?>
            <div style="text-align:center;padding:40px;background:#fff;border-radius:8px;border:1px dashed var(--border)">
                <div style="font-size:44px;opacity:.3;margin-bottom:12px">📦</div>
                <p style="color:var(--muted);margin-bottom:16px">Заказов пока нет</p>
                <a href="<?= BASE_URL ?>catalog.php" class="btn-primary">Перейти в каталог</a>
            </div>
            <?php else: ?>
            <?php foreach ($orders as $o): $st=$o['status']; ?>
            <a href="<?= BASE_URL ?>order_view.php?id=<?= $o['id'] ?>" class="order-row" style="display:flex">
                <div style="flex:1">
                    <div class="order-num">Заказ #<?= $o['id'] ?></div>
                    <div class="order-date"><?= date('d.m.Y H:i',strtotime($o['created_at'])) ?> · <?= $o['items_count'] ?> позиций</div>
                </div>
                <div style="display:flex;align-items:center;gap:16px">
                    <span class="status-badge" style="background:<?= $statusColors[$st]??'#666' ?>22;color:<?= $statusColors[$st]??'#666' ?>">
                        <?= $statusLabels[$st]??$st ?>
                    </span>
                    <span style="font-family:var(--font-h);font-size:18px;font-weight:600;color:var(--navy)"><?= fmtPrice($o['total_amount']) ?></span>
                    <span style="color:var(--orange)">→</span>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- РЕДАКТИРОВАНИЕ ПРОФИЛЯ -->
        <div id="profile" style="margin-bottom:28px">
            <div class="checkout-form-card">
                <h2>Редактировать профиль</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Имя *</label>
                            <input type="text" name="first_name" class="form-input" value="<?= e($user['first_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Фамилия *</label>
                            <input type="text" name="last_name" class="form-input" value="<?= e($user['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" class="form-input" value="<?= e($user['phone']??'') ?>" placeholder="+7 (___) ___-__-__">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" class="form-input" value="<?= e($user['email']) ?>" disabled style="opacity:.6">
                        <small style="color:var(--muted);font-size:12px">Email изменить нельзя — обратитесь к администратору</small>
                    </div>
                    <button type="submit" class="btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>

        <!-- СМЕНА ПАРОЛЯ -->
        <div id="password">
            <div class="checkout-form-card">
                <h2>Сменить пароль</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Текущий пароль</label>
                        <input type="password" name="old_password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label>Новый пароль (мин. 6 символов)</label>
                        <input type="password" name="new_password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label>Повторите новый пароль</label>
                        <input type="password" name="new_password2" class="form-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-primary">Изменить пароль</button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
