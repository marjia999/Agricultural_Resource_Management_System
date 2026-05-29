<?php
session_start();
include '../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is client (not admin)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info for greeting
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Fix: Use correct column names from your database schema
// service_requests table has: user_id (not client_id), request_status (not status), created_at (not request_date)
$total_requests_query = "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status IN ('Approved','Processing','Delivered')";
$total_requests_result = mysqli_query($conn, $total_requests_query);
$total_requests = $total_requests_result ? mysqli_fetch_assoc($total_requests_result)['count'] : 0;

$pending_requests_query = "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Pending'";
$pending_requests_result = mysqli_query($conn, $pending_requests_query);
$pending_requests = $pending_requests_result ? mysqli_fetch_assoc($pending_requests_result)['count'] : 0;

$approved_requests_query = "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status IN ('Approved','Processing','Delivered')";
$approved_requests_result = mysqli_query($conn, $approved_requests_query);
$approved_requests = $approved_requests_result ? mysqli_fetch_assoc($approved_requests_result)['count'] : 0;

$completed_requests_query = "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Returned'";
$completed_requests_result = mysqli_query($conn, $completed_requests_query);
$completed_requests = $completed_requests_result ? mysqli_fetch_assoc($completed_requests_result)['count'] : 0;

// Fix: payments table has payment_status (not status)
$pending_payments_query = "SELECT COUNT(*) as count FROM payments WHERE user_id = $user_id AND payment_status = 'Pending'";
$pending_payments_result = mysqli_query($conn, $pending_payments_query);
$pending_payments = $pending_payments_result ? mysqli_fetch_assoc($pending_payments_result)['count'] : 0;

// Fix: resources table has type and status columns
$machinery_count_query = "SELECT COUNT(*) as count FROM resources WHERE type = 'Tractor' AND status = 'Available'";
$machinery_count_result = mysqli_query($conn, $machinery_count_query);
$machinery_count = $machinery_count_result ? mysqli_fetch_assoc($machinery_count_result)['count'] : 0;

// For storage and equipment - adjust based on your actual categories
$storage_count_query = "SELECT COUNT(*) as count FROM resources WHERE type IN ('Post Harvest', 'Harvesting') AND status = 'Available'";
$storage_count_result = mysqli_query($conn, $storage_count_query);
$storage_count = $storage_count_result ? mysqli_fetch_assoc($storage_count_result)['count'] : 0;

$equipment_count_query = "SELECT COUNT(*) as count FROM resources WHERE type IN ('Fertilizer Dispenser', 'Produce Sorter', 'Irrigation') AND status = 'Available'";
$equipment_count_result = mysqli_query($conn, $equipment_count_query);
$equipment_count = $equipment_count_result ? mysqli_fetch_assoc($equipment_count_result)['count'] : 0;

$total_available_query = "SELECT COUNT(*) as count FROM resources WHERE status = 'Available'";
$total_available_result = mysqli_query($conn, $total_available_query);
$total_available = $total_available_result ? mysqli_fetch_assoc($total_available_result)['count'] : 0;

// Fix: Use correct column names: created_at instead of request_date
$recent_requests_query = "SELECT sr.*, r.name as resource_name, r.type as resource_type 
                          FROM service_requests sr 
                          LEFT JOIN resources r ON sr.resource_id = r.id 
                          WHERE sr.user_id = $user_id AND sr.request_status IN ('Approved','Processing','Delivered') 
                          ORDER BY sr.created_at DESC 
                          LIMIT 5";
$recent_requests = mysqli_query($conn, $recent_requests_query);

