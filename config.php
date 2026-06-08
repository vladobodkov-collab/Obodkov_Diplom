<?php
// config.php — подключение к БД, сессии, хелперы
// ─────────────────────────────────────────────

// Автоопределение BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Всё что до /admin/ или до имени php-файла в корне
$script   = $_SERVER['SCRIPT_NAME'] ?? '/motors/index.php';
// Вычисляем корень проекта (папка motors/)
if (preg_match('#^(/.*?/motors/)#', $script, $m)) {
    $base = $m[1];
} elseif (preg_match('#^(/motors/)#', $script, $m)) {
    $base = $m[1];
} else {
    $base = '/motors/';
}
define('BASE_URL', $protocol . '://' . $host . $base);
// Меняйте число после смены style.css — браузер подтянет новый файл
define('ASSET_VER', '7');
define('BASE_PATH', rtrim(str_replace('\\','/',dirname(__DIR__ . '/x')),'/') . '/');
// Путь к корню проекта на диске (для include)
define('ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// ─── БД ───────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'motors_komplektaciya');
define('DB_USER',    'root');    // ← изменить если нужно
define('DB_PASS',    '');        // ← изменить если нужно
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ─── Сессия ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Хелперы ролей ────────────────────────────
function isLoggedIn(): bool  { return !empty($_SESSION['user_id']); }
function currentUser(): ?array { return $_SESSION['user'] ?? null; }
function hasRole(string $r): bool { return ($_SESSION['user']['role'] ?? '') === $r; }
function isAdmin(): bool    { return hasRole('admin'); }
function isEmployee(): bool { return hasRole('employee') || hasRole('admin'); }

function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: '.BASE_URL.'login.php'); exit; }
}
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) { header('Location: '.BASE_URL.'index.php'); exit; }
}
function requireEmployee(): void {
    requireLogin();
    if (!isEmployee()) { header('Location: '.BASE_URL.'index.php'); exit; }
}

// ─── Утилиты ──────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** CSS/JS с версией — обход кэша браузера */
function asset(string $file): string {
    return BASE_URL . ltrim($file, '/') . '?v=' . ASSET_VER;
}

function fmtPrice(float $v): string {
    return number_format($v, 0, '.', ' ') . ' ₽';
}

// Количество товаров в корзине (кешируется в сессии)
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    if (!isset($_SESSION['cart_count'])) {
        try {
            $s = getDB()->prepare('SELECT COALESCE(SUM(qty),0) FROM cart WHERE user_id=?');
            $s->execute([$_SESSION['user_id']]);
            $_SESSION['cart_count'] = (int)$s->fetchColumn();
        } catch (Exception $e) { return 0; }
    }
    return $_SESSION['cart_count'];
}
function resetCartCount(): void { unset($_SESSION['cart_count']); }
