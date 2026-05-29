<?php
session_start();
include '../database.php';
include '../includes/security.php';
include '../includes/notifications.php';

setSecurityHeaders();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
ensureNotificationsTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    if (validateCsrfToken($_POST['csrf_token'] ?? null)) {
        markNotificationsRead($conn, $user_id);
    }
    header('Location: notifications.php');
    exit();
}

$notifications = fetchNotifications($conn, $user_id, 50);
$unread = getUnreadNotificationCount($conn, $user_id);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>My Notifications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:Inter,sans-serif;background:#f5f7f5;margin:0;color:#1a2e1f}.wrap{max-width:950px;margin:2rem auto;padding:0 1rem}.top{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}.btn{background:#1B4F2B;color:#fff;border:none;padding:.6rem 1rem;border-radius:10px;cursor:pointer;text-decoration:none}.badge{background:#FF8C42;color:#fff;padding:.2rem .6rem;border-radius:999px}.list{margin-top:1rem;display:grid;gap:.8rem}.item{background:#fff;border:1px solid #e4ede4;border-radius:14px;padding:1rem}.item.unread{border-left:5px solid #FF8C42}.meta{font-size:.8rem;color:#666}.title{font-weight:700}.links{margin-top:1rem;display:flex;gap:.7rem;flex-wrap:wrap}</style></head><body>
<div class="wrap">
<div class="top"><h1><i class="fas fa-bell"></i> Notifications <span class="badge"><?php echo $unread; ?></span></h1>
<form method="post"><?php echo csrfInput(); ?><button class="btn" name="mark_all_read" type="submit">Mark all as read</button></form></div>
<div class="links"><a class="btn" href="dashboard.php">Dashboard</a><a class="btn" href="payments.php">Payments</a><a class="btn" href="wishlist.php">Wishlist</a></div>
<div class="list">
<?php if (!$notifications): ?><div class="item">No notifications yet.</div><?php endif; ?>
<?php foreach ($notifications as $note): ?>
<div class="item <?php echo (int)$note['is_read'] === 0 ? 'unread' : ''; ?>">
<div class="title"><?php echo htmlspecialchars($note['title']); ?></div>
<div><?php echo htmlspecialchars($note['message']); ?></div>
<div class="meta"><?php echo date('d M Y, h:i A', strtotime($note['created_at'])); ?></div>
<?php if (!empty($note['related_url'])): ?><a href="<?php echo htmlspecialchars($note['related_url']); ?>">Open</a><?php endif; ?>
</div>
<?php endforeach; ?>
</div></div></body></html>
