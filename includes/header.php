<?php
// includes/header.php — общая шапка (работает и из корня, и из admin/)
// BASE_URL определён в config.php
$currentPage = basename($_SERVER['PHP_SELF']);
$cartQty     = cartCount();
?>
<header class="site-header">
    <div class="header-inner">
        <a href="<?= BASE_URL ?>index.php" class="logo">
            <span class="logo-icon">М</span>
            <div class="logo-text">
                <span class="logo-main">МОТОРЫ И КОМПЛЕКТАЦИЯ</span>
                <span class="logo-sub">ООО · г. Тутаев</span>
            </div>
        </a>

        <nav class="main-nav">
            <a href="<?= BASE_URL ?>index.php"   <?= $currentPage==='index.php'  ?'class="active"':'' ?>>Главная</a>
            <a href="<?= BASE_URL ?>catalog.php" <?= $currentPage==='catalog.php'?'class="active"':'' ?>>Каталог</a>
            <?php if (isEmployee()): ?>
                <a href="<?= BASE_URL ?>admin/index.php" class="nav-admin">Управление</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>contact.php" <?= $currentPage==='contact.php'?'class="active"':'' ?>>Контакты</a>
        </nav>

        <div class="header-actions">
            <form method="GET" action="<?= BASE_URL ?>catalog.php" class="search-wrap">
                <input type="text" name="q" placeholder="Поиск по артикулу..."
                       class="search-input" value="<?= e($_GET['q'] ?? '') ?>">
                <button type="submit" class="search-btn">🔍</button>
            </form>

            <a href="<?= BASE_URL ?>cart.php" class="icon-btn cart-btn" title="Корзина">
                <span>🛒</span>
                <span class="cart-count"><?= $cartQty ?></span>
            </a>

            <?php if (isLoggedIn()): ?>
            <div class="user-menu-wrap">
                <button class="btn-login" onclick="toggleUserMenu()" type="button">
                    👤 <?= e($_SESSION['user']['first_name']) ?>
                </button>
                <div class="user-dropdown" id="userMenu">
                    <a href="<?= BASE_URL ?>account.php">👤 Личный кабинет</a>
                    <?php if (isEmployee()): ?>
                    <a href="<?= BASE_URL ?>admin/index.php">⚙ Панель управления</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>logout.php" style="color:#c0392b">🚪 Выйти</a>
                </div>
            </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>login.php" class="btn-login">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<script>
function toggleUserMenu(){
    var m=document.getElementById('userMenu');
    if(m) m.classList.toggle('open');
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.user-menu-wrap')){
        var m=document.getElementById('userMenu');
        if(m) m.classList.remove('open');
    }
});
</script>
