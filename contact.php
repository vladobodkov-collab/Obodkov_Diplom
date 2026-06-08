<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $msg = 'error:Заполните обязательные поля: имя, email, сообщение.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'error:Некорректный email.';
    } else {
        $pdo->prepare('INSERT INTO contact_messages (name,email,phone,message) VALUES (?,?,?,?)')
            ->execute([$name,$email,$phone,$message]);
        $msg = 'success:Ваше сообщение отправлено! Мы свяжемся с вами в ближайшее время.';
    }
}
[$msgType,$msgText] = str_contains($msg,':') ? explode(':',$msg,2) : ['',$msg];
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Контакты — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>

<div class="page-banner">
    <div class="container">
        <div class="breadcrumb"><a href="<?= BASE_URL ?>index.php">Главная</a> → <span>Контакты</span></div>
        <h1>Контакты и обратная связь</h1>
    </div>
</div>

<div class="container contact-page-layout">
    <!-- ФОРМА -->
    <div class="contact-form-card">
        <h2>Задать вопрос</h2>
        <?php if ($msgText): ?>
            <div class="alert alert-<?= $msgType ?>"><?= e($msgText) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Имя *</label>
                    <input type="text" name="name" class="form-input" placeholder="Иван Иванов"
                           value="<?= e($_POST['name'] ?? (isLoggedIn() ? $_SESSION['user']['first_name'].' '.$_SESSION['user']['last_name'] : '')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" class="form-input" placeholder="+7 (___) ___-__-__"
                           value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-input" placeholder="example@mail.ru"
                       value="<?= e($_POST['email'] ?? (isLoggedIn() ? $_SESSION['user']['email'] : '')) ?>" required>
            </div>
            <div class="form-group">
                <label>Сообщение *</label>
                <textarea name="message" class="form-input" rows="5"
                          placeholder="Опишите ваш вопрос или запрос на подбор запчасти..." required><?= e($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-primary">Отправить сообщение</button>
        </form>
    </div>

    <!-- КОНТАКТЫ -->
    <div>
        <div class="contact-info-card">
            <h3>Наши контакты</h3>
            <div class="contact-item" style="border-color:rgba(255,255,255,.15);color:rgba(255,255,255,.85)">
                <span class="contact-icon">📍</span>
                <span>Ярославская обл., г. Тутаев,<br>ул. Строителей, 12</span>
            </div>
            <div class="contact-item" style="border-color:rgba(255,255,255,.15);color:rgba(255,255,255,.85)">
                <span class="contact-icon">📞</span>
                <span>+7 (485) 123-45-67</span>
            </div>
            <div class="contact-item" style="border-color:rgba(255,255,255,.15);color:rgba(255,255,255,.85)">
                <span class="contact-icon">✉</span>
                <span>info@motors-k.ru</span>
            </div>
            <div class="contact-item" style="border-color:rgba(255,255,255,.15);color:rgba(255,255,255,.85)">
                <span class="contact-icon">🕐</span>
                <span>Пн–Пт: 8:00–17:00<br>Сб–Вс: выходной</span>
            </div>
        </div>

        <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:20px;margin-top:16px">
            <h3 style="font-family:var(--font-h);font-size:17px;color:var(--navy);margin-bottom:12px">Как нас найти</h3>
            <p style="font-size:14px;color:var(--muted);line-height:1.6">
                От центра Тутаева по ул. Октябрьской до перекрёстка со Строителей,
                затем направо. Склад и офис — серое здание с вывеской «Моторы».
            </p>
            <p style="font-size:14px;color:var(--muted);margin-top:8px">
                Самовывоз заказов: в рабочие дни с 8:00 до 16:30.
            </p>
        </div>
    </div>
</div>

<?php include ROOT.'includes/footer.php'; ?>
</body></html>
