<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

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
    $price = $_POST['price'];
    mysqli_query($conn, "UPDATE resources SET price = '$price' WHERE id = $id");
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

$resources = mysqli_query($conn, "SELECT * FROM resources ORDER BY type, unit_id ASC");
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

        /* Unit ID Badge */
        .unit-id {
            background: #1B4F2B;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            font-family: monospace;
            display: inline-block;
            color: #FFD966;
        }

        .unit-id i {
            color: #FF8C42;
            margin-right: 4px;
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

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-in_use {
            background: #cce5ff;
            color: #004085;
        }

        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
        }

        /* Price Cell - Inline Edit with Edit Icon */
        .price-cell {
            min-width: 160px;
        }

        .price-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .price-display {
            font-weight: 700;
            color: #1B4F2B;
            font-size: 1rem;
        }

        .price-edit-icon {
            background: none;
            border: none;
            color: #FF8C42;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 4px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .price-edit-icon:hover {
            background: rgba(255,140,66,0.1);
            transform: scale(1.1);
        }

        .price-edit-form {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .price-edit-form.active {
            display: flex;
        }

        .price-input {
            width: 100px;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e0e8e0;
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
        }

        .price-input:focus {
            outline: none;
            border-color: #FF8C42;
        }

        .btn-confirm {
            background: #28a745;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            border: none;
            font-size: 0.7rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-confirm:hover {
            background: #218838;
            transform: scale(1.02);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            border: none;
            font-size: 0.7rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: scale(1.02);
        }

        /* Status Cell - Inline Dropdown (auto-update) */
        .status-update-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e0e8e0;
            background: white;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .status-select:hover {
            border-color: #FF8C42;
        }

        .status-select:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 2px rgba(255,140,66,0.2);
        }

        /* Action Buttons */
        .btn-maintenance {
            background: #17a2b8;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-maintenance:hover {
            background: #138496;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .action-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .maintenance-date {
            font-size: 0.8rem;
            white-space: nowrap;
            color: #666;
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
        @media (max-width: 1200px) {
            .main-content {
                padding: 1.5rem;
            }
            th, td {
                padding: 10px 8px;
            }
        }

        @media (max-width: 992px) {
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
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .action-group {
                flex-direction: column;
                align-items: flex-start;
            }
            .status-update-form {
                flex-direction: column;
                align-items: flex-start;
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
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="resources.php"><i class="fas fa-tractor"></i> Resources</a>
            <a href="service_requests.php"><i class="fas fa-clipboard-list"></i> Requests</a>
            <a href="logistics.php"><i class="fas fa-truck"></i> Logistics</a>
            <a href="billing.php"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
            <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <?php
        // Calculate statistics
        $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM resources");
        $total = mysqli_fetch_assoc($total_query)['total'];
        
        $available_query = mysqli_query($conn, "SELECT COUNT(*) as available FROM resources WHERE status = 'Available'");
        $available = mysqli_fetch_assoc($available_query)['available'];
        
        $in_use_query = mysqli_query($conn, "SELECT COUNT(*) as in_use FROM resources WHERE status = 'In_Use'");
        $in_use = mysqli_fetch_assoc($in_use_query)['in_use'];
        
        $maintenance_query = mysqli_query($conn, "SELECT COUNT(*) as maintenance FROM resources WHERE status = 'Maintenance'");
        $maintenance = mysqli_fetch_assoc($maintenance_query)['maintenance'];
        ?>
        
        <!-- Stats Cards -->
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

        <!-- All Resources Table -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    All Resources
                </h3>
                <span style="font-size: 0.8rem; color: #888;">
                    <i class="fas fa-database"></i> Total: <?php echo $total; ?> resources
                </span>
            </div>
            <div class="table-responsive">
                <?php if(mysqli_num_rows($resources) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Unit ID</th>
                            <th>Resource Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Daily Rate</th>
                            <th>Last Maint.</th>
                            <th>Next Maint.</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($resources)): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <span class="unit-id"><i class="fas fa-qrcode"></i> <?php echo htmlspecialchars($row['unit_id']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td>
                                <!-- Status Dropdown - Auto updates on change -->
                                <form method="POST" class="status-update-form">
                                    <input type="hidden" name="resource_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="Available" <?php echo $row['status'] == 'Available' ? 'selected' : ''; ?>>✓ Available</option>
                                        <option value="In_Use" <?php echo $row['status'] == 'In_Use' ? 'selected' : ''; ?>>▶ In Use</option>
                                        <option value="Maintenance" <?php echo $row['status'] == 'Maintenance' ? 'selected' : ''; ?>>🔧 Maintenance</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td class="price-cell">
                                <div class="price-wrapper">
                                    <span class="price-display">৳ <?php echo number_format($row['price'], 2); ?></span>
                                    <button type="button" class="price-edit-icon" onclick="toggleEditForm(<?php echo $row['id']; ?>)" title="Edit Price">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                <form method="POST" class="price-edit-form" id="price-form-<?php echo $row['id']; ?>">
                                    <input type="hidden" name="resource_id" value="<?php echo $row['id']; ?>">
                                    <input type="number" name="price" class="price-input" step="100" value="<?php echo $row['price']; ?>" placeholder="Price">
                                    <button type="submit" name="update_price" class="btn-confirm" title="Save">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn-cancel" onclick="cancelEdit(<?php echo $row['id']; ?>)" title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="maintenance-date"><?php echo $row['last_maintenance'] ? date('M d, Y', strtotime($row['last_maintenance'])) : '—'; ?></td>
                            <td class="maintenance-date"><?php echo $row['next_maintenance'] ? date('M d, Y', strtotime($row['next_maintenance'])) : '—'; ?></td>
                            <td>
                                <div class="action-group">
                                    <a href="maintenance.php?resource_id=<?php echo $row['id']; ?>" class="btn-maintenance" title="Maintenance History">
                                        <i class="fas fa-tools"></i> History
                                    </a>
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this resource? This action cannot be undone.')" class="btn-delete" title="Delete Resource">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tractor"></i>
                        <p>No resources found. Click "Add New Resource" to create one.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
        
        // Close edit form when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.price-cell')) {
                document.querySelectorAll('.price-edit-form').forEach(form => {
                    form.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>