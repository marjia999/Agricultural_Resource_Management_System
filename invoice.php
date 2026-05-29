<?php
session_start();
include 'database.php';
include 'includes/security.php';
include 'includes/mailer.php';

setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$is_admin = ($_SESSION['role'] ?? '') === 'Admin';
$payment_id = (int)($_GET['payment_id'] ?? 0);
$email_status = '';
$email_error = '';

if ($payment_id <= 0) {
    http_response_code(400);
    echo 'Invalid invoice request.';
    exit();
}

$query = "SELECT p.*, u.full_name, u.email, u.phone, u.address,
          sr.id AS request_id, sr.start_date, sr.end_date, sr.rental_duration,
          r.name AS resource_name, r.model
          FROM payments p
          JOIN users u ON p.user_id = u.id
          LEFT JOIN service_requests sr ON p.booking_id = sr.id
          LEFT JOIN resources r ON p.resource_id = r.id
          WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $payment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$invoice) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit();
}

if (!$is_admin && (int)$invoice['user_id'] !== $user_id) {
    http_response_code(403);
    echo 'You do not have permission to view this invoice.';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_invoice'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $email_error = 'Invalid request token. Please refresh and try again.';
    } else {
        $recipient = $is_admin
            ? trim($_POST['recipient_email'] ?? '')
            : (string)($invoice['email'] ?? '');
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $email_error = 'Please provide a valid recipient email address.';
        } else {
            $subject = 'AgriRMS Invoice #' . str_pad((string)$invoice['id'], 5, '0', STR_PAD_LEFT);
            $invoiceUrl = appUrl('invoice.php?payment_id=' . (int)$invoice['id']);
            $message = "Hello " . ($invoice['full_name'] ?? 'Client') . ",\n\n" .
                "Your invoice details are ready.\n" .
                "Invoice ID: #" . str_pad((string)$invoice['id'], 5, '0', STR_PAD_LEFT) . "\n" .
                "Status: " . ($invoice['payment_status'] ?? 'Pending') . "\n" .
                "Total: ৳ " . number_format((float)$invoice['total_amount'], 2) . "\n" .
                "Due: ৳ " . number_format((float)$invoice['due_amount'], 2) . "\n\n" .
                "You can view it from your AgriRMS dashboard or directly at: {$invoiceUrl}\n\n" .
                "Regards,\nAgriRMS";

            if (sendPlatformEmail($recipient, $subject, $message)) {
                $email_status = 'Invoice email has been sent successfully.';
            } else {
                $email_error = 'Unable to send invoice email right now.';
            }
        }
    }
}

