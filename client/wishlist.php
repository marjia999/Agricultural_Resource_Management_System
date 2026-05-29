<?php
session_start();
include '../database.php';
include '../includes/security.php';

setSecurityHeaders();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS resource_wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resource_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_wishlist_user_resource (user_id, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    if (validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $resource_id = (int)($_POST['resource_id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM resource_wishlist WHERE user_id = ? AND resource_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $resource_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: wishlist.php');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT r.* FROM resource_wishlist rw JOIN resources r ON rw.resource_id = r.id WHERE rw.user_id = ? ORDER BY rw.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = [];
while ($result && ($row = mysqli_fetch_assoc($result))) {
    $items[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Wishlist</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:Inter,sans-serif;margin:0;background:#f5f7f5;color:#1a2e1f}.wrap{max-width:1000px;margin:2rem auto;padding:0 1rem}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem}.card{background:#fff;border:1px solid #e7f0e7;border-radius:14px;padding:1rem}.btn{border:none;background:#1B4F2B;color:#fff;padding:.5rem .9rem;border-radius:8px;cursor:pointer;text-decoration:none}.top{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}</style></head><body>
<div class="wrap">
<div class="top"><h1><i class="fas fa-heart"></i> My Wishlist</h1><div><a class="btn" href="resources.php">Browse Resources</a> <a class="btn" href="notifications.php">Notifications</a></div></div>
<div class="grid">
<?php if (!$items): ?><div class="card">No wishlist items yet.</div><?php endif; ?>
<?php foreach ($items as $item): ?>
<div class="card">
<h3><?php echo htmlspecialchars($item['name']); ?></h3>
<div><?php echo htmlspecialchars($item['model']); ?></div>
<div style="margin:.4rem 0;">৳ <?php echo number_format((float)$item['daily_rate'],2); ?>/day</div>
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem;">
<a class="btn" href="request_service.php?resource_id=<?php echo (int)$item['id']; ?>">Request</a>
<form method="post" style="margin:0;">
<?php echo csrfInput(); ?>
<input type="hidden" name="resource_id" value="<?php echo (int)$item['id']; ?>">
<button class="btn" name="remove" type="submit" style="background:#dc3545;">Remove</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div></div></body></html>
