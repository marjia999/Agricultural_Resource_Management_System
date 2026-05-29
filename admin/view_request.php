<?php
session_start();
include '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($request_id <= 0) {
    header('Location: service_requests.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    $allowed_status = ['Pending', 'Approved', 'Processing', 'Delivered', 'Returned', 'Cancelled'];
    if (in_array($status, $allowed_status, true)) {
        $stmt = mysqli_prepare($conn, 'UPDATE service_requests SET request_status = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $status, $request_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Request status updated.';
        } else {
            $error = 'Failed to update request status.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment'] ?? '');
    if ($comment !== '') {
        $stmt = mysqli_prepare($conn, 'INSERT INTO request_comments (request_id, admin_id, comment) VALUES (?, ?, ?)');
        if ($stmt) {
            $admin_id = (int)$_SESSION['user_id'];
            mysqli_stmt_bind_param($stmt, 'iis', $request_id, $admin_id, $comment);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Note added successfully.';
            } else {
                $error = 'Failed to save note.';
            }
        } else {
            $error = 'Could not save note. Ensure request_comments table exists.';
        }
    }
}

$request_stmt = mysqli_prepare($conn, "SELECT sr.*, u.full_name, u.email, u.phone, u.address AS client_address, r.name AS resource_name, r.model, r.type, r.daily_rate, d.delivery_type, d.location_type FROM service_requests sr JOIN users u ON sr.user_id = u.id JOIN resources r ON sr.resource_id = r.id LEFT JOIN delivery d ON sr.delivery_id = d.id WHERE sr.id = ? LIMIT 1");
mysqli_stmt_bind_param($request_stmt, 'i', $request_id);
mysqli_stmt_execute($request_stmt);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($request_stmt));
if (!$request) {
    header('Location: service_requests.php');
    exit();
}

$payment_stmt = mysqli_prepare($conn, "SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($payment_stmt, 'i', $request_id);
mysqli_stmt_execute($payment_stmt);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($payment_stmt));

