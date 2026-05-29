<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_pending'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    if ($request_id > 0) {
        $cancel_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = 'Cancelled' WHERE id = ? AND user_id = ? AND request_status = 'Pending'");
        mysqli_stmt_bind_param($cancel_stmt, 'ii', $request_id, $user_id);
        mysqli_stmt_execute($cancel_stmt);
        if (mysqli_stmt_affected_rows($cancel_stmt) > 0) {
            $success = 'Pending request cancelled successfully.';
        }
    }
}

// Get all service requests for this client with correct column names
$requests_query = "SELECT sr.*, r.name as resource_name, r.model, r.daily_rate, r.type as resource_type
                   FROM service_requests sr 
                   LEFT JOIN resources r ON sr.resource_id = r.id 
                   WHERE sr.user_id = $user_id 
                   ORDER BY sr.created_at DESC";
$requests = mysqli_query($conn, $requests_query);

// Calculate statistics
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM service_requests WHERE user_id = $user_id");
$total = mysqli_fetch_assoc($total_query)['total'];

$pending_query = mysqli_query($conn, "SELECT COUNT(*) as pending FROM service_requests WHERE user_id = $user_id AND request_status = 'Pending'");
$pending = mysqli_fetch_assoc($pending_query)['pending'];

$approved_query = mysqli_query($conn, "SELECT COUNT(*) as approved FROM service_requests WHERE user_id = $user_id AND request_status = 'Approved'");
$approved = mysqli_fetch_assoc($approved_query)['approved'];

$completed_query = mysqli_query($conn, "SELECT COUNT(*) as completed FROM service_requests WHERE user_id = $user_id AND request_status = 'Returned'");
$completed = mysqli_fetch_assoc($completed_query)['completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Service Requests - AgriRMS</title>
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

        .main-content {
            flex: 1;
            padding: 2rem 5%;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

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

        /* Stats Cards - Premium */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
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
            width: 55px;
            height: 55px;
            background: rgba(255,140,66,0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: #FF8C42;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            color: #1B4F2B;
            font-weight: 700;
        }

        .stat-info p {
            color: #666;
            font-size: 0.75rem;
        }

        /* Requests Grid - Card Layout */
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .request-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .request-header {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            padding: 1.2rem;
            position: relative;
            color: white;
        }

        .request-id {
            font-size: 0.7rem;
            opacity: 0.8;
            margin-bottom: 0.3rem;
        }

        .request-resource {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .request-model {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .request-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #cce5ff;
            color: #004085;
        }

        .status-processing {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-returned {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .request-body {
            padding: 1.2rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #e8f0e8;
        }

        .info-label {
            font-size: 0.75rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-value {
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .price-row {
            background: #f8f9f8;
            padding: 0.8rem;
            border-radius: 12px;
            margin: 0.8rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-label {
            font-size: 0.7rem;
            color: #888;
        }

        .price-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #FF8C42;
        }

        .date-range {
            background: #f0f7f0;
            padding: 0.5rem;
            border-radius: 10px;
            text-align: center;
            margin: 0.8rem 0;
            font-size: 0.75rem;
            color: #1B4F2B;
        }

        .action-buttons {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .btn-view {
            flex: 1;
            background: #17a2b8;
            color: white;
            padding: 0.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.75rem;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .btn-pay {
            flex: 1;
            background: #28a745;
            color: white;
            padding: 0.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.75rem;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-pay:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 24px;
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

        .btn-create {
            display: inline-block;
            margin-top: 1rem;
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.6rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
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

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                gap: 1rem;
            }
            .requests-grid {
                grid-template-columns: 1fr;
            }
            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
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
            <a href="dashboard.php">Home</a>
            <a href="resources.php">Resources</a>
            <a href="request_service.php">New Request</a>
            <a href="my_requests.php">My Requests</a>
            <a href="payments.php">Payments</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-list-ul"></i>
                My Service Requests
            </h1>
            <p>Track and manage all your agricultural service requests in one place</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total; ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $approved; ?></h3>
                    <p>Approved</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <!-- Requests Grid -->
        <?php if(!empty($success)): ?>
        <div style="background:#d4edda;color:#155724;padding:12px 16px;border-radius:12px;margin-bottom:16px;"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if($requests && mysqli_num_rows($requests) > 0): ?>
        <div class="requests-grid">
            <?php while($row = mysqli_fetch_assoc($requests)): 
                $status_class = 'status-pending';
                switch($row['request_status']) {
                    case 'Pending': $status_class = 'status-pending'; break;
                    case 'Approved': $status_class = 'status-approved'; break;
                    case 'Processing': $status_class = 'status-processing'; break;
                    case 'Delivered': $status_class = 'status-delivered'; break;
                    case 'Returned': $status_class = 'status-returned'; break;
                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                }
            ?>
            <div class="request-card">
                <div class="request-header">
                    <div class="request-id">
                        <i class="fas fa-qrcode"></i> Request #<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div class="request-resource">
                        <?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?>
                    </div>
                    <div class="request-model">
                        <?php echo htmlspecialchars($row['model'] ?? ''); ?>
                    </div>
                    <span class="request-status-badge <?php echo $status_class; ?>">
                        <?php echo $row['request_status']; ?>
                    </span>
                </div>
                <div class="request-body">
                    <div class="info-row">
                        <span class="info-label">
                            <i class="fas fa-calendar-alt"></i> Rental Duration
                        </span>
                        <span class="info-value"><?php echo $row['rental_duration']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">
                            <i class="fas fa-boxes"></i> Quantity
                        </span>
                        <span class="info-value"><?php echo $row['quantity']; ?> unit(s)</span>
                    </div>
                    <div class="date-range">
                        <i class="fas fa-calendar-week"></i> 
                        <?php echo date('d M Y', strtotime($row['start_date'])); ?> 
                        <i class="fas fa-arrow-right"></i> 
                        <?php echo date('d M Y', strtotime($row['end_date'])); ?>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Total Amount</span>
                        <span class="price-amount">৳ <?php echo number_format($row['total_cost'], 2); ?></span>
                    </div>
                    <div class="action-buttons">
                        <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <?php if($row['request_status'] == 'Pending'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this pending request?');">
                                <input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>">
                                <button type="submit" name="cancel_pending" class="btn-pay" style="background:#dc3545; border:none; cursor:pointer;">
                                    <i class="fas fa-times-circle"></i> Cancel
                                </button>
                            </form>
                        <?php elseif($row['request_status'] == 'Returned'): ?>
                            <a href="payments.php?request_id=<?php echo $row['id']; ?>" class="btn-pay">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <p>You haven't made any service requests yet.</p>
            <a href="request_service.php" class="btn-create">
                <i class="fas fa-plus"></i> Create Your First Request
            </a>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>