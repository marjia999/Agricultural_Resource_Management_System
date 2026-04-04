<?php
include '../includes/session.php';
redirectIfNotClient();
include '../database.php';

$user_id = $_SESSION['user_id'];

// Get filter type from URL
$filter_type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';

// Build query
$query = "SELECT * FROM resources WHERE status = 'Available'";
if ($filter_type && $filter_type != 'All') {
    $query .= " AND type = '$filter_type'";
}
$query .= " ORDER BY type, name";
$resources = mysqli_query($conn, $query);

// Get counts for filters
$machinery_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE type = 'Machinery' AND status = 'Available'"))['count'];
$storage_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE type = 'Storage' AND status = 'Available'"))['count'];
$equipment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE type = 'Equipment' AND status = 'Available'"))['count'];
$total_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Available'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Resources - AgriRMS</title>
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

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            background: white;
            border: 2px solid #e8f0e8;
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            text-decoration: none;
            color: #1B4F2B;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .filter-tab:hover {
            border-color: #FF8C42;
            transform: translateY(-2px);
        }

        .filter-tab.active {
            background: #FF8C42;
            border-color: #FF8C42;
            color: #1B4F2B;
        }

        .filter-tab span {
            background: #e8f0e8;
            padding: 0.1rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
            color: #1B4F2B;
        }

        .filter-tab.active span {
            background: rgba(255,255,255,0.3);
            color: #1B4F2B;
        }

        /* Resources Grid */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .resource-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .resource-header {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            padding: 1.2rem;
            color: white;
        }

        .resource-header h3 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .resource-header .unit-id {
            font-size: 0.7rem;
            opacity: 0.8;
            font-family: monospace;
        }

        .resource-body {
            padding: 1.2rem;
        }

        .resource-info {
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f4f0;
        }

        .info-row .label {
            color: #888;
            font-size: 0.75rem;
        }

        .info-row .value {
            font-weight: 600;
            color: #1B4F2B;
        }

        .price {
            font-size: 1.3rem;
            color: #FF8C42;
            font-weight: 700;
        }

        .price small {
            font-size: 0.7rem;
            font-weight: 400;
        }

        .btn-request {
            width: 100%;
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.7rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 20px;
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
            .resources-grid {
                grid-template-columns: 1fr;
            }
            .filter-tabs {
                justify-content: center;
            }
            .page-header h1 {
                font-size: 1.5rem;
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="resources.php"><i class="fas fa-tractor"></i> Resources</a>
            <a href="request_service.php"><i class="fas fa-plus-circle"></i> New Request</a>
            <a href="my_requests.php"><i class="fas fa-list"></i> My Requests</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-tractor"></i>
                Available Resources
            </h1>
            <p>Browse and rent agricultural equipment, storage, and machinery</p>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?type=All" class="filter-tab <?php echo (!$filter_type || $filter_type == 'All') ? 'active' : ''; ?>">
                All <span><?php echo $total_count; ?></span>
            </a>
            <a href="?type=Machinery" class="filter-tab <?php echo ($filter_type == 'Machinery') ? 'active' : ''; ?>">
                Machinery <span><?php echo $machinery_count; ?></span>
            </a>
            <a href="?type=Storage" class="filter-tab <?php echo ($filter_type == 'Storage') ? 'active' : ''; ?>">
                Storage <span><?php echo $storage_count; ?></span>
            </a>
            <a href="?type=Equipment" class="filter-tab <?php echo ($filter_type == 'Equipment') ? 'active' : ''; ?>">
                Equipment <span><?php echo $equipment_count; ?></span>
            </a>
        </div>

        <!-- Resources Grid -->
        <?php if(mysqli_num_rows($resources) > 0): ?>
        <div class="resources-grid">
            <?php while($resource = mysqli_fetch_assoc($resources)): ?>
            <div class="resource-card">
                <div class="resource-header">
                    <h3><?php echo htmlspecialchars($resource['name']); ?></h3>
                    <div class="unit-id"><i class="fas fa-qrcode"></i> <?php echo htmlspecialchars($resource['unit_id']); ?></div>
                </div>
                <div class="resource-body">
                    <div class="resource-info">
                        <div class="info-row">
                            <span class="label"><i class="fas fa-cogs"></i> Resource Type</span>
                            <span class="value"><?php echo $resource['type']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-money-bill-wave"></i> Daily Rate</span>
                            <span class="price">৳ <?php echo number_format($resource['price'], 2); ?><small>/day</small></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-chart-line"></i> Status</span>
                            <span class="value" style="color: #28a745;"><i class="fas fa-check-circle"></i> Available</span>
                        </div>
                        <?php if($resource['next_maintenance']): ?>
                        <div class="info-row">
                            <span class="label"><i class="fas fa-calendar-alt"></i> Next Maintenance</span>
                            <span class="value"><?php echo date('M d, Y', strtotime($resource['next_maintenance'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="request_service.php?resource_id=<?php echo $resource['id']; ?>" class="btn-request">
                        <i class="fas fa-calendar-alt"></i> Request This Resource
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tractor"></i>
            <p>No resources available at the moment. Please check back later.</p>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>