// Check if query failed
if (!$recent_requests) {
    $recent_requests = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - AgriRMS</title>
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

        /* Welcome Hero Section - Premium Design */
        .welcome-hero {
            background: linear-gradient(135deg, #1B4F2B 0%, #0d3b1a 100%);
            padding: 2.5rem 5%;
            position: relative;
            overflow: hidden;
        }

        /* Decorative elements */
        .welcome-hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,140,66,0.15), transparent);
            border-radius: 50%;
            pointer-events: none;
        }

        .welcome-hero::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -5%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(255,215,0,0.08), transparent);
            border-radius: 50%;
            pointer-events: none;
        }

        .welcome-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* Welcome Top Section */
        .welcome-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .welcome-greeting {
            flex: 1;
        }

        .greeting-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,140,66,0.25);
            backdrop-filter: blur(10px);
            color: #FFD966;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }

        .greeting-badge i {
            font-size: 0.7rem;
        }

        .welcome-greeting h1 {
            font-size: 2.2rem;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .welcome-greeting h1 span {
            color: #FFD966;
            position: relative;
        }

        .welcome-greeting p {
            color: #c8e6d9;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .welcome-date {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(10px);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.3s;
        }

        .welcome-date:hover {
            background: rgba(255,255,255,0.18);
            transform: translateY(-2px);
        }

        .welcome-date i {
            color: #FFD966;
            font-size: 1rem;
        }

        .welcome-date span {
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Stats Grid - Premium Cards */
        .welcome-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .welcome-stat-item {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.2rem;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }

        .welcome-stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .welcome-stat-item:hover::before {
            left: 100%;
        }

        .welcome-stat-item:hover {
            background: rgba(255,255,255,0.12);
            transform: translateY(-4px);
            border-color: rgba(255,140,66,0.5);
        }

        .stat-icon-wrapper {
            width: 55px;
            height: 55px;
            background: rgba(255,140,66,0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
        }

        .stat-icon-wrapper i {
            font-size: 1.6rem;
            color: #FFD966;
        }

        .welcome-stat-item .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.3rem;
            letter-spacing: -1px;
        }

        .welcome-stat-item .stat-label {
            font-size: 0.75rem;
            color: #c8e6d9;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 5%;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        /* Categories Section */
        .categories-section {
            margin-bottom: 2rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .section-title h2 {
            font-size: 1.8rem;
            color: #1B4F2B;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .section-title h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: #FF8C42;
            border-radius: 2px;
        }

        .section-title p {
            color: #666;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .category-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .category-image {
            height: 220px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-image i {
            font-size: 5rem;
            color: #FF8C42;
            opacity: 0.7;
        }

        .category-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(27,79,43,0.7), rgba(13,59,26,0.8));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .category-card:hover .category-overlay {
            opacity: 1;
        }

        .category-overlay span {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            background: #FF8C42;
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
        }

        .category-info {
            padding: 1.2rem;
            text-align: center;
        }

        .category-info h3 {
            color: #1B4F2B;
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
        }

        .category-info p {
            color: #888;
            font-size: 0.8rem;
        }

        .category-badge {
            display: inline-block;
            background: #e8f0e8;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            color: #1B4F2B;
            margin-top: 0.5rem;
        }

        /* Recent Requests Card */
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

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e8f0e8;
            vertical-align: middle;
        }

        th {
            background: #f8f9f8;
            color: #1B4F2B;
            font-weight: 600;
            font-size: 0.8rem;
        }

        td {
            color: #444;
            font-size: 0.85rem;
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
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
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

        /* Buttons */
        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }

        .btn-view:hover {
            background: #138496;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.6rem 1.3rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 0.8rem;
        }

        .empty-state p {
            color: #888;
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

        @media (max-width: 992px) {
            .categories-grid {
                gap: 1rem;
            }
            .category-image {
                height: 180px;
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
            .welcome-hero {
                padding: 1.5rem;
            }
            .welcome-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .welcome-top {
                flex-direction: column;
                text-align: center;
            }
            .welcome-date {
                margin-left: auto;
                margin-right: auto;
            }
            .welcome-greeting h1 {
                font-size: 1.5rem;
            }
            .main-content {
                padding: 1rem;
            }
            .categories-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .category-image {
                height: 200px;
            }
            th, td {
                padding: 8px 6px;
                font-size: 0.75rem;
            }
            .card-header {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .welcome-stats {
                grid-template-columns: 1fr;
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

    <!-- Welcome Hero Section - Premium Design -->
    <div class="welcome-hero">
        <div class="welcome-content">
            <div class="welcome-top">
                <div class="welcome-greeting">
                    <div class="greeting-badge">
                        <i class="fas fa-hand-peace"></i> Welcome Back!
                    </div>
                    <h1>Hello, <span><?php echo htmlspecialchars($user_data['full_name'] ?? $_SESSION['full_name'] ?? 'User'); ?></span></h1>
                    <p>Ready to manage your agricultural resources today?</p>
                </div>
                <div class="welcome-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>

            <!-- Stats Row - Premium Cards -->
            <div class="welcome-stats">
                <div class="welcome-stat-item">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_requests; ?></div>
                    <div class="stat-label">Active Requests</div>
                </div>
                <div class="welcome-stat-item">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $approved_requests; ?></div>
                    <div class="stat-label">Approved/Active</div>
                </div>
                <div class="welcome-stat-item">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-number"><?php echo $completed_requests; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="welcome-stat-item">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-number"><?php echo $pending_payments; ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Categories Section -->
        <div class="categories-section">
            <div class="section-title">
                <h2>Browse Agricultural Resources</h2>
                <p>Choose from our wide range of farming equipment and storage solutions</p>
            </div>
            <div class="categories-grid">
                <a href="request_service.php?type=Tractor" class="category-card">
                    <div class="category-image">
                        <i class="fas fa-tractor"></i>
                        <div class="category-overlay">
                            <span>View All →</span>
                        </div>
                    </div>
                    <div class="category-info">
                        <h3>Tractors</h3>
                        <p>Powerful tractors for all farming needs</p>
                        <span class="category-badge"><?php echo $machinery_count; ?> available</span>
                    </div>
                </a>
                <a href="request_service.php?type=Harvesting" class="category-card">
                    <div class="category-image">
                        <i class="fas fa-seedling"></i>
                        <div class="category-overlay">
                            <span>View All →</span>
                        </div>
                    </div>
                    <div class="category-info">
                        <h3>Harvesting</h3>
                        <p>Harvesters, combines and more</p>
                        <span class="category-badge"><?php echo $storage_count; ?> available</span>
                    </div>
                </a>
                <a href="request_service.php?type=Irrigation" class="category-card">
                    <div class="category-image">
                        <i class="fas fa-water"></i>
                        <div class="category-overlay">
                            <span>View All →</span>
                        </div>
                    </div>
                    <div class="category-info">
                        <h3>Irrigation</h3>
                        <p>Pumps, sprinklers, drip systems</p>
                        <span class="category-badge"><?php echo $equipment_count; ?> available</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Service Requests -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-history"></i>
                    Recent Service Requests
                </h3>
                <a href="my_requests.php" class="btn-view" style="background: #6c757d;">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>
            <div class="table-responsive">
                <?php if($recent_requests && mysqli_num_rows($recent_requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Resource</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($recent_requests)): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['resource_type'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $status_class = 'status-pending';
                                $status_text = $row['request_status'];
                                switch($row['request_status']) {
                                    case 'Pending': $status_class = 'status-pending'; break;
                                    case 'Approved': $status_class = 'status-approved'; break;
                                    case 'Processing': $status_class = 'status-processing'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    case 'Returned': $status_class = 'status-returned'; break;
                                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                                    default: $status_class = 'status-pending';
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>">
                                    <?php echo $row['request_status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No service requests found. Click below to create your first request.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-top: 1.2rem; text-align: center;">
                <a href="request_service.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Create New Service Request
                </a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>