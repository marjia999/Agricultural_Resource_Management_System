<?php
session_start();
include '../database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}


// Handle client delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    if ($delete_id > 0) {
        $active_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS active_count FROM service_requests WHERE user_id = ? AND request_status IN ('Pending','Approved','Processing','Delivered')");
        mysqli_stmt_bind_param($active_stmt, 'i', $delete_id);
        mysqli_stmt_execute($active_stmt);
        $active_count = mysqli_fetch_assoc(mysqli_stmt_get_result($active_stmt))['active_count'] ?? 0;

        if ((int)$active_count === 0) {
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'Client'");
            mysqli_stmt_bind_param($delete_stmt, 'i', $delete_id);
            mysqli_stmt_execute($delete_stmt);
            header("Location: clients.php?deleted=1");
            exit();
        }
    }
    header("Location: clients.php?delete_blocked=1");
    exit();
}

// Get all clients with their request counts
$clients_query = "SELECT u.*, 
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id) as total_requests,
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id AND request_status = 'Pending') as pending_requests,
                  (SELECT COUNT(*) FROM service_requests WHERE user_id = u.id AND request_status IN ('Delivered', 'Returned')) as completed_requests,
                  (SELECT SUM(total_amount) FROM payments WHERE user_id = u.id AND payment_status = 'Paid') as total_spent
                  FROM users u 
                  WHERE u.role = 'Client' 
                  ORDER BY u.created_at DESC";
$clients = mysqli_query($conn, $clients_query);

// Calculate statistics
$total_clients_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'Client'");
$total_clients = $total_clients_query ? mysqli_fetch_assoc($total_clients_query)['total'] : 0;

$active_clients_query = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as active FROM service_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$active_clients = $active_clients_query ? mysqli_fetch_assoc($active_clients_query)['active'] : 0;

$total_requests_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM service_requests sr JOIN users u ON sr.user_id = u.id WHERE u.role = 'Client'");
$total_requests = $total_requests_query ? mysqli_fetch_assoc($total_requests_query)['total'] : 0;

$total_spent_query = mysqli_query($conn, "SELECT SUM(p.total_amount) as total FROM payments p JOIN users u ON p.user_id = u.id WHERE u.role = 'Client' AND p.payment_status = 'Paid'");
$total_spent = $total_spent_query ? mysqli_fetch_assoc($total_spent_query)['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - AgriRMS</title>
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
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-high {
            background: #d4edda;
            color: #155724;
        }

        .status-medium {
            background: #cce5ff;
            color: #004085;
        }

        .status-low {
            background: #fff3cd;
            color: #856404;
        }

        /* Action Buttons */
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
            transform: translateY(-1px);
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

        .footer p i {
            color: #FFD966;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                gap: 1rem;
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
            th, td {
                font-size: 0.8rem;
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
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <!-- Stats Cards -->
        <?php if(isset($_GET['deleted'])): ?>
        <div style="background:#d4edda;color:#155724;padding:12px 16px;border-radius:12px;margin-bottom:16px;">
            <i class="fas fa-check-circle"></i> Client deleted successfully.
        </div>
        <?php endif; ?>
        <?php if(isset($_GET['delete_blocked'])): ?>
        <div style="background:#fff3cd;color:#856404;padding:12px 16px;border-radius:12px;margin-bottom:16px;">
            <i class="fas fa-info-circle"></i> Client has active requests and cannot be deleted.
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?php echo $total_clients; ?></h3>
                <p>Total Clients</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <h3><?php echo $active_clients; ?></h3>
                <p>Active (Last 30 days)</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3><?php echo $total_requests; ?></h3>
                <p>Total Requests</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>৳ <?php echo number_format($total_spent, 2); ?></h3>
                <p>Total Spent</p>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-users"></i>
                Client Management
            </h1>
        </div>

        <!-- All Clients Table -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    Registered Clients
                </h3>
                <span style="font-size: 0.8rem; color: #888;">
                    <i class="fas fa-database"></i> Total: <?php echo $total_clients; ?> clients
                </span>
            </div>
            <div class="table-responsive">
                <?php if($clients && mysqli_num_rows($clients) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered Date</th>
                            <th>Total Requests</th>
                            <th>Pending</th>
                            <th>Completed</th>
                            <th>Total Spent</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($clients)): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <span class="status-badge status-high"><?php echo $row['total_requests']; ?> requests</span>
                            </td>
                            <td>
                                <?php 
                                $pending_class = $row['pending_requests'] > 0 ? 'status-medium' : 'status-low';
                                ?>
                                <span class="status-badge <?php echo $pending_class; ?>"><?php echo $row['pending_requests']; ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-high"><?php echo $row['completed_requests']; ?></span>
                            </td>
                            <td class="amount">৳ <?php echo number_format($row['total_spent'] ?? 0, 2); ?></td>
                            <td>
                                <a href="view_client.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this client account? This cannot be undone.');">
                                    <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" class="btn-view" style="background:#dc3545;margin-left:6px;border:none;cursor:pointer;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No clients found.</p>
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