<?php
session_start();
include '../database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Get client ID from URL
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($client_id == 0) {
    header("Location: clients.php");
    exit();
}

// Get client information
$client_query = "SELECT * FROM users WHERE id = $client_id AND role = 'Client'";
$client_result = mysqli_query($conn, $client_query);

if (!$client_result || mysqli_num_rows($client_result) == 0) {
    header("Location: clients.php");
    exit();
}

$client = mysqli_fetch_assoc($client_result);

// Get client statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $client_id) as total_requests,
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $client_id AND request_status = 'Pending') as pending_requests,
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $client_id AND request_status = 'Approved') as approved_requests,
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $client_id AND request_status = 'Delivered') as delivered_requests,
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $client_id AND request_status = 'Returned') as returned_requests,
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $client_id AND request_status = 'Cancelled') as cancelled_requests,
                (SELECT SUM(total_amount) FROM payments WHERE user_id = $client_id AND payment_status = 'Paid') as total_spent,
                (SELECT SUM(due_amount) FROM payments WHERE user_id = $client_id AND payment_status != 'Paid') as total_due,
                (SELECT COUNT(*) FROM payments WHERE user_id = $client_id) as total_payments";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get all service requests for this client
$requests_query = "SELECT sr.*, r.name as resource_name, r.model, r.type as resource_type, r.daily_rate,
                  d.transport_name, d.transport_type
                  FROM service_requests sr 
                  LEFT JOIN resources r ON sr.resource_id = r.id 
                  LEFT JOIN delivery d ON sr.delivery_id = d.id 
                  WHERE sr.user_id = $client_id 
                  ORDER BY sr.created_at DESC";
$requests = mysqli_query($conn, $requests_query);

