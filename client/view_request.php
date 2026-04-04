<?php
include '../includes/session.php';
redirectIfNotClient();
include '../database.php';

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Get request details
$query = "SELECT sr.*, r.name as resource_name, r.price as daily_rate 
          FROM service_requests sr 
          LEFT JOIN resources r ON sr.resource_id = r.id 
          WHERE sr.id = $request_id AND sr.client_id = $user_id";
$result = mysqli_query($conn, $query);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header("Location: my_requests.php");
    exit();
}

// THIS IS WHERE THE LINE GOES - Check if request can be edited
// User can edit only if request is Pending or Approved AND payment is not Paid yet
$can_edit = ($request['status'] == 'Pending' || $request['status'] == 'Approved') && $request['payment_status'] != 'Paid';

// Handle cancellation
if (isset($_POST['cancel_request'])) {
    $reason = mysqli_real_escape_string($conn, $_POST['cancellation_reason']);
    mysqli_query($conn, "UPDATE service_requests SET status = 'Cancelled', cancellation_reason = '$reason' WHERE id = $request_id");
    header("Location: view_request.php?id=$request_id&cancelled=1");
    exit();
}

// Handle update request (only if can edit)
if (isset($_POST['update_request']) && $can_edit) {
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Calculate days and total amount
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $start->diff($end)->days + 1;
    $total_amount = $days * $request['daily_rate'];
    
    mysqli_query($conn, "UPDATE service_requests SET 
                         start_date = '$start_date', 
                         end_date = '$end_date', 
                         description = '$description',
                         days = $days,
                         total_amount = $total_amount
                         WHERE id = $request_id");
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
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            padding: 0.8rem;
            background: #f8f9f8;
            border-radius: 12px;
        }

        .info-item label {
            font-size: 0.75rem;
            color: #888;
            display: block;
            margin-bottom: 0.3rem;
        }

        .info-item .value {
            font-size: 1rem;
            font-weight: 600;
            color: #1B4F2B;
        }

        .status {
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
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
            <a href="request_service.php">New Request</a>
            <a href="my_requests.php">My Requests</a>
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

            <!-- Show info message if cannot edit -->
            <?php if(!$can_edit && ($request['status'] == 'Pending' || $request['status'] == 'Approved')): ?>
                <div class="info-message">
                    <i class="fas fa-info-circle"></i> 
                    This request cannot be edited because payment has already been made.
                </div>
            <?php endif; ?>

            <div class="card">
                <h2><i class="fas fa-eye"></i> Request Details #<?php echo $request['id']; ?></h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>Resource</label>
                        <div class="value"><?php echo htmlspecialchars($request['resource_name'] ?? $request['resource_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Resource Type</label>
                        <div class="value"><?php echo htmlspecialchars($request['resource_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Start Date</label>
                        <div class="value"><?php echo date('M d, Y', strtotime($request['start_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label>End Date</label>
                        <div class="value"><?php echo date('M d, Y', strtotime($request['end_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Days</label>
                        <div class="value"><?php echo $request['days']; ?> days</div>
                    </div>
                    <div class="info-item">
                        <label>Daily Rate</label>
                        <div class="value">৳ <?php echo number_format($request['daily_rate'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Total Amount</label>
                        <div class="value">৳ <?php echo number_format($request['total_amount'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <div class="value">
                            <span class="status status-<?php echo strtolower($request['status']); ?>">
                                <?php echo $request['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Payment Status</label>
                        <div class="value">
                            <span class="status <?php echo $request['payment_status'] == 'Paid' ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo $request['payment_status'] ?? 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Request Date</label>
                        <div class="value"><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></div>
                    </div>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <label>Description</label>
                    <div class="value"><?php echo nl2br(htmlspecialchars($request['description'])); ?></div>
                </div>

                <?php if($request['cancellation_reason']): ?>
                    <div class="info-item">
                        <label>Cancellation Reason</label>
                        <div class="value"><?php echo htmlspecialchars($request['cancellation_reason']); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($can_edit): ?>
                <div class="card">
                    <h2><i class="fas fa-edit"></i> Edit Request</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $request['start_date']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo $request['end_date']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" required><?php echo htmlspecialchars($request['description']); ?></textarea>
                        </div>
                        <button type="submit" name="update_request" class="btn-update">
                            <i class="fas fa-save"></i> Update Request
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h2><i class="fas fa-ban"></i> Cancel Request</h2>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this request? This action cannot be undone.');">
                        <div class="form-group">
                            <label>Cancellation Reason</label>
                            <textarea name="cancellation_reason" rows="3" required placeholder="Please tell us why you're cancelling this request..."></textarea>
                        </div>
                        <button type="submit" name="cancel_request" class="btn-cancel">
                            <i class="fas fa-trash"></i> Cancel Request
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="text-align: center;">
                <a href="my_requests.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to My Requests</a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System</p>
    </footer>
</body>
</html>