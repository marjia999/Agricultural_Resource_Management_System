<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

// Get statistics
$total_resources = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources"))['count'];
$total_clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Client'"))['count'];
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests"))['count'];
$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE status = 'Pending'"))['count'];
$available_resources = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Available'"))['count'];
$in_use_resources = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'In_Use'"))['count'];
$maintenance_resources = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources WHERE status = 'Maintenance'"))['count'];
$completed_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE status = 'Completed'"))['count'];
$pending_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'Pending'"))['count'];
$paid_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'Paid'"))['count'];

// Recent requests with more details
$recent_requests = mysqli_query($conn, "SELECT sr.*, u.first_name, u.last_name, u.email FROM service_requests sr 
                                        JOIN users u ON sr.client_id = u.id 
                                        ORDER BY sr.request_date DESC LIMIT 5");

// Recent resources
$recent_resources = mysqli_query($conn, "SELECT * FROM resources ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgriRMS</title>
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
        }

        .user-info {
            background: #0d3b1a;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-info i {
            color: #FFD966;
        }

        .btn-logout {
            background: #dc3545;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            color: white !important;
        }

        .btn-logout:hover {
            background: #c82333;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Dashboard Header */
        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header h1 i {
            color: #FF8C42;
        }

        .dashboard-header p {
            color: #666;
            margin-top: 0.3rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: 0.3s;
            border: 1px solid #e8f0e8;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            background: rgba(255,140,66,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.6rem;
            color: #FF8C42;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            color: #1B4F2B;
            font-weight: 700;
        }

        .stat-info p {
            color: #666;
            font-size: 0.85rem;
        }

        .stat-info small {
            color: #888;
            font-size: 0.7rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
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

        .card-header a {
            color: #FF8C42;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.3s;
        }

        .card-header a:hover {
            color: #e67e22;
            text-decoration: underline;
        }

        /* Interactive Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e8f0e8;
        }

        th {
            background: #f8f9f8;
            color: #1B4F2B;
            font-weight: 600;
            font-size: 0.85rem;
        }

        td {
            color: #444;
            font-size: 0.85rem;
        }

        tr {
            cursor: pointer;
        }

        tr:hover {
            background: #f0f7f0;
        }

        /* Status Badges */
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-available, .status-approved, .status-paid, .status-successful, .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-in_use, .status-assigned, .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }

        .status-maintenance, .status-cancelled, .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        /* Interactive Buttons */
        .btn-view {
            background: #FF8C42;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-block;
        }

        .btn-view:hover {
            background: #e67e22;
        }

        .btn-edit {
            background: #1B4F2B;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-block;
        }

        .btn-edit:hover {
            background: #0d3b1a;
        }

        /* Two Column Layout */
        .two-columns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        /* Quick Actions - Rounded borders, no transition */
        .quick-actions {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e8f0e8;
            margin-bottom: 2rem;
        }

        .quick-actions-header {
            background: #1B4F2B;
            padding: 1.2rem 1.5rem;
            color: white;
        }

        .quick-actions-header h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .quick-actions-header h3 i {
            color: #FFD966;
        }

        .quick-actions-header p {
            font-size: 0.8rem;
            color: #c8e6d9;
            margin-top: 0.3rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .action-item {
            padding: 1.8rem 1.5rem;
            text-align: center;
            text-decoration: none;
            background: white;
            display: block;
            color: inherit;
            border-right: 1px solid #e8f0e8;
            border-bottom: 1px solid #e8f0e8;
        }

        /* Remove borders for specific positions */
        .action-item:nth-child(3n) {
            border-right: none;
        }

        .action-item:nth-last-child(-n+3) {
            border-bottom: none;
        }

        /* Remove default link styles */
        .action-item:link,
        .action-item:visited,
        .action-item:hover,
        .action-item:active {
            color: inherit;
            text-decoration: none;
        }

        /* Hover effect - NO TRANSITION, rounded border */
        .action-item:hover {
            border: 2px solid #FF8C42;
            margin: -1px;
            border-radius: 16px;
            background: #fefefe;
            position: relative;
            z-index: 2;
        }

        /* Fix border radius for edge items */
        .action-item:first-child:hover {
            border-radius: 16px 0 0 16px;
        }

        .action-item:nth-child(3):hover {
            border-radius: 0 16px 16px 0;
        }

        .action-item:nth-child(4):hover {
            border-radius: 16px 0 0 16px;
        }

        .action-item:last-child:hover {
            border-radius: 0 16px 16px 0;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,140,66,0.1);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .action-item:hover .action-icon {
            background: #FF8C42;
        }

        .action-item:hover .action-icon i {
            color: white;
        }

        .action-icon i {
            font-size: 1.6rem;
            color: #FF8C42;
        }

        .action-item h4 {
            color: #1B4F2B;
            font-size: 1rem;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .action-item p {
            color: #888;
            font-size: 0.75rem;
        }

        /* Footer */
        .footer {
            background: #0d2b18;
            color: white;
            padding: 1.5rem;
            text-align: center;
            margin-top: 3rem;
        }

        .footer p {
            color: #c0ddc0;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .action-item:nth-child(2n) {
                border-right: none;
            }
            .action-item:nth-child(3n) {
                border-right: 1px solid #e8f0e8;
            }
            .action-item:nth-last-child(-n+2) {
                border-bottom: none;
            }
            .action-item:hover {
                border-radius: 16px;
                margin: -1px;
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
            .container {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .actions-grid {
                grid-template-columns: 1fr;
            }
            .action-item {
                border-right: none;
                border-bottom: 1px solid #e8f0e8;
            }
            .action-item:last-child {
                border-bottom: none;
            }
            .action-item:hover {
                border-radius: 16px;
                margin: 0;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            background-color: #1B4F2B;
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 4px 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.7rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
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

    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-tractor"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_resources; ?></h3>
                    <p>Total Resources</p>
                    <small><?php echo $available_resources; ?> Available • <?php echo $in_use_resources; ?> In Use</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_clients; ?></h3>
                    <p>Total Clients</p>
                    <small>Active registered users</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_requests; ?></h3>
                    <p>Service Requests</p>
                    <small><?php echo $pending_requests; ?> Pending • <?php echo $completed_requests; ?> Completed</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pending_payments; ?></h3>
                    <p>Pending Payments</p>
                    <small><?php echo $paid_payments; ?> Paid</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="quick-actions">
            <div class="quick-actions-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="actions-grid">
                <a href="add_resource.php" class="action-item">
                    <div class="action-icon"><i class="fas fa-plus"></i></div>
                    <h4>Add Resource</h4>
                    <p>Add new machinery or equipment</p>
                </a>
                <a href="service_requests.php" class="action-item">
                    <div class="action-icon"><i class="fas fa-clipboard-list"></i></div>
                    <h4>View Requests</h4>
                    <p>Manage service requests</p>
                </a>
                <a href="logistics.php" class="action-item">
                    <div class="action-icon"><i class="fas fa-truck"></i></div>
                    <h4>Logistics</h4>
                    <p>Schedule deliveries</p>
                </a>
                <a href="billing.php" class="action-item">
                    <div class="action-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h4>Billing</h4>
                    <p>Manage invoices & payments</p>
                </a>
                <a href="clients.php" class="action-item">
                    <div class="action-icon"><i class="fas fa-users"></i></div>
                    <h4>Clients</h4>
                    <p>View all registered clients</p>
                </a>
                <a href="resources.php?status=maintenance" class="action-item">
                    <div class="action-icon"><i class="fas fa-tools"></i></div>
                    <h4>Maintenance</h4>
                    <p>Track resource maintenance</p>
                </a>
            </div>
        </div>

        <!-- Two Column Layout for Recent Data -->
        <div class="two-columns">
            <!-- Recent Service Requests -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Recent Service Requests</h3>
                    <a href="service_requests.php">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($recent_requests)): ?>
                            <tr onclick="window.location.href='service_requests.php?id=<?php echo $row['id']; ?>'">
                                <td>
                                    <strong><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></strong>
                                    <br><small style="color:#888;"><?php echo $row['email']; ?></small>
                                </td>
                                <td><?php echo $row['resource_type']; ?></td>
                                <td><span class="status status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($row['request_date'])); ?></td>
                                <td>
                                    <div class="tooltip">
                                        <a href="service_requests.php?id=<?php echo $row['id']; ?>" class="btn-view">View Details</a>
                                        <span class="tooltip-text">View full request</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($recent_requests) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                    <p style="margin-top: 0.5rem; color: #888;">No requests found</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Resources -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Recently Added Resources</h3>
                    <a href="resources.php">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($recent_resources)): ?>
                            <tr onclick="window.location.href='resources.php?id=<?php echo $row['id']; ?>'">
                                <td>
                                    <strong><?php echo $row['name']; ?></strong>
                                    <br><small style="color:#888;">ID: #<?php echo $row['id']; ?></small>
                                </td>
                                <td><?php echo $row['type']; ?></td>
                                <td><span class="status status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="tooltip">
                                        <a href="resources.php?edit=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                        <span class="tooltip-text">Edit resource details</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($recent_resources) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-tractor" style="font-size: 2rem; color: #ccc;"></i>
                                    <p style="margin-top: 0.5rem; color: #888;">No resources found</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved.</p>
    </footer>
</body>
</html>