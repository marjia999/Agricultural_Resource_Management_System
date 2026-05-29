<?php
session_start();
include '../database.php';
include '../includes/security.php';
include '../includes/notifications.php';

setSecurityHeaders();
ensureNotificationsTable($conn);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Update request status
if (isset($_POST['update_status'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        header("Location: service_requests.php?error=csrf");
        exit();
    }

    $request_id = (int)($_POST['request_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed_status = ['Pending', 'Approved', 'Processing', 'Delivered', 'Returned', 'Cancelled'];

    if ($request_id > 0 && in_array($status, $allowed_status, true)) {
        mysqli_begin_transaction($conn);

        $request_stmt = mysqli_prepare($conn, "SELECT user_id, resource_id, quantity, request_status FROM service_requests WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($request_stmt, 'i', $request_id);
        mysqli_stmt_execute($request_stmt);
        $request_result = mysqli_stmt_get_result($request_stmt);
        $request_data = mysqli_fetch_assoc($request_result);

        if ($request_data) {
            $request_user_id = (int)$request_data['user_id'];
            $resource_id = (int)$request_data['resource_id'];
            $requested_qty = (int)$request_data['quantity'];
            $old_status = $request_data['request_status'];

            $reserve_statuses = ['Processing', 'Delivered'];
            $was_reserved = in_array($old_status, $reserve_statuses, true);
            $should_reserve = in_array($status, $reserve_statuses, true);

            if (!$was_reserved && $should_reserve) {
                $stock_stmt = mysqli_prepare($conn, "SELECT quantity, status FROM resources WHERE id = ? FOR UPDATE");
                mysqli_stmt_bind_param($stock_stmt, 'i', $resource_id);
                mysqli_stmt_execute($stock_stmt);
                $stock = mysqli_fetch_assoc(mysqli_stmt_get_result($stock_stmt));

                if (!$stock || $stock['status'] === 'Under Maintenance' || (int)$stock['quantity'] < $requested_qty) {
                    mysqli_rollback($conn);
                    header("Location: service_requests.php?error=stock_unavailable");
                    exit();
                }

                $new_quantity = max(((int)$stock['quantity']) - $requested_qty, 0);
                $new_status = $new_quantity <= 0 ? 'Rented' : 'Available';
                $resource_stmt = mysqli_prepare($conn, "UPDATE resources SET quantity = ?, status = ? WHERE id = ?");
                mysqli_stmt_bind_param($resource_stmt, 'isi', $new_quantity, $new_status, $resource_id);
                mysqli_stmt_execute($resource_stmt);
            } elseif ($was_reserved && !$should_reserve && in_array($status, ['Returned', 'Cancelled'], true)) {
                $resource_stmt = mysqli_prepare($conn, "UPDATE resources SET quantity = quantity + ?, status = CASE WHEN quantity + ? > 0 THEN 'Available' ELSE status END WHERE id = ? AND status != 'Under Maintenance'");
                mysqli_stmt_bind_param($resource_stmt, 'iii', $requested_qty, $requested_qty, $resource_id);
                mysqli_stmt_execute($resource_stmt);
            }

            if ($status === 'Approved') {
                $admin_id = (int)$_SESSION['user_id'];
                $update_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, 'sii', $status, $admin_id, $request_id);
            } else {
                $update_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, 'si', $status, $request_id);
            }
            mysqli_stmt_execute($update_stmt);

            mysqli_commit($conn);

            if ($old_status !== $status) {
                createNotification(
                    $conn,
                    $request_user_id,
                    'Request status updated',
                    'Your request #' . $request_id . ' status changed from ' . $old_status . ' to ' . $status . '.',
                    $status === 'Cancelled' ? 'warning' : 'success',
                    'view_request.php?id=' . $request_id
                );
            }
        } else {
            mysqli_rollback($conn);
        }
    }

    header("Location: service_requests.php");
    exit();
}

// Get all service requests with correct column names
$requests = mysqli_query($conn, "SELECT sr.*, u.full_name, u.email, u.phone 
                                FROM service_requests sr 
                                JOIN users u ON sr.user_id = u.id 
                                ORDER BY sr.created_at DESC");

// Calculate statistics
$total_requests_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM service_requests");
$total_requests = $total_requests_query ? mysqli_fetch_assoc($total_requests_query)['total'] : 0;

$pending_query = mysqli_query($conn, "SELECT COUNT(*) as pending FROM service_requests WHERE request_status = 'Pending'");
$pending = $pending_query ? mysqli_fetch_assoc($pending_query)['pending'] : 0;

$approved_query = mysqli_query($conn, "SELECT COUNT(*) as approved FROM service_requests WHERE request_status = 'Approved'");
$approved = $approved_query ? mysqli_fetch_assoc($approved_query)['approved'] : 0;

$delivered_query = mysqli_query($conn, "SELECT COUNT(*) as delivered FROM service_requests WHERE request_status = 'Delivered'");
$delivered = $delivered_query ? mysqli_fetch_assoc($delivered_query)['delivered'] : 0;

$completed_query = mysqli_query($conn, "SELECT COUNT(*) as completed FROM service_requests WHERE request_status = 'Returned'");
$completed = $completed_query ? mysqli_fetch_assoc($completed_query)['completed'] : 0;

$cancelled_query = mysqli_query($conn, "SELECT COUNT(*) as cancelled FROM service_requests WHERE request_status = 'Cancelled'");
$cancelled = $cancelled_query ? mysqli_fetch_assoc($cancelled_query)['cancelled'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests - AgriRMS</title>
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
            margin-bottom: 2rem;
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

        .page-header p {
            color: #666;
            margin-top: 0.5rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,140,66,0.1);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }

        .stat-icon i {
            font-size: 1.2rem;
            color: #FF8C42;
        }

        .stat-card h3 {
            font-size: 1.5rem;
            color: #1B4F2B;
            font-weight: 700;
        }

        .stat-card p {
            color: #666;
            font-size: 0.7rem;
            font-weight: 500;
        }

        /* Card */
        .card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f4f0;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h3 {
            color: #1B4F2B;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 i {
            color: #FF8C42;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9f8;
            color: #1B4F2B;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 2px solid #e8f0e8;
        }

        td {
            padding: 14px 10px;
            text-align: left;
            border-bottom: 1px solid #e8f0e8;
            vertical-align: middle;
            font-size: 0.8rem;
            color: #444;
        }

        tr:hover {
            background: #fafbfa;
        }

        /* Status Badges */
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-returned { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e0e8e0;
            background: white;
            font-size: 0.75rem;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .status-select:focus {
            outline: none;
            border-color: #FF8C42;
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
            transition: 0.2s;
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

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .table-responsive {
                overflow-x: auto;
            }
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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .page-header h1 {
                font-size: 1.5rem;
            }
            .action-buttons {
                flex-direction: column;
                align-items: flex-start;
            }
            th, td {
                font-size: 0.7rem;
                padding: 8px 6px;
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
        <div class="page-header">
            <h1>
                <i class="fas fa-clipboard-list"></i>
                Service Requests
            </h1>
            <p>Manage and track all customer service requests</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <h3><?php echo $total_requests; ?></h3>
                <p>Total</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <h3><?php echo $pending; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <h3><?php echo $approved; ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-truck"></i></div>
                <h3><?php echo $delivered; ?></h3>
                <p>Delivered</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <h3><?php echo $completed; ?></h3>
                <p>Completed</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                <h3><?php echo $cancelled; ?></h3>
                <p>Cancelled</p>
            </div>
        </div>

        <!-- All Requests Table -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    All Service Requests
                </h3>
                <span style="font-size: 0.7rem; color: #888;">
                    <i class="fas fa-database"></i> <?php echo $total_requests; ?> requests found
                </span>
            </div>
            <div class="table-responsive">
                <?php if($requests && mysqli_num_rows($requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Resource</th>
                            <th>Rental Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($requests)): 
                            $resource_query = mysqli_query($conn, "SELECT name, model FROM resources WHERE id = " . $row['resource_id']);
                            $resource = mysqli_fetch_assoc($resource_query);
                        ?>
                        <tr>
                            <!-- Request ID -->
                            <td>
                                <strong style="color: #1B4F2B;">#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                             </td

                            <!-- Client Info -->
                            <td>
                                <div>
                                    <span style="font-weight: 600; color: #1B4F2B;"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    <br>
                                    <span style="font-size: 0.7rem; color: #888;"><?php echo htmlspecialchars($row['email']); ?></span>
                                </div>
                             </td

                            <!-- Resource -->
                            <td>
                                <?php echo htmlspecialchars($resource['name'] ?? 'N/A'); ?>
                                <br>
                                <span style="font-size: 0.7rem; color: #888;">Model: <?php echo htmlspecialchars($resource['model'] ?? 'N/A'); ?></span>
                             </td

                            <!-- Rental Details -->
                            <td>
                                <div>
                                    <span><i class="fas fa-calendar-alt"></i> <?php echo $row['rental_duration']; ?></span>
                                    <br>
                                    <span style="font-size: 0.7rem; color: #888;">
                                        <i class="fas fa-box"></i> Qty: <?php echo $row['quantity']; ?> 
                                    </span>
                                    <br>
                                    <span style="font-size: 0.7rem; color: #888;">
                                        <?php echo date('d M', strtotime($row['start_date'])); ?> - <?php echo date('d M', strtotime($row['end_date'])); ?>
                                    </span>
                                </div>
                             </td

                            <!-- Amount -->
                            <td style="font-weight: 700; color: #FF8C42;">
                                ৳ <?php echo number_format($row['total_cost'], 2); ?>
                             </td

                            <!-- Status -->
                            <td>
                                <?php
                                $status_class = 'status-pending';
                                switch($row['request_status']) {
                                    case 'Pending': $status_class = 'status-pending'; break;
                                    case 'Approved': $status_class = 'status-approved'; break;
                                    case 'Processing': $status_class = 'status-approved'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    case 'Returned': $status_class = 'status-returned'; break;
                                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $row['request_status']; ?></span>
                             </td

                            <!-- Request Date -->
                            <td>
                                <span style="font-size: 0.75rem;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                             </td

                            <!-- Actions -->
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <select name="status" class="status-select">
                                            <option value="Pending" <?php echo $row['request_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Approved" <?php echo $row['request_status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="Processing" <?php echo $row['request_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Delivered" <?php echo $row['request_status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Returned" <?php echo $row['request_status'] == 'Returned' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $row['request_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">
                                            <i class="fas fa-sync-alt"></i> Update
                                        </button>
                                    </form>
                                    <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                             </td
                         </tr
                        <?php endwhile; ?>
                    </tbody>
                 </table
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No service requests found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved.</p>
    </footer>
</body>
</html>