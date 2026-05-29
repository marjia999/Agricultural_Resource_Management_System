<?php
session_start();
include '../database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Assign resource to request and create delivery
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_resource'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $resource_id = (int)($_POST['resource_id'] ?? 0);
    $delivery_id = (int)($_POST['delivery_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    mysqli_begin_transaction($conn);

    $req_stmt = mysqli_prepare($conn, "SELECT quantity, request_status FROM service_requests WHERE id = ? AND resource_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($req_stmt, 'ii', $request_id, $resource_id);
    mysqli_stmt_execute($req_stmt);
    $req = mysqli_fetch_assoc(mysqli_stmt_get_result($req_stmt));

    if ($req && $req['request_status'] === 'Approved') {
        $requested_qty = (int)$req['quantity'];

        $res_stmt = mysqli_prepare($conn, "SELECT quantity, status FROM resources WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($res_stmt, 'i', $resource_id);
        mysqli_stmt_execute($res_stmt);
        $res = mysqli_fetch_assoc(mysqli_stmt_get_result($res_stmt));

        if ($res && (int)$res['quantity'] >= $requested_qty && $res['status'] !== 'Under Maintenance') {
            $update_req = mysqli_prepare($conn, "UPDATE service_requests SET delivery_id = ?, start_date = ?, end_date = ?, request_status = 'Processing' WHERE id = ?");
            mysqli_stmt_bind_param($update_req, 'issi', $delivery_id, $start_date, $end_date, $request_id);
            mysqli_stmt_execute($update_req);

            $new_quantity = max(((int)$res['quantity']) - $requested_qty, 0);
            $new_status = $new_quantity <= 0 ? 'Rented' : 'Available';
            $update_res = mysqli_prepare($conn, "UPDATE resources SET quantity = ?, status = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_res, 'isi', $new_quantity, $new_status, $resource_id);
            mysqli_stmt_execute($update_res);

            mysqli_commit($conn);
            header("Location: logistics.php?success=assigned");
            exit();
        }
    }

    mysqli_rollback($conn);
    header("Location: logistics.php?error=assign_failed");
    exit();
}

// Update delivery status
if (isset($_POST['update_delivery_status'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed_status = ['Processing', 'Delivered', 'Returned', 'Cancelled'];

    if ($request_id > 0 && in_array($status, $allowed_status, true)) {
        mysqli_begin_transaction($conn);

        $req_stmt = mysqli_prepare($conn, "SELECT resource_id, quantity, request_status FROM service_requests WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($req_stmt, 'i', $request_id);
        mysqli_stmt_execute($req_stmt);
        $req = mysqli_fetch_assoc(mysqli_stmt_get_result($req_stmt));

        if ($req) {
            $resource_id = (int)$req['resource_id'];
            $requested_qty = (int)$req['quantity'];
            $old_status = $req['request_status'];

            if (in_array($old_status, ['Processing', 'Delivered'], true) && in_array($status, ['Returned', 'Cancelled'], true)) {
                $release_stmt = mysqli_prepare($conn, "UPDATE resources SET quantity = quantity + ?, status = CASE WHEN quantity + ? > 0 THEN 'Available' ELSE status END WHERE id = ? AND status != 'Under Maintenance'");
                mysqli_stmt_bind_param($release_stmt, 'iii', $requested_qty, $requested_qty, $resource_id);
                mysqli_stmt_execute($release_stmt);
            }

            if ($status === 'Delivered') {
                $update_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = ?, delivered_at = NOW() WHERE id = ?");
            } elseif ($status === 'Returned') {
                $update_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = ?, returned_at = NOW() WHERE id = ?");
            } else {
                $update_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = ? WHERE id = ?");
            }
            mysqli_stmt_bind_param($update_stmt, 'si', $status, $request_id);
            mysqli_stmt_execute($update_stmt);

            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }
    }

    header("Location: logistics.php");
    exit();
}

// Get approved requests where payment is paid
$approved_requests = mysqli_query($conn, "SELECT sr.*, u.full_name, u.email, r.name as resource_name, r.model, 
                                          d.delivery_type, d.location_type, d.delivery_fee
                                         FROM service_requests sr 
                                         JOIN users u ON sr.user_id = u.id 
                                         JOIN resources r ON sr.resource_id = r.id
                                         LEFT JOIN delivery d ON sr.delivery_id = d.id
                                         WHERE sr.request_status = 'Approved' 
                                         AND sr.payment_status = 'Paid'");

// Get active deliveries
$active_deliveries = mysqli_query($conn, "SELECT sr.*, u.full_name, u.email, u.phone, r.name as resource_name, r.model, 
                                          d.delivery_type, d.location_type, d.delivery_fee
                                         FROM service_requests sr 
                                         JOIN users u ON sr.user_id = u.id 
                                         JOIN resources r ON sr.resource_id = r.id 
                                         LEFT JOIN delivery d ON sr.delivery_id = d.id 
                                         WHERE sr.request_status IN ('Processing', 'Delivered')
                                         ORDER BY sr.created_at DESC");

// Calculate statistics
$total_assignments_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM service_requests WHERE request_status IN ('Processing', 'Delivered', 'Returned')");
$total_assignments = $total_assignments_query ? mysqli_fetch_assoc($total_assignments_query)['total'] : 0;

$active_assignments_query = mysqli_query($conn, "SELECT COUNT(*) as active FROM service_requests WHERE request_status = 'Processing'");
$active_assignments = $active_assignments_query ? mysqli_fetch_assoc($active_assignments_query)['active'] : 0;

$delivered_query = mysqli_query($conn, "SELECT COUNT(*) as delivered FROM service_requests WHERE request_status = 'Delivered'");
$delivered = $delivered_query ? mysqli_fetch_assoc($delivered_query)['delivered'] : 0;

$returned_query = mysqli_query($conn, "SELECT COUNT(*) as returned FROM service_requests WHERE request_status = 'Returned'");
$returned = $returned_query ? mysqli_fetch_assoc($returned_query)['returned'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics Management - AgriRMS</title>
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

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.1);
        }

        .form-group input:read-only {
            background: #f0f7f0;
            cursor: not-allowed;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
        }

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

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-returned {
            background: #d4edda;
            color: #155724;
        }

        .status-update-form {
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
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            border: none;
            font-size: 0.7rem;
            cursor: pointer;
        }

        .btn-update:hover {
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

        .info-box {
            background: #f8f9f8;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            border: 1px solid #e8f0e8;
        }

        .info-box .label {
            font-size: 0.7rem;
            color: #888;
            display: block;
            margin-bottom: 0.3rem;
        }

        .info-box .value {
            font-weight: 600;
            color: #1B4F2B;
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

        @media (max-width: 1024px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
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
    <script>
        function updateDetails() {
            const requestSelect = document.getElementById('request_id');
            const selectedOption = requestSelect.options[requestSelect.selectedIndex];
            
            if (selectedOption.value) {
                const resourceName = selectedOption.getAttribute('data-resource-name') || 'N/A';
                const resourceModel = selectedOption.getAttribute('data-resource-model') || 'N/A';
                const deliveryType = selectedOption.getAttribute('data-delivery-type') || 'Standard Delivery';
                const deliveryLocation = selectedOption.getAttribute('data-delivery-location') || '';
                const deliveryFee = parseFloat(selectedOption.getAttribute('data-delivery-fee')) || 0;
                const address = selectedOption.getAttribute('data-address') || '';
                
                // Update resource display
                document.getElementById('resource_display').innerHTML = 
                    '<div class="info-box"><span class="label"><i class="fas fa-tractor"></i> Resource</span>' +
                    '<span class="value">' + resourceName + ' (' + resourceModel + ')</span></div>';
                
                // Update delivery display
                let deliveryText = deliveryType;
                if (deliveryLocation) {
                    deliveryText += ' (' + deliveryLocation + ')';
                }
                deliveryText += ' - ৳' + deliveryFee.toLocaleString();
                
                document.getElementById('delivery_display').innerHTML = 
                    '<div class="info-box"><span class="label"><i class="fas fa-truck"></i> Delivery Transport</span>' +
                    '<span class="value">' + deliveryText + '</span></div>';
                
                // Update address
                document.getElementById('delivery_address').value = address || '';
                
                // Set hidden inputs
                document.getElementById('resource_id').value = selectedOption.getAttribute('data-resource-id') || '';
                document.getElementById('delivery_id').value = selectedOption.getAttribute('data-delivery-id') || '';
                document.getElementById('delivery_fee').value = deliveryFee;
            }
        }
    </script>
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
        <?php if(isset($_GET['success']) && $_GET['success'] == 'assigned'): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            Resource assigned and delivery scheduled successfully!
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-truck"></i></div>
                <h3><?php echo $total_assignments; ?></h3>
                <p>Total Assignments</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
                <h3><?php echo $active_assignments; ?></h3>
                <p>Active Deliveries</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <h3><?php echo $delivered; ?></h3>
                <p>Delivered</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-undo-alt"></i></div>
                <h3><?php echo $returned; ?></h3>
                <p>Returned</p>
            </div>
        </div>

        <div class="page-header">
            <h1>
                <i class="fas fa-truck"></i>
                Logistics & Delivery Management
            </h1>
        </div>

        <!-- Assign Resource Form -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Assign Resource & Schedule Delivery
                </h3>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Select Approved Request (Payment Completed)</label>
                    <select name="request_id" id="request_id" required onchange="updateDetails()">
                        <option value="">Select Request</option>
                        <?php while($row = mysqli_fetch_assoc($approved_requests)): 
                            $full_address = $row['delivery_address'];
                            if($row['delivery_upazila']) $full_address .= ', ' . $row['delivery_upazila'];
                            if($row['delivery_district']) $full_address .= ', ' . $row['delivery_district'];
                        ?>
                        <option value="<?php echo $row['id']; ?>" 
                                data-resource-id="<?php echo $row['resource_id']; ?>"
                                data-resource-name="<?php echo htmlspecialchars($row['resource_name']); ?>"
                                data-resource-model="<?php echo htmlspecialchars($row['model']); ?>"
                                data-delivery-id="<?php echo $row['delivery_id']; ?>"
                                data-delivery-type="<?php echo htmlspecialchars($row['delivery_type'] ?? 'Standard Delivery'); ?>"
                                data-delivery-location="<?php echo htmlspecialchars($row['location_type'] ?? ''); ?>"
                                data-delivery-fee="<?php echo $row['delivery_fee']; ?>"
                                data-address="<?php echo htmlspecialchars($full_address); ?>">
                            #<?php echo $row['id']; ?> - <?php echo htmlspecialchars($row['full_name']); ?> - <?php echo htmlspecialchars($row['rental_duration']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if(mysqli_num_rows($approved_requests) == 0): ?>
                    <small style="color: #dc3545; display: block; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> No approved requests with completed payment found.
                    </small>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Resource (Auto-filled from request)</label>
                        <div id="resource_display" class="info-box">
                            <span class="label"><i class="fas fa-tractor"></i> Resource</span>
                            <span class="value">— Select a request first —</span>
                        </div>
                        <input type="hidden" name="resource_id" id="resource_id">
                    </div>
                    <div class="form-group">
                        <label>Delivery Transport (Auto-filled from request)</label>
                        <div id="delivery_display" class="info-box">
                            <span class="label"><i class="fas fa-truck"></i> Delivery Transport</span>
                            <span class="value">— Select a request first —</span>
                        </div>
                        <input type="hidden" name="delivery_id" id="delivery_id">
                        <input type="hidden" name="delivery_fee" id="delivery_fee">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Delivery Address (Auto-filled from request)</label>
                    <textarea name="delivery_address" id="delivery_address" rows="3" required readonly style="background: #f0f7f0;"></textarea>
                </div>

                <button type="submit" name="assign_resource" class="btn-primary">
                    <i class="fas fa-check"></i> Assign & Schedule Delivery
                </button>
            </form>
        </div>

        <!-- Active Deliveries -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    Active Deliveries
                </h3>
                <span style="font-size: 0.8rem; color: #888;">
                    <i class="fas fa-truck"></i> Total: <?php echo $active_assignments; ?> active
                </span>
            </div>
            <div class="table-responsive">
                <?php if($active_deliveries && mysqli_num_rows($active_deliveries) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Client</th>
                            <th>Resource</th>
                            <th>Transport</th>
                            <th>Delivery Fee</th>
                            <th>Period</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($active_deliveries)): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                <br><small><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></small>
                             </td
                            <td>
                                <?php echo htmlspecialchars($row['resource_name']); ?>
                                <br><small><?php echo htmlspecialchars($row['model']); ?></small>
                             </td
                            <td>
                                <?php 
                                $transport_text = 'Standard Delivery';
                                if($row['location_type'] == 'Dhaka') {
                                    $transport_text = 'Inside Dhaka';
                                } elseif($row['location_type'] == 'Outside Dhaka') {
                                    $transport_text = 'Outside Dhaka';
                                }
                                echo htmlspecialchars($transport_text);
                                ?>
                             </td
                            <td style="font-weight: 600; color: #FF8C42;">৳ <?php echo number_format($row['delivery_fee'] ?? $row['delivery_cost'], 2); ?> </td
                            <td>
                                <?php echo date('d M', strtotime($row['start_date'])); ?> - <?php echo date('d M', strtotime($row['end_date'])); ?>
                             </td
                            <td>
                                <?php echo htmlspecialchars(substr($row['delivery_address'], 0, 40)); ?>...
                                <br><small><?php echo htmlspecialchars($row['delivery_district']); ?></small>
                             </td
                            <td>
                                <?php
                                $status_class = 'status-processing';
                                switch($row['request_status']) {
                                    case 'Processing': $status_class = 'status-processing'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    case 'Returned': $status_class = 'status-returned'; break;
                                    default: $status_class = 'status-processing';
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $row['request_status']; ?></span>
                             </td
                            <td>
                                <form method="POST" class="status-update-form">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="status-select">
                                        <option value="Processing" <?php echo $row['request_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Delivered" <?php echo $row['request_status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Returned" <?php echo $row['request_status'] == 'Returned' ? 'selected' : ''; ?>>Returned</option>
                                        <option value="Cancelled" <?php echo $row['request_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_delivery_status" class="btn-update">
                                        <i class="fas fa-sync-alt"></i> Update
                                    </button>
                                </form>
                             </td
                         </tr
                        <?php endwhile; ?>
                    </tbody>
                 </table
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <p>No active deliveries found.</p>
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