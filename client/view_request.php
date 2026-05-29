<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Get request details with correct column names
$query = "SELECT sr.*, r.name as resource_name, r.model, r.daily_rate, r.type as resource_type 
          FROM service_requests sr 
          LEFT JOIN resources r ON sr.resource_id = r.id 
          WHERE sr.id = $request_id AND sr.user_id = $user_id";
$result = mysqli_query($conn, $query);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header("Location: my_requests.php");
    exit();
}

// Check if request can be edited - only if status is Pending or Approved AND payment_status is not Paid
$can_edit = ($request['request_status'] == 'Pending' || $request['request_status'] == 'Approved') && $request['payment_status'] != 'Paid';

// Get payment details if any
$payment_query = "SELECT * FROM payments WHERE booking_id = $request_id";
$payment_result = mysqli_query($conn, $payment_query);
$payment = mysqli_fetch_assoc($payment_result);

// Handle cancellation (pending requests only)
if (isset($_POST['cancel_request']) && $request['request_status'] === 'Pending') {
    $reason = trim($_POST['cancellation_reason'] ?? '');
    if ($reason !== '') {
        $updated_instruction = trim(($request['special_instructions'] ?? '') . "\n[Cancellation Reason] " . $reason);
        $cancel_stmt = mysqli_prepare($conn, "UPDATE service_requests SET request_status = 'Cancelled', special_instructions = ? WHERE id = ?");
        mysqli_stmt_bind_param($cancel_stmt, 'si', $updated_instruction, $request_id);
        mysqli_stmt_execute($cancel_stmt);
        header("Location: view_request.php?id=$request_id&cancelled=1");
        exit();
    }
}

