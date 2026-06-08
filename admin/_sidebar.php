<?php
$adminPage = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'] ?? [];
$roleLabel = isAdmin() ? 'Администратор' : 'Сотрудник';
?>
<header class="admin-header">
    <div class="admin-header-inner">
        <a href="<?= BASE_URL ?>admin/index.php" class="admin-header-brand">
            <span class="admin-header-logo">МК</span>
            <span class="admin-header-title">
                <strong>Учёт и заказы</strong>
                <small><?= e($user['first_name'] ?? '') ?> · <?= e($roleLabel) ?></small>
            </span>
        </a>

        <nav class="admin-header-nav">
            <a href="<?= BASE_URL ?>admin/index.php"    class="<?= $adminPage==='index.php'?'active':'' ?>">Обзор</a>
            <a href="<?= BASE_URL ?>admin/orders.php"   class="<?= $adminPage==='orders.php'?'active':'' ?>">Заказы</a>
            <a href="<?= BASE_URL ?>admin/products.php" class="<?= $adminPage==='products.php'?'active':'' ?>">Товары</a>
            <a href="<?= BASE_URL ?>admin/arrivals.php" class="<?= $adminPage==='arrivals.php'?'active':'' ?>">Поступления</a>
            <a href="<?= BASE_URL ?>admin/reports.php"  class="<?= $adminPage==='reports.php'?'active':'' ?>">Отчёты</a>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>admin/users.php"    class="<?= $adminPage==='users.php'?'active':'' ?>">Пользователи</a>
            <a href="<?= BASE_URL ?>admin/contacts.php" class="<?= $adminPage==='contacts.php'?'active':'' ?>">Сообщения</a>
            <?php endif; ?>
        </nav>

        <div class="admin-header-actions">
            <a href="<?= BASE_URL ?>index.php" class="admin-header-link">На сайт</a>
            <a href="<?= BASE_URL ?>logout.php" class="admin-header-link admin-header-out">Выйти</a>
        </div>
    </div>
</header>
