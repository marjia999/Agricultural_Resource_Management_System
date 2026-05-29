<?php
session_start();
include '../database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Handle status update (auto-update on dropdown change)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['resource_id'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE resources SET status = '$status' WHERE id = $id");
    header("Location: resources.php");
    exit();
}

// Handle price update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_price'])) {
    $id = $_POST['resource_id'];
    $daily_rate = $_POST['daily_rate'];
    mysqli_query($conn, "UPDATE resources SET daily_rate = '$daily_rate' WHERE id = $id");
    header("Location: resources.php");
    exit();
}

// Delete resource
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM resources WHERE id = $id");
    header("Location: resources.php");
    exit();
}

// Get filter values
$filter_type = isset($_GET['type']) && $_GET['type'] != '' ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) && $_GET['status'] != '' ? $_GET['status'] : '';

// Build query with filters
$where_conditions = [];
if ($filter_type) {
    $where_conditions[] = "type = '$filter_type'";
}
if ($filter_status) {
    $where_conditions[] = "status = '$filter_status'";
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$resources = mysqli_query($conn, "SELECT * FROM resources $where_clause ORDER BY type, name ASC");

// Get unique types for filter dropdown
$types_query = mysqli_query($conn, "SELECT DISTINCT type FROM resources ORDER BY type");
$all_types = [];
while($row = mysqli_fetch_assoc($types_query)) {
    $all_types[] = $row['type'];
}

// Get unique statuses for filter dropdown
$statuses_query = mysqli_query($conn, "SELECT DISTINCT status FROM resources ORDER BY status");
$all_statuses = [];
while($row = mysqli_fetch_assoc($statuses_query)) {
    $all_statuses[] = $row['status'];
}

// Calculate statistics (for all resources, not filtered)
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM resources");
$total = mysqli_fetch_assoc($total_query)['total'];

$available_query = mysqli_query($conn, "SELECT COUNT(*) as available FROM resources WHERE status = 'Available'");
$available = mysqli_fetch_assoc($available_query)['available'];

$in_use_query = mysqli_query($conn, "SELECT COUNT(*) as in_use FROM resources WHERE status = 'Rented'");
$in_use = mysqli_fetch_assoc($in_use_query)['in_use'];

$maintenance_query = mysqli_query($conn, "SELECT COUNT(*) as maintenance FROM resources WHERE status = 'Under Maintenance'");
$maintenance = mysqli_fetch_assoc($maintenance_query)['maintenance'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Management - AgriRMS</title>
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

        .btn-add {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.8rem 1.8rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid #e8f0e8;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-label {
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            background: white;
            cursor: pointer;
            min-width: 160px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #FF8C42;
        }

        .active-filters {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .filter-badge {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-clear-filter {
            background: #6c757d;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }

        .btn-clear-filter:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Resource Grid - Card Layout */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            font-size: 1.3rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header h2 i {
            color: #FF8C42;
        }

        .resource-count {
            background: #1B4F2B;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .resource-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .resource-header {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            padding: 1.2rem;
            position: relative;
            color: white;
        }

        .resource-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,140,66,0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
            border: 1px solid rgba(255,140,66,0.5);
        }

        .resource-icon i {
            font-size: 1.8rem;
            color: #FF8C42;
        }

        .resource-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .resource-model {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .resource-type-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .resource-body {
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
        }

        .info-value {
            font-weight: 600;
            color: #1B4F2B;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9f8;
            padding: 0.8rem;
            border-radius: 12px;
            margin: 0.8rem 0;
        }

        .price-label {
            font-size: 0.75rem;
            color: #888;
        }

        .price-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #FF8C42;
        }

        .price-edit-btn {
            background: none;
            border: none;
            color: #FF8C42;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
        }

        .price-edit-btn:hover {
            background: rgba(255,140,66,0.1);
        }

        .price-edit-form {
            display: none;
            margin-top: 0.5rem;
        }

        .price-edit-form.active {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .price-edit-input {
            width: 100px;
            padding: 0.3rem 0.5rem;
            border: 1px solid #e0e8e0;
            border-radius: 8px;
            font-size: 0.75rem;
        }

        .status-update-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.5rem;
        }

        .status-select {
            padding: 0.3rem 0.5rem;
            border-radius: 8px;
            border: 1px solid #e0e8e0;
            background: white;
            font-size: 0.7rem;
            cursor: pointer;
        }

        .btn-update-status {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            border: none;
            font-size: 0.65rem;
            cursor: pointer;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-maintenance {
            flex: 1;
            background: #17a2b8;
            color: white;
            padding: 0.4rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.7rem;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-delete {
            flex: 1;
            background: #dc3545;
            color: white;
            padding: 0.4rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.7rem;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-rented {
            background: #cce5ff;
            color: #004085;
        }

        .status-under_maintenance {
            background: #f8d7da;
            color: #721c24;
        }

        .status-out_of_service {
            background: #f8d7da;
            color: #721c24;
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

        @media (max-width: 900px) {
            .resources-grid {
                grid-template-columns: 1fr;
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
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                justify-content: space-between;
            }
            .resource-header {
                padding: 1rem;
            }
            .resource-body {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-select {
                width: 100%;
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
            <a href="dashboard.php">Dashboard</a>
            <a href="resources.php">Resources</a>
            <a href="service_requests.php">Requests</a>
            <a href="logistics.php">Logistics</a>
            <a href="billing.php">Billing</a>
            <a href="clients.php">Clients</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <!-- Stats Cards (Unchanged) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tractor"></i>
                </div>
                <h3><?php echo $total; ?></h3>
                <p>Total Resources</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?php echo $available; ?></h3>
                <p>Available</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <h3><?php echo $in_use; ?></h3>
                <p>In Use</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <h3><?php echo $maintenance; ?></h3>
                <p>Maintenance</p>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-tractor"></i>
                Resource Management
            </h1>
            <a href="add_resource.php" class="btn-add">
                <i class="fas fa-plus"></i> Add New Resource
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label"><i class="fas fa-filter"></i> Filter By:</span>
                <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <select name="type" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach($all_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type == $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Available" <?php echo $filter_status == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Rented" <?php echo $filter_status == 'Rented' ? 'selected' : ''; ?>>Rented</option>
                        <option value="Under Maintenance" <?php echo $filter_status == 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="Out of Service" <?php echo $filter_status == 'Out of Service' ? 'selected' : ''; ?>>Out of Service</option>
                    </select>
                </form>
            </div>
            
            <?php if($filter_type || $filter_status): ?>
            <div class="active-filters">
                <?php if($filter_type): ?>
                <span class="filter-badge">
                    <i class="fas fa-tag"></i> Type: <?php echo htmlspecialchars($filter_type); ?>
                </span>
                <?php endif; ?>
                <?php if($filter_status): ?>
                <span class="filter-badge">
                    <i class="fas fa-circle"></i> Status: <?php echo htmlspecialchars($filter_status); ?>
                </span>
                <?php endif; ?>
                <a href="resources.php" class="btn-clear-filter">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- All Resources - Card Grid Layout -->
        <div class="section-header">
            <h2>
                <i class="fas fa-list"></i>
                All Resources
            </h2>
            <div class="resource-count">
                <i class="fas fa-database"></i> <?php echo mysqli_num_rows($resources); ?> Resources
                <?php if($filter_type || $filter_status): ?>
                <span style="margin-left: 5px;">(Filtered)</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if($resources && mysqli_num_rows($resources) > 0): ?>
        <div class="resources-grid">
            <?php while($row = mysqli_fetch_assoc($resources)): 
                $type_icon = '';
                switch($row['type']) {
                    case 'Tractor': $type_icon = 'fas fa-tractor'; break;
                    case 'Soil Cultivation': $type_icon = 'fas fa-seedling'; break;
                    case 'Planting': $type_icon = 'fas fa-leaf'; break;
                    case 'Irrigation': $type_icon = 'fas fa-water'; break;
                    case 'Harvesting': $type_icon = 'fas fa-cut'; break;
                    case 'Hay Making': $type_icon = 'fas fa-tree'; break;
                    case 'Loading': $type_icon = 'fas fa-crane'; break;
                    case 'Fertilizer Dispenser': $type_icon = 'fas fa-tachometer-alt'; break;
                    case 'Produce Sorter': $type_icon = 'fas fa-sort-amount-down'; break;
                    case 'Post Harvest': $type_icon = 'fas fa-warehouse'; break;
                    default: $type_icon = 'fas fa-tractor';
                }
            ?>
            <div class="resource-card">
                <div class="resource-header">
                    <div class="resource-icon">
                        <i class="<?php echo $type_icon; ?>"></i>
                    </div>
                    <div class="resource-name"><?php echo htmlspecialchars($row['name']); ?></div>
                    <div class="resource-model">Model: <?php echo htmlspecialchars($row['model']); ?></div>
                    <div class="resource-type-badge"><?php echo htmlspecialchars($row['type']); ?></div>
                </div>
                <div class="resource-body">
                    <div class="info-row">
                        <span class="info-label">Category</span>
                        <span class="info-value"><?php echo htmlspecialchars($row['category']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Manufacturer</span>
                        <span class="info-value"><?php echo htmlspecialchars($row['manufacturer'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">HP / Fuel</span>
                        <span class="info-value"><?php echo $row['horsepower'] ? $row['horsepower'] . ' HP' : 'N/A'; ?> / <?php echo $row['fuel_type']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quantity</span>
                        <span class="info-value"><?php echo $row['quantity']; ?> unit(s)</span>
                    </div>
                    
                    <!-- Price Row with Edit -->
                    <div class="price-row">
                        <div>
                            <div class="price-label">Daily Rate</div>
                            <div class="price-amount">৳ <?php echo number_format($row['daily_rate'], 2); ?></div>
                        </div>
                        <button type="button" class="price-edit-btn" onclick="toggleEditForm(<?php echo $row['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <form method="POST" class="price-edit-form" id="price-form-<?php echo $row['id']; ?>">
                        <input type="hidden" name="resource_id" value="<?php echo $row['id']; ?>">
                        <input type="number" name="daily_rate" class="price-edit-input" step="100" value="<?php echo $row['daily_rate']; ?>">
                        <button type="submit" name="update_price" class="btn-update-status">Save</button>
                        <button type="button" class="btn-update-status" onclick="cancelEdit(<?php echo $row['id']; ?>)" style="background:#6c757d;">Cancel</button>
                    </form>

                    <!-- Status Update -->
                    <form method="POST" class="status-update-form">
                        <input type="hidden" name="resource_id" value="<?php echo $row['id']; ?>">
                        <select name="status" class="status-select">
                            <option value="Available" <?php echo $row['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Rented" <?php echo $row['status'] == 'Rented' ? 'selected' : ''; ?>>Rented</option>
                            <option value="Under Maintenance" <?php echo $row['status'] == 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                            <option value="Out of Service" <?php echo $row['status'] == 'Out of Service' ? 'selected' : ''; ?>>Out of Service</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-update-status">Update</button>
                    </form>

                    <!-- Current Status Badge -->
                    <div style="margin-top: 0.8rem;">
                        <?php
                        $status_class = 'status-available';
                        switch($row['status']) {
                            case 'Available': $status_class = 'status-available'; break;
                            case 'Rented': $status_class = 'status-rented'; break;
                            case 'Under Maintenance': $status_class = 'status-under_maintenance'; break;
                            case 'Out of Service': $status_class = 'status-out_of_service'; break;
                        }
                        ?>
                        <span class="status <?php echo $status_class; ?>">Current: <?php echo $row['status']; ?></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="maintenance.php?resource_id=<?php echo $row['id']; ?>" class="btn-maintenance">
                            <i class="fas fa-tools"></i> History
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this resource? This cannot be undone.')" class="btn-delete">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tractor"></i>
            <p>No resources found matching your filters.</p>
            <a href="resources.php" style="margin-top: 1rem; display: inline-block; color: #FF8C42; text-decoration: none;">Clear Filters</a>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>

    <script>
        function toggleEditForm(resourceId) {
            const form = document.getElementById('price-form-' + resourceId);
            if (form.classList.contains('active')) {
                form.classList.remove('active');
            } else {
                form.classList.add('active');
            }
        }
        
        function cancelEdit(resourceId) {
            const form = document.getElementById('price-form-' + resourceId);
            form.classList.remove('active');
        }
    </script>
</body>
</html>