$comments_stmt = mysqli_prepare($conn, "SELECT rc.*, u.full_name FROM request_comments rc LEFT JOIN users u ON rc.admin_id = u.id WHERE rc.request_id = ? ORDER BY rc.created_at DESC");
mysqli_stmt_bind_param($comments_stmt, 'i', $request_id);
mysqli_stmt_execute($comments_stmt);
$comments_query = mysqli_stmt_get_result($comments_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?php echo $request_id; ?> - Admin View</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{font-family:Inter,sans-serif;background:#f5f7f5;margin:0;color:#1a2e1f}
        .header{background:#1B4F2B;color:#fff;padding:1rem 5%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
        .header a{color:#fff;text-decoration:none}
        .main{max-width:1100px;margin:0 auto;padding:1.5rem}
        .card{background:#fff;border-radius:16px;padding:1.2rem;margin-bottom:1rem;border:1px solid #e8f0e8}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem}
        .info small{color:#666;display:block}.info strong{display:block;margin-top:.2rem}
        .status{padding:4px 10px;border-radius:999px;background:#cce5ff}
        .btn{background:#FF8C42;color:#fff;border:none;border-radius:8px;padding:.5rem .9rem;cursor:pointer;text-decoration:none;display:inline-block}
        .btn-danger{background:#dc3545}.alert{padding:.7rem 1rem;border-radius:10px;margin-bottom:1rem}.ok{background:#d4edda;color:#155724}.err{background:#f8d7da;color:#721c24}
        textarea,select{width:100%;padding:.7rem;border:1px solid #dbe9db;border-radius:8px}
        @media print {.header,.actions,.no-print{display:none}.main{padding:0}}
    </style>
</head>
<body>
<header class="header no-print">
    <div><strong><i class="fas fa-leaf"></i> AgriRMS</strong></div>
    <div><a href="service_requests.php"><i class="fas fa-arrow-left"></i> Back to Requests</a></div>
</header>
<main class="main">
    <?php if ($success): ?><div class="alert ok"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="card">
        <h2>Service Request #<?php echo str_pad($request['id'], 5, '0', STR_PAD_LEFT); ?></h2>
        <div class="grid">
            <div class="info"><small>Status</small><strong><span class="status"><?php echo htmlspecialchars($request['request_status']); ?></span></strong></div>
            <div class="info"><small>Payment Status</small><strong><?php echo htmlspecialchars($request['payment_status']); ?></strong></div>
            <div class="info"><small>Rental Period</small><strong><?php echo htmlspecialchars($request['start_date']); ?> to <?php echo htmlspecialchars($request['end_date']); ?></strong></div>
            <div class="info"><small>Total</small><strong>৳ <?php echo number_format($request['total_cost'],2); ?></strong></div>
        </div>
    </div>

    <div class="card">
        <h3>Client Information</h3>
        <div class="grid">
            <div class="info"><small>Name</small><strong><?php echo htmlspecialchars($request['full_name']); ?></strong></div>
            <div class="info"><small>Email</small><strong><?php echo htmlspecialchars($request['email']); ?></strong></div>
            <div class="info"><small>Phone</small><strong><?php echo htmlspecialchars($request['phone'] ?? 'N/A'); ?></strong></div>
            <div class="info"><small>Address</small><strong><?php echo htmlspecialchars($request['client_address'] ?? 'N/A'); ?></strong></div>
        </div>
    </div>

    <div class="card">
        <h3>Resource Details</h3>
        <div class="grid">
            <div class="info"><small>Resource</small><strong><?php echo htmlspecialchars($request['resource_name']); ?></strong></div>
            <div class="info"><small>Model</small><strong><?php echo htmlspecialchars($request['model']); ?></strong></div>
            <div class="info"><small>Type</small><strong><?php echo htmlspecialchars($request['type']); ?></strong></div>
            <div class="info"><small>Quantity</small><strong><?php echo (int)$request['quantity']; ?></strong></div>
            <div class="info"><small>Delivery</small><strong><?php echo htmlspecialchars(($request['delivery_type'] ?? 'Standard') . ' ' . ($request['location_type'] ? '(' . $request['location_type'] . ')' : '')); ?></strong></div>
            <div class="info"><small>Delivery Address</small><strong><?php echo htmlspecialchars($request['delivery_address']); ?></strong></div>
        </div>
    </div>

    <div class="card">
        <h3>Payment Information</h3>
        <?php if($payment): ?>
            <div class="grid">
                <div class="info"><small>Payment Method</small><strong><?php echo htmlspecialchars($payment['payment_method'] ?? 'Pending'); ?></strong></div>
                <div class="info"><small>Transaction ID</small><strong><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></strong></div>
                <div class="info"><small>Paid Amount</small><strong>৳ <?php echo number_format($payment['paid_amount'],2); ?></strong></div>
                <div class="info"><small>Due Amount</small><strong>৳ <?php echo number_format($payment['due_amount'],2); ?></strong></div>
            </div>
        <?php else: ?>
            <p>No payment record found for this request.</p>
        <?php endif; ?>
    </div>

    <div class="card no-print">
        <h3>Update Status</h3>
        <form method="POST" class="actions" style="display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;">
            <select name="status" required>
                <?php foreach (['Pending','Approved','Processing','Delivered','Returned','Cancelled'] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo $request['request_status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit" name="update_status"><i class="fas fa-sync-alt"></i> Update</button>
            <button class="btn" type="button" onclick="window.print()"><i class="fas fa-print"></i> Print Invoice</button>
        </form>
    </div>

    <div class="card">
        <h3>Admin Notes / Comments</h3>
        <form method="POST" class="no-print">
            <textarea name="comment" rows="3" placeholder="Add note for internal tracking or client communication..."></textarea>
            <button class="btn" type="submit" name="add_comment" style="margin-top:.5rem;"><i class="fas fa-comment"></i> Add Note</button>
        </form>
        <div style="margin-top:1rem;">
            <?php if($comments_query && mysqli_num_rows($comments_query) > 0): ?>
                <?php while($comment = mysqli_fetch_assoc($comments_query)): ?>
                    <div style="padding:.7rem;border:1px solid #e8f0e8;border-radius:8px;margin-bottom:.6rem;">
                        <strong><?php echo htmlspecialchars($comment['full_name'] ?? 'Admin'); ?></strong>
                        <small style="color:#666;margin-left:.4rem;"><?php echo htmlspecialchars($comment['created_at']); ?></small>
                        <div><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#666;">No notes added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
