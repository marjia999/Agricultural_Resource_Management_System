<?php
session_start();
include '../database.php';
include '../includes/security.php';
include '../includes/notifications.php';
include '../includes/mailer.php';

setSecurityHeaders();
ensureNotificationsTable($conn);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Update payment status
if (isset($_POST['update_payment'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        header("Location: billing.php?error=csrf");
        exit();
    }

    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? '';
    $allowed_status = ['Pending', 'Paid', 'Partial', 'Failed', 'Refunded'];

    if ($payment_id > 0 && in_array($payment_status, $allowed_status, true)) {
        $payment_date = date('Y-m-d H:i:s');

        $update_stmt = mysqli_prepare($conn, "UPDATE payments SET payment_status = ?, payment_date = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, 'ssi', $payment_status, $payment_date, $payment_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $payment_stmt = mysqli_prepare($conn, "SELECT p.booking_id, p.user_id, p.total_amount, u.email FROM payments p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        mysqli_stmt_bind_param($payment_stmt, 'i', $payment_id);
        mysqli_stmt_execute($payment_stmt);
        $payment_result = mysqli_stmt_get_result($payment_stmt);
        $payment = $payment_result ? mysqli_fetch_assoc($payment_result) : null;
        mysqli_stmt_close($payment_stmt);

        if ($payment && $payment['booking_id']) {
            $booking_id = (int)$payment['booking_id'];
            $request_update = mysqli_prepare($conn, "UPDATE service_requests SET payment_status = ? WHERE id = ?");
            mysqli_stmt_bind_param($request_update, 'si', $payment_status, $booking_id);
            mysqli_stmt_execute($request_update);
            mysqli_stmt_close($request_update);
        }

        if ($payment) {
            $user_id = (int)$payment['user_id'];
            $invoiceUrl = appUrl('invoice.php?payment_id=' . $payment_id);
            createNotification($conn, $user_id, 'Payment status updated', 'Payment #' . $payment_id . ' is now marked as ' . $payment_status . '.', $payment_status === 'Paid' ? 'success' : 'info', '../invoice.php?payment_id=' . $payment_id);

            if (!empty($payment['email']) && filter_var($payment['email'], FILTER_VALIDATE_EMAIL)) {
                $subject = 'AgriRMS Invoice Update #' . $payment_id;
                $message = "Your payment status is now {$payment_status}.\nInvoice: {$invoiceUrl}\nAmount: ৳" . number_format((float)$payment['total_amount'], 2) . "\n\nThank you for using AgriRMS.";
                sendPlatformEmail((string)$payment['email'], $subject, $message);
            }
        }
    }

    header("Location: billing.php?success=updated");
    exit();
}

// Get all payments with booking details
$payments_query = "SELECT p.*, u.full_name, u.email, u.phone, 
                  sr.id as request_id, sr.resource_id, sr.rental_duration, 
                  sr.start_date, sr.end_date, sr.total_cost as rental_total,
                  r.name as resource_name, r.model
                  FROM payments p
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN service_requests sr ON p.booking_id = sr.id
                  LEFT JOIN resources r ON p.resource_id = r.id
                  ORDER BY p.created_at DESC";
$payments = mysqli_query($conn, $payments_query);

// Calculate statistics
$total_payments_query = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM payments WHERE payment_status = 'Paid'");
$total_payments = $total_payments_query ? mysqli_fetch_assoc($total_payments_query)['total'] : 0;

$pending_payments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Pending'");
$pending_payments = $pending_payments_query ? mysqli_fetch_assoc($pending_payments_query)['count'] : 0;

$paid_payments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Paid'");
$paid_payments = $paid_payments_query ? mysqli_fetch_assoc($paid_payments_query)['count'] : 0;

$partial_payments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Partial'");
$partial_payments = $partial_payments_query ? mysqli_fetch_assoc($partial_payments_query)['count'] : 0;

// Get monthly revenue for chart
$monthly_revenue = mysqli_query($conn, "SELECT 
                                        MONTH(payment_date) as month,
                                        YEAR(payment_date) as year,
                                        SUM(total_amount) as revenue
                                        FROM payments 
                                        WHERE payment_status = 'Paid' 
                                        AND payment_date IS NOT NULL
                                        GROUP BY YEAR(payment_date), MONTH(payment_date)
                                        ORDER BY year DESC, month DESC
                                        LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - AgriRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7f5;
            color: #1a2e1f;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: #1B4F2B;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo h2 {
            color: #FF8C42;
            font-size: 1.5rem;
        }

        .logo p {
            color: #f0f7f0;
            font-size: 0.75rem;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #FFD966 !important;
            transform: translateY(-1px);
        }

        .btn-logout {
            background: #dc3545;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
            transition: 0.3s;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 5%;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #FF8C42;
            font-size: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,140,66,0.1);
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .stat-icon i {
            font-size: 2rem;
            color: #FF8C42;
        }

        .stat-card h3 {
            font-size: 2rem;
            color: #1B4F2B;
            margin-bottom: 0.3rem;
        }

        .stat-card p {
            color: #666;
            font-size: 0.85rem;
        }

        .stat-card .small-text {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.3rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 24px;
            padding: 1.8rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f4f0;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h3 {
            color: #1B4F2B;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h3 i {
            color: #FF8C42;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e8f0e8;
            vertical-align: middle;
        }

        th {
            background: #f8f9f8;
            color: #1B4F2B;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        td {
            color: #444;
            font-size: 0.9rem;
        }

        tr:hover {
            background: #fafbfa;
        }

        /* Status Badges */
        .status {
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-partial {
            background: #cce5ff;
            color: #004085;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        /* Payment Update Form */
        .payment-update-form {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e0e8e0;
            background: white;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-update {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            border: none;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-update:hover {
            background: #e67e22;
            transform: scale(1.02);
        }

        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view:hover {
            background: #138496;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #888;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .amount {
            font-weight: 700;
            color: #1B4F2B;
        }

        .amount-paid {
            color: #28a745;
            font-weight: 600;
        }

        .amount-due {
            color: #dc3545;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: #0d2b18;
            color: white;
            padding: 2rem 5%;
            text-align: center;
            margin-top: auto;
        }

        .footer p {
            color: #c0ddc0;
            font-size: 0.85rem;
        }

        .footer p i {
            color: #FFD966;
        }

        /* Revenue Section */
        .revenue-summary {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
        }

        .revenue-summary h3 {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .revenue-amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: #FFD966;
        }

        .revenue-period {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                justify-content: center;
            }
            .main-content {
                padding: 1rem;
            }
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            th, td {
                font-size: 0.8rem;
                padding: 8px 6px;
            }
            .payment-update-form {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .card-header {
                flex-direction: column;
                text-align: center;
            }
            .revenue-amount {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <h2><i class="fas fa-leaf"></i> AgriRMS</h2>
            <p>Agricultural Resource Management System</p>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="resources.php">Resources</a>
            <a href="service_requests.php">Requests</a>
            <a href="logistics.php">Logistics</a>
            <a href="billing.php">Billing</a>
            <a href="clients.php">Clients</a>
            <a href="reports.php">Reports</a>
            <a href="notifications.php">Notifications</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <?php if(isset($_GET['success']) && $_GET['success'] == 'updated'): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            Payment status updated successfully!
        </div>
        <?php endif; ?>

        <!-- Revenue Summary -->
        <div class="revenue-summary">
            <h3><i class="fas fa-chart-line"></i> Total Revenue</h3>
            <div class="revenue-amount">৳ <?php echo number_format($total_payments, 2); ?></div>
            <div class="revenue-period">Total collected from all payments</div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3><?php echo $paid_payments + $pending_payments + $partial_payments; ?></h3>
                <p>Total Transactions</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?php echo $paid_payments; ?></h3>
                <p>Paid</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?php echo $pending_payments; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-adjust"></i>
                </div>
                <h3><?php echo $partial_payments; ?></h3>
                <p>Partial</p>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                Billing & Payment Management
            </h1>
        </div>

        <!-- All Payments Table -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    All Payment Transactions
                </h3>
                <span style="font-size: 0.8rem; color: #888;">
                    <i class="fas fa-database"></i> Total: <?php echo $paid_payments + $pending_payments + $partial_payments; ?> transactions
                </span>
            </div>
            <div class="table-responsive">
                <?php if($payments && mysqli_num_rows($payments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Resource</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($row['email']); ?></small>
                             </td
                            <td>
                                <?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?>
                                <br><small><?php echo htmlspecialchars($row['model'] ?? ''); ?></small>
                             </td
                            <td class="amount">৳ <?php echo number_format($row['total_amount'], 2); ?> </td
                            <td class="amount-paid">৳ <?php echo number_format($row['paid_amount'], 2); ?> </td
                            <td class="amount-due">৳ <?php echo number_format($row['due_amount'], 2); ?> </td
                            <td>
                                <?php
                                $method_icon = '';
                                switch($row['payment_method']) {
                                    case 'Bkash': $method_icon = '<i class="fab fa-btc"></i>'; break;
                                    case 'Nagad': $method_icon = '<i class="fas fa-mobile-alt"></i>'; break;
                                    case 'Rocket': $method_icon = '<i class="fas fa-rocket"></i>'; break;
                                    default: $method_icon = '<i class="fas fa-money-bill"></i>';
                                }
                                echo $method_icon . ' ' . htmlspecialchars($row['payment_method']);
                                ?>
                                <?php if($row['transaction_id']): ?>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($row['transaction_id']); ?></small>
                                <?php endif; ?>
                             </td
                            <td>
                                <?php
                                $status_class = 'status-pending';
                                switch($row['payment_status']) {
                                    case 'Paid': $status_class = 'status-paid'; break;
                                    case 'Pending': $status_class = 'status-pending'; break;
                                    case 'Partial': $status_class = 'status-partial'; break;
                                    case 'Failed': $status_class = 'status-failed'; break;
                                    default: $status_class = 'status-pending';
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $row['payment_status']; ?></span>
                             </td
                            <td>
                                <?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '—'; ?>
                                <br><small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
                             </td
                            <td>
                                <form method="POST" class="payment-update-form">
                                    <input type="hidden" name="payment_id" value="<?php echo $row['id']; ?>">
                                    <?php echo csrfInput(); ?>
                                    <select name="payment_status" class="status-select">
                                        <option value="Pending" <?php echo $row['payment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Partial" <?php echo $row['payment_status'] == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                                        <option value="Paid" <?php echo $row['payment_status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Failed" <?php echo $row['payment_status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                    <button type="submit" name="update_payment" class="btn-update">
                                        <i class="fas fa-sync-alt"></i> Update
                                    </button>
                                </form>
                                <?php if($row['booking_id']): ?>
                                <a href="view_request.php?id=<?php echo $row['booking_id']; ?>" class="btn-view" style="margin-top: 5px; display: inline-block;">
                                    <i class="fas fa-eye"></i> View Booking
                                </a>
                                <?php endif; ?>
                                <a href="../invoice.php?payment_id=<?php echo $row['id']; ?>" class="btn-view" style="margin-top: 5px; display: inline-block;">
                                    <i class="fas fa-file-invoice"></i> Invoice
                                </a>
                             </td
                         </tr
                        <?php endwhile; ?>
                    </tbody>
                 </table
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <p>No payment transactions found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Monthly Revenue Chart Section -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    Recent Monthly Revenue
                </h3>
            </div>
            <div class="table-responsive">
                <?php if($monthly_revenue && mysqli_num_rows($monthly_revenue) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Month</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($monthly_revenue)): ?>
                        <tr>
                            <td><?php echo $row['year']; ?></td>
                            <td>
                                <?php 
                                $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                                               'July', 'August', 'September', 'October', 'November', 'December'];
                                echo $month_names[$row['month']];
                                ?>
                            </td>
                            <td class="amount">৳ <?php echo number_format($row['revenue'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                 </table
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-line"></i>
                        <p>No revenue data available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>
