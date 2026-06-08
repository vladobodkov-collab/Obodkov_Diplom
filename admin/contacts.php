<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$pdo = getDB();

// Пометить как прочитанное
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='mark_read') {
    $id = (int)$_POST['id'];
    $pdo->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([$id]);
    header('Location: '.BASE_URL.'admin/contacts.php'); exit;
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
$unread   = array_sum(array_column($messages, 'is_read')==0 ? [1] : [0]);
$unread   = count(array_filter($messages, fn($m)=>!$m['is_read']));
?><!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Сообщения — Панель управления</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
<?php include ROOT.'includes/header.php'; ?>
<div class="admin-wrap">
<?php include __DIR__.'/_sidebar.php'; ?>
<main class="admin-content">
    <h1 class="admin-title">
        Сообщения от клиентов
        <?php if ($unread>0): ?>
        <span style="background:var(--danger);color:#fff;font-size:14px;padding:3px 10px;border-radius:20px;margin-left:10px;font-family:var(--font-b);font-weight:600"><?= $unread ?> новых</span>
        <?php endif; ?>
    </h1>

    <?php if (empty($messages)): ?>
    <div style="text-align:center;padding:48px;background:#fff;border-radius:8px;border:1px dashed var(--border)">
        <div style="font-size:48px;opacity:.3;margin-bottom:12px">✉</div>
        <p style="color:var(--muted)">Сообщений пока нет</p>
    </div>
    <?php else: ?>
    <?php foreach ($messages as $m): ?>
    <div style="background:#fff;border:1px solid <?= $m['is_read']?'var(--border)':'var(--orange)' ?>;border-radius:8px;padding:18px 20px;margin-bottom:12px;opacity:<?= $m['is_read']?'0.8':'1' ?>">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div>
                <div style="font-weight:700;font-size:15px;color:var(--navy)">
                    <?= e($m['name']) ?>
                    <?php if (!$m['is_read']): ?>
                    <span style="background:var(--orange);color:#fff;font-size:10px;padding:2px 8px;border-radius:20px;margin-left:6px;font-weight:600">НОВОЕ</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px">
                    📧 <?= e($m['email']) ?>
                    <?php if ($m['phone']): ?> · 📞 <?= e($m['phone']) ?><?php endif; ?>
                    · <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?>
                </div>
            </div>
            <?php if (!$m['is_read']): ?>
            <form method="POST">
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn-reset" style="font-size:12px;padding:5px 12px">✓ Прочитано</button>
            </form>
            <?php endif; ?>
        </div>
        <div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:6px;font-size:14px;color:var(--text);line-height:1.6">
            <?= nl2br(e($m['message'])) ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
<?php include ROOT.'includes/footer.php'; ?>
</body></html>