// Get all payments for this client
$payments_query = "SELECT * FROM payments WHERE user_id = $client_id ORDER BY created_at DESC";
$payments = mysqli_query($conn, $payments_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - <?php echo htmlspecialchars($client['full_name']); ?> - AgriRMS</title>
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

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #1B4F2B 0%, #0d3b1a 100%);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,140,66,0.15), transparent);
            border-radius: 50%;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,215,0,0.08), transparent);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .hero-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FF8C42, #FFD966);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #1B4F2B;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .hero-info h1 {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 0.5rem;
        }

        .hero-info .member-since {
            color: #c8e6d9;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        /* Stats Grid Premium */
        .stats-premium {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-premium-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #e8f0e8;
            position: relative;
            overflow: hidden;
        }

        .stat-premium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF8C42, #FFD966);
        }

        .stat-premium-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .stat-premium-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,140,66,0.1);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
        }

        .stat-premium-icon i {
            font-size: 1.3rem;
            color: #FF8C42;
        }

        .stat-premium-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1B4F2B;
        }

        .stat-premium-label {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.2rem;
        }

        /* Contact Info Row */
        .contact-info {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 0.8rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            padding: 0.4rem 1rem;
            border-radius: 10px;
        }

        .contact-item i {
            color: #FFD966;
            font-size: 0.8rem;
        }

        .contact-item span {
            color: white;
            font-size: 0.8rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
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
            gap: 10px;
        }

        .card-header h3 i {
            color: #FF8C42;
        }

        .badge-count {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
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
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-returned { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #cce5ff; color: #004085; }

        /* Buttons */
        .btn-view {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            padding: 0.3rem 0.9rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(23,162,184,0.3);
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem;
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

        .amount {
            font-weight: 700;
            color: #1B4F2B;
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
            .stats-premium {
                grid-template-columns: repeat(3, 1fr);
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
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            .hero-left {
                justify-content: center;
                text-align: center;
            }
            .stats-premium {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            .contact-info {
                justify-content: center;
            }
            th, td {
                font-size: 0.7rem;
                padding: 6px;
            }
        }

        @media (max-width: 480px) {
            .stats-premium {
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
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <div class="hero-left">
                    <div class="hero-avatar">
                        <?php echo strtoupper(substr($client['full_name'], 0, 1)); ?>
                    </div>
                    <div class="hero-info">
                        <h1><?php echo htmlspecialchars($client['full_name']); ?></h1>
                        <div class="member-since">
                            <i class="fas fa-calendar-alt"></i>
                            Member since <?php echo date('F j, Y', strtotime($client['created_at'])); ?>
                        </div>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($client['email']); ?></span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($client['phone'] ?? 'Not provided'); ?></span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($client['address'] ?? 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="clients.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Premium Stats Grid -->
        <div class="stats-premium">
            <div class="stat-premium-card">
                <div class="stat-premium-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-premium-value"><?php echo $stats['total_requests'] ?? 0; ?></div>
                <div class="stat-premium-label">Total Requests</div>
            </div>
            <div class="stat-premium-card">
                <div class="stat-premium-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-premium-value"><?php echo $stats['pending_requests'] ?? 0; ?></div>
                <div class="stat-premium-label">Pending</div>
            </div>
            <div class="stat-premium-card">
                <div class="stat-premium-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-premium-value"><?php echo $stats['approved_requests'] ?? 0; ?></div>
                <div class="stat-premium-label">Approved</div>
            </div>
            <div class="stat-premium-card">
                <div class="stat-premium-icon"><i class="fas fa-truck"></i></div>
                <div class="stat-premium-value"><?php echo $stats['delivered_requests'] ?? 0; ?></div>
                <div class="stat-premium-label">Delivered</div>
            </div>
            <div class="stat-premium-card">
                <div class="stat-premium-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-premium-value"><?php echo $stats['returned_requests'] ?? 0; ?></div>
                <div class="stat-premium-label">Completed</div>
            </div>
        </div>

        <!-- Service Requests Section -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-history"></i>
                    Service Request History
                </h3>
                <span class="badge-count">
                    <i class="fas fa-database"></i> <?php echo $stats['total_requests'] ?? 0; ?> Records
                </span>
            </div>
            <div class="table-responsive">
                <?php if($requests && mysqli_num_rows($requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Resource</th>
                            <th>Duration</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($requests)): ?>
                        <tr style="cursor: pointer;" onclick="window.location.href='view_request.php?id=<?php echo $row['id']; ?>'">
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($row['resource_name'] ?? 'N/A'); ?>
                                <?php if($row['model']): ?>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($row['model']); ?></small>
                                <?php endif; ?>
                             </td
                            <td>
                                <?php echo $row['rental_duration']; ?> 
                                <br><small><?php echo $row['quantity']; ?> unit(s)</small>
                             </td
                            <td>
                                <?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d', strtotime($row['end_date'])); ?>
                             </td
                            <td class="amount">৳ <?php echo number_format($row['total_cost'], 2); ?> </td
                            <td>
                                <?php
                                $status_class = 'status-pending';
                                switch($row['request_status']) {
                                    case 'Pending': $status_class = 'status-pending'; break;
                                    case 'Approved': $status_class = 'status-approved'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    case 'Returned': $status_class = 'status-returned'; break;
                                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $row['request_status']; ?></span>
                             </td
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?> </td
                            <td>
                                <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-view" onclick="event.stopPropagation();">
                                    <i class="fas fa-eye"></i> View
                                </a>
                             </td
                         </tr
                        <?php endwhile; ?>
                    </tbody>
                 </table
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No service requests found for this client.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment History Section -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-file-invoice-dollar"></i>
                    Payment History
                </h3>
                <span class="badge-count">
                    <i class="fas fa-database"></i> <?php echo $stats['total_payments'] ?? 0; ?> Records
                </span>
            </div>
            <div class="table-responsive">
                <?php if($payments && mysqli_num_rows($payments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td><span class="badge-count"><?php echo $row['payment_type']; ?></span></td>
                            <td class="amount">৳ <?php echo number_format($row['total_amount'], 2); ?> </td
                            <td style="color: #28a745;">৳ <?php echo number_format($row['paid_amount'], 2); ?> </td
                            <td style="color: #dc3545;">৳ <?php echo number_format($row['due_amount'], 2); ?> </td
                            <td>
                                <?php
                                $method_icon = '';
                                switch($row['payment_method']) {
                                    case 'Bkash': $method_icon = '<i class="fab fa-btc"></i>'; break;
                                    case 'Nagad': $method_icon = '<i class="fas fa-mobile-alt"></i>'; break;
                                    case 'Rocket': $method_icon = '<i class="fas fa-rocket"></i>'; break;
                                    default: $method_icon = '<i class="fas fa-money-bill"></i>';
                                }
                                echo $method_icon . ' ' . $row['payment_method'];
                                ?>
                             </td
                            <td><small><?php echo $row['transaction_id'] ?? '—'; ?></small> </td
                            <td>
                                <?php
                                $payment_class = $row['payment_status'] == 'Paid' ? 'status-paid' : ($row['payment_status'] == 'Partial' ? 'status-partial' : 'status-pending');
                                ?>
                                <span class="status <?php echo $payment_class; ?>"><?php echo $row['payment_status']; ?></span>
                             </td
                            <td><?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '—'; ?> </td
                         </tr
                        <?php endwhile; ?>
                    </tbody>
                 </table
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <p>No payment records found for this client.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>

    <script>
        // Make table rows clickable
        document.querySelectorAll('tbody tr').forEach(row => {
            if (!row.querySelector('.empty-state')) {
                row.style.cursor = 'pointer';
            }
        });
    </script>
</body>
</html>