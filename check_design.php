<?php
/**
 * Проверка: открыть http://localhost/ВАШ_ПУТЬ/motors/check_design.php
 * Покажет, откуда читается проект и какая версия CSS.
 */
require_once __DIR__ . '/config.php';
$cssPath = __DIR__ . '/style.css';
$cssFirst = is_readable($cssPath) ? trim((string) file($cssPath)[0]) : 'файл не найден';
$isNew = str_contains($cssFirst, 'основные стили') || str_contains(@file_get_contents($cssPath, false, null, 0, 200) ?: '', 'hero-panel');
?><!DOCTYPE html>
<html lang="ru"><head><meta charset="UTF-8"><title>Проверка дизайна</title>
<style>body{font-family:sans-serif;max-width:640px;margin:40px auto;padding:20px;line-height:1.6}
.ok{color:#2e8b57}.bad{color:#c0392b}code{background:#f0f0f0;padding:2px 6px}</style></head><body>
<h1>Проверка проекта motors</h1>
<ul>
  <li><b>Папка на диске:</b><br><code><?= e(__DIR__) ?></code></li>
  <li><b>BASE_URL:</b> <code><?= e(BASE_URL) ?></code></li>
  <li><b>Ссылка на CSS:</b> <code><?= e(asset('style.css')) ?></code></li>
  <li><b>Версия ASSET_VER:</b> <?= e(ASSET_VER) ?></li>
  <li><b>Новый style.css:</b>
    <?php if ($isNew): ?><span class="ok">да ✓</span><?php else: ?><span class="bad">нет — старый файл</span><?php endif; ?>
  </li>
  <li><b>Первая строка CSS:</b> <code><?= e($cssFirst) ?></code></li>
</ul>
<p>Откройте главную: <a href="<?= BASE_URL ?>index.php">index.php</a></p>
<p>Если «Новый style.css: нет» — вы смотрите <b>другую копию</b> сайта, не эту папку.</p>
<p><b>Ctrl+F5</b> на главной после проверки.</p>
</body></html>