// Handle update request (only if can edit)
if (isset($_POST['update_request']) && $can_edit) {
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $delivery_district = mysqli_real_escape_string($conn, $_POST['delivery_district']);
    $delivery_upazila = mysqli_real_escape_string($conn, $_POST['delivery_upazila']);
    
    // Calculate days and total amount
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $start->diff($end)->days + 1;
    
    // Calculate total based on rental duration
    if ($request['rental_duration'] == 'Daily') {
        $total_amount = $days * $request['daily_rate'] * $request['quantity'];
    } elseif ($request['rental_duration'] == 'Weekly') {
        $weeks = ceil($days / 7);
        $total_amount = $weeks * $request['daily_rate'] * 7 * $request['quantity'];
    } else {
        $months = ceil($days / 30);
        $total_amount = $months * $request['daily_rate'] * 30 * $request['quantity'];
    }
    
    $total_cost = $total_amount + $request['delivery_cost'];
    
    mysqli_query($conn, "UPDATE service_requests SET 
                         start_date = '$start_date', 
                         end_date = '$end_date',
                         total_rental_cost = '$total_amount',
                         total_cost = '$total_cost',
                         delivery_address = '$delivery_address',
                         delivery_district = '$delivery_district',
                         delivery_upazila = '$delivery_upazila'
                         WHERE id = $request_id");
    
    // Update payment record
    if ($payment) {
        mysqli_query($conn, "UPDATE payments SET resource_cost = '$total_amount', total_amount = '$total_cost' WHERE booking_id = $request_id");
    }
    
    header("Location: view_request.php?id=$request_id&updated=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - AgriRMS</title>
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

        .btn-logout {
            background: #dc3545;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
        }

        .main-content {
            flex: 1;
            padding: 2rem 5%;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .card h2 {
            color: #1B4F2B;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f4f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            color: #FF8C42;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            padding: 0.8rem;
            background: #f8f9f8;
            border-radius: 12px;
        }

        .info-item label {
            font-size: 0.7rem;
            color: #888;
            display: block;
            margin-bottom: 0.3rem;
        }

        .info-item .value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1B4F2B;
        }

        .full-width {
            grid-column: span 2;
        }

        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-returned { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-paid { background: #d4edda; color: #155724; }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-update {
            background: #28a745;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 1rem;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 10px;
            display: inline-block;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .info-message {
            background: #cce5ff;
            color: #004085;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .footer {
            background: #0d2b18;
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .form-row {
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
            <a href="dashboard.php">Dashboard</a>
            <a href="resources.php">Resources</a>
            <a href="request_service.php">New Request</a>
            <a href="my_requests.php">My Requests</a>
            <a href="payments.php">Payments</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="container">
            <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> Request updated successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['cancelled'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> Request cancelled successfully!</div>
            <?php endif; ?>

            <?php if(!$can_edit && ($request['request_status'] == 'Pending' || $request['request_status'] == 'Approved')): ?>
                <div class="info-message">
                    <i class="fas fa-info-circle"></i> 
                    This request cannot be edited because payment has already been made or request is already processed.
                </div>
            <?php endif; ?>

            <div class="card">
                <h2><i class="fas fa-eye"></i> Request Details #<?php echo str_pad($request['id'], 5, '0', STR_PAD_LEFT); ?></h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label><i class="fas fa-tractor"></i> Resource</label>
                        <div class="value"><?php echo htmlspecialchars($request['resource_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-cogs"></i> Model</label>
                        <div class="value"><?php echo htmlspecialchars($request['model'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                        <div class="value"><?php echo date('M d, Y', strtotime($request['start_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-calendar-check"></i> End Date</label>
                        <div class="value"><?php echo date('M d, Y', strtotime($request['end_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-clock"></i> Rental Duration</label>
                        <div class="value"><?php echo $request['rental_duration']; ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-boxes"></i> Quantity</label>
                        <div class="value"><?php echo $request['quantity']; ?> unit(s)</div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-money-bill-wave"></i> Rental Cost</label>
                        <div class="value">৳ <?php echo number_format($request['total_rental_cost'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-truck"></i> Delivery Cost</label>
                        <div class="value">৳ <?php echo number_format($request['delivery_cost'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-file-invoice-dollar"></i> Total Amount</label>
                        <div class="value" style="color: #FF8C42; font-size: 1.1rem;">৳ <?php echo number_format($request['total_cost'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-chart-line"></i> Request Status</label>
                        <div class="value">
                            <?php
                            $status_class = 'status-pending';
                            switch($request['request_status']) {
                                case 'Pending': $status_class = 'status-pending'; break;
                                case 'Approved': $status_class = 'status-approved'; break;
                                case 'Processing': $status_class = 'status-processing'; break;
                                case 'Delivered': $status_class = 'status-delivered'; break;
                                case 'Returned': $status_class = 'status-returned'; break;
                                case 'Cancelled': $status_class = 'status-cancelled'; break;
                            }
                            ?>
                            <span class="status <?php echo $status_class; ?>"><?php echo $request['request_status']; ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-credit-card"></i> Payment Status</label>
                        <div class="value">
                            <span class="status <?php echo $request['payment_status'] == 'Paid' ? 'status-paid' : 'status-pending'; ?>">
                                <?php echo $request['payment_status'] ?? 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-calendar-day"></i> Request Date</label>
                        <div class="value"><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label><i class="fas fa-map-marker-alt"></i> Delivery Address</label>
                        <div class="value">
                            <?php echo htmlspecialchars($request['delivery_address']); ?>
                            <?php if($request['delivery_district']): ?>
                                <br><small><?php echo htmlspecialchars($request['delivery_district']); ?>, <?php echo htmlspecialchars($request['delivery_upazila']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-route"></i> Tracking Timeline</h2>
                <div class="info-grid" style="grid-template-columns: repeat(1, 1fr);">
                    <div class="info-item"><label>1. Request Submitted</label><div class="value"><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></div></div>
                    <div class="info-item"><label>2. Approved</label><div class="value"><?php echo $request['approved_at'] ? date('M d, Y h:i A', strtotime($request['approved_at'])) : 'Waiting for admin approval'; ?></div></div>
                    <div class="info-item"><label>3. Delivered</label><div class="value"><?php echo $request['delivered_at'] ? date('M d, Y h:i A', strtotime($request['delivered_at'])) : ($request['request_status'] === 'Delivered' ? 'Out for delivery' : 'Not delivered yet'); ?></div></div>
                    <div class="info-item"><label>4. Returned / Completed</label><div class="value"><?php echo $request['returned_at'] ? date('M d, Y h:i A', strtotime($request['returned_at'])) : 'Not completed yet'; ?></div></div>
                    <div class="info-item"><label>Delivery Status</label><div class="value"><?php echo in_array($request['request_status'], ['Processing', 'Delivered'], true) ? 'In transit' : ($request['request_status'] === 'Returned' ? 'Returned' : ($request['request_status'] === 'Cancelled' ? 'Cancelled' : 'Pending dispatch')); ?></div></div>
                </div>
            </div>

            <?php if($can_edit): ?>
                <div class="card">
                    <h2><i class="fas fa-edit"></i> Edit Request</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                                <input type="date" name="start_date" value="<?php echo $request['start_date']; ?>" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-check"></i> End Date</label>
                                <input type="date" name="end_date" value="<?php echo $request['end_date']; ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> District</label>
                                <input type="text" name="delivery_district" value="<?php echo htmlspecialchars($request['delivery_district']); ?>" placeholder="e.g., Dhaka">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-location-dot"></i> Upazila/Thana</label>
                                <input type="text" name="delivery_upazila" value="<?php echo htmlspecialchars($request['delivery_upazila']); ?>" placeholder="e.g., Gulshan">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-address-card"></i> Full Delivery Address</label>
                            <textarea name="delivery_address" rows="3" required><?php echo htmlspecialchars($request['delivery_address']); ?></textarea>
                        </div>
                        <button type="submit" name="update_request" class="btn-update">
                            <i class="fas fa-save"></i> Update Request
                        </button>
                    </form>
                </div>

                <?php if($request['request_status'] === 'Pending'): ?>
                <div class="card">
                    <h2><i class="fas fa-ban"></i> Cancel Request</h2>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this request? This action cannot be undone.');">
                        <div class="form-group">
                            <label>Cancellation Reason</label>
                            <textarea name="cancellation_reason" rows="3" required placeholder="Please tell us why you're cancelling this request..."></textarea>
                        </div>
                        <button type="submit" name="cancel_request" class="btn-cancel">
                            <i class="fas fa-trash-alt"></i> Cancel Request
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 1rem;">
                <a href="my_requests.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to My Requests</a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System</p>
    </footer>

    <script>
        // Set end date min to start date
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');
            if (startDate) {
                endDateInput.min = startDate;
                if (endDateInput.value && endDateInput.value < startDate) {
                    endDateInput.value = startDate;
                }
            }
        });
    </script>
</body>
</html>