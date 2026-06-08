<?php
require_once __DIR__ . '/config.php';
if (isLoggedIn()) { header('Location: '.BASE_URL.'index.php'); exit; }

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    // ── ВХОД ─────────────────────────────────────────
    if ($action==='login') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password']  ?? '';
        if (!$email || !$pass) {
            $error = 'Заполните все поля.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT u.*,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.is_active=1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($pass, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = ['id'=>$user['id'],'first_name'=>$user['first_name'],'last_name'=>$user['last_name'],'email'=>$user['email'],'role'=>$user['role']];
                $pdo->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$user['id']]);
                header('Location: '.(isEmployee() ? BASE_URL.'admin/index.php' : BASE_URL.'index.php'));
                exit;
            }
            $error = 'Неверный email или пароль.';
        }
    }

    // ── РЕГИСТРАЦИЯ ───────────────────────────────────
    if ($action==='register') {
        $tab       = 'register';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $pass      = $_POST['password']        ?? '';
        $pass2     = $_POST['password2']       ?? '';

        if (!$firstName||!$lastName||!$email||!$pass) { $error='Заполните обязательные поля.'; }
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $error='Некорректный email.'; }
        elseif (strlen($pass)<6)  { $error='Пароль — минимум 6 символов.'; }
        elseif ($pass!==$pass2)   { $error='Пароли не совпадают.'; }
        else {
            $pdo = getDB();
            $ex  = $pdo->prepare('SELECT id FROM users WHERE email=?');
            $ex->execute([$email]);
            if ($ex->fetch()) {
                $error = 'Пользователь с таким email уже существует.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO users (role_id,last_name,first_name,email,phone,password) VALUES (2,?,?,?,?,?)')->execute([$lastName,$firstName,$email,$phone,$hash]);
                $success = 'Регистрация успешна! Войдите в аккаунт.';
                $tab = 'login';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Вход — МОТОРЫ И КОМПЛЕКТАЦИЯ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="auth-page">
  <div class="auth-wrapper">
    <div class="auth-brand">
      <div class="auth-brand-icon">⚙</div>
      <h2>МОТОРЫ И КОМПЛЕКТАЦИЯ</h2>
      <p>Запчасти для двигателей ЯМЗ и ТМЗ</p>
    </div>
    <div class="auth-card">
      <div class="auth-tabs">
        <button class="auth-tab <?= $tab==='login'?'active':'' ?>"    onclick="showTab('login')">Вход</button>
        <button class="auth-tab <?= $tab==='register'?'active':'' ?>" onclick="showTab('register')">Регистрация</button>
      </div>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

      <!-- ВХОД -->
      <div id="tab-login" <?= $tab!=='login'?'style="display:none"':'' ?>>
        <h3 class="auth-title">Войти в аккаунт</h3>
        <form method="POST">
          <input type="hidden" name="action" value="login">
          <div class="form-group"><label>Email</label><input type="email" name="email" class="form-input" placeholder="example@mail.ru" required autofocus></div>
          <div class="form-group"><label>Пароль</label><input type="password" name="password" class="form-input" placeholder="••••••••" required></div>
          <button type="submit" class="btn-primary full-width">Войти</button>
        </form>
        <p class="auth-switch">Нет аккаунта? <a href="#" onclick="showTab('register')">Зарегистрироваться</a></p>
      </div>

      <!-- РЕГИСТРАЦИЯ -->
      <div id="tab-register" <?= $tab!=='register'?'style="display:none"':'' ?>>
        <h3 class="auth-title">Создать аккаунт</h3>
        <form method="POST">
          <input type="hidden" name="action" value="register">
          <div class="form-row-2">
            <div class="form-group"><label>Имя *</label><input type="text" name="first_name" class="form-input" placeholder="Иван" required></div>
            <div class="form-group"><label>Фамилия *</label><input type="text" name="last_name" class="form-input" placeholder="Иванов" required></div>
          </div>
          <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-input" placeholder="example@mail.ru" required></div>
          <div class="form-group"><label>Телефон</label><input type="tel" name="phone" class="form-input" placeholder="+7 (___) ___-__-__"></div>
          <div class="form-group"><label>Пароль * (мин. 6 символов)</label><input type="password" name="password" class="form-input" placeholder="••••••••" required></div>
          <div class="form-group"><label>Повторите пароль *</label><input type="password" name="password2" class="form-input" placeholder="••••••••" required></div>
          <button type="submit" class="btn-primary full-width">Зарегистрироваться</button>
        </form>
        <p class="auth-switch">Есть аккаунт? <a href="#" onclick="showTab('login')">Войти</a></p>
      </div>
    </div>
  </div>
</div>
<?php include ROOT.'includes/footer.php'; ?>
<script>
function showTab(t){
  document.getElementById('tab-login').style.display    = t==='login'    ?'':'none';
  document.getElementById('tab-register').style.display = t==='register' ?'':'none';
  document.querySelectorAll('.auth-tab').forEach((el,i)=>el.classList.toggle('active',(i===0&&t==='login')||(i===1&&t==='register')));
}
</script>
</body></html>