$home = $is_admin ? 'admin/billing.php' : 'client/payments.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo (int)$invoice['id']; ?> - AgriRMS</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; margin: 0; background: #f4f7f4; color: #17321f; }
        .container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .top { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #e5efe5; padding-bottom: 1rem; }
        .brand h1 { margin: 0; color: #1B4F2B; }
        .meta { text-align: right; }
        .meta h2 { margin: 0 0 .4rem 0; color: #FF8C42; }
        .grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 1rem; margin-top: 1.5rem; }
        .card { border: 1px solid #e5efe5; border-radius: 12px; padding: 1rem; }
        .card h3 { margin: 0 0 .6rem 0; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { border-bottom: 1px solid #e5efe5; padding: .8rem; text-align: left; }
        th { background: #f5faf5; }
        .totals { margin-top: 1rem; margin-left: auto; max-width: 320px; }
        .row { display: flex; justify-content: space-between; padding: .4rem 0; }
        .total { font-weight: 700; font-size: 1.1rem; color: #1B4F2B; border-top: 1px solid #d7e7d7; margin-top: .5rem; padding-top: .6rem; }
        .actions { margin-top: 1.5rem; display: flex; gap: .8rem; }
        .btn { border: none; border-radius: 10px; padding: .7rem 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-print { background: #FF8C42; color: #fff; }
        .btn-back { background: #1B4F2B; color: #fff; }
        .btn-email { background: #2e7d32; color: #fff; }
        .alert { border-radius: 10px; padding: .7rem .9rem; margin-top: 1rem; }
        .alert-ok { background: #e7f6ea; color: #1f6f2f; border: 1px solid #b8e3c2; }
        .alert-err { background: #fdecec; color: #a52a2a; border: 1px solid #f4b7b7; }
        .email-form { margin-top: 1rem; display: flex; gap: .6rem; flex-wrap: wrap; }
        .email-form input { border: 1px solid #d5e7d5; border-radius: 10px; padding: .6rem .7rem; min-width: 260px; }
        @media print { .actions { display: none; } body { background: #fff; } .container { margin: 0; box-shadow: none; } }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } .meta { text-align: left; } }
    </style>
</head>
<body>
<div class="container">
    <div class="top">
        <div class="brand">
            <h1>AgriRMS</h1>
            <div>Agricultural Resource Management System</div>
        </div>
        <div class="meta">
            <h2>Invoice #<?php echo str_pad((string)$invoice['id'], 5, '0', STR_PAD_LEFT); ?></h2>
            <div>Date: <?php echo date('d M Y', strtotime($invoice['payment_date'] ?: $invoice['created_at'])); ?></div>
            <div>Status: <?php echo htmlspecialchars($invoice['payment_status']); ?></div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Billed To</h3>
            <div><?php echo htmlspecialchars($invoice['full_name'] ?? 'Client'); ?></div>
            <div><?php echo htmlspecialchars($invoice['email'] ?? ''); ?></div>
            <div><?php echo htmlspecialchars($invoice['phone'] ?? ''); ?></div>
            <div><?php echo nl2br(htmlspecialchars($invoice['address'] ?? '')); ?></div>
        </div>
        <div class="card">
            <h3>Booking Info</h3>
            <div>Request ID: #<?php echo str_pad((string)($invoice['request_id'] ?? 0), 5, '0', STR_PAD_LEFT); ?></div>
            <div>Resource: <?php echo htmlspecialchars(($invoice['resource_name'] ?? 'N/A') . ' ' . ($invoice['model'] ? '(' . $invoice['model'] . ')' : '')); ?></div>
            <div>Duration: <?php echo htmlspecialchars($invoice['rental_duration'] ?? 'N/A'); ?></div>
            <div>Period: <?php echo $invoice['start_date'] ? date('d M Y', strtotime($invoice['start_date'])) : 'N/A'; ?> - <?php echo $invoice['end_date'] ? date('d M Y', strtotime($invoice['end_date'])) : 'N/A'; ?></div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Description</th>
            <th>Amount (৳)</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Resource rental cost</td>
            <td><?php echo number_format((float)$invoice['resource_cost'], 2); ?></td>
        </tr>
        <tr>
            <td>Delivery cost</td>
            <td><?php echo number_format((float)$invoice['delivery_cost'], 2); ?></td>
        </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal</span><span>৳ <?php echo number_format((float)$invoice['total_amount'], 2); ?></span></div>
        <div class="row"><span>Paid</span><span>৳ <?php echo number_format((float)$invoice['paid_amount'], 2); ?></span></div>
        <div class="row total"><span>Due</span><span>৳ <?php echo number_format((float)$invoice['due_amount'], 2); ?></span></div>
    </div>

    <div class="actions">
        <button class="btn btn-print" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-back" href="<?php echo $home; ?>">Back</a>
    </div>

    <?php if ($email_status): ?><div class="alert alert-ok"><?php echo htmlspecialchars($email_status); ?></div><?php endif; ?>
    <?php if ($email_error): ?><div class="alert alert-err"><?php echo htmlspecialchars($email_error); ?></div><?php endif; ?>

    <form method="post" class="email-form">
        <?php echo csrfInput(); ?>
        <?php if ($is_admin): ?>
            <input type="email" name="recipient_email" value="<?php echo htmlspecialchars($invoice['email'] ?? ''); ?>" placeholder="Recipient email" required>
        <?php endif; ?>
        <button class="btn btn-email" type="submit" name="email_invoice">Email Invoice</button>
    </form>
</div>
</body>
</html>
