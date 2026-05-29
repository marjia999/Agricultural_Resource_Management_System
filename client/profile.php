<?php
session_start();
include '../database.php';

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

$success = '';
$error = '';
$edit_mode = isset($_GET['edit']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $password_matches = password_verify($current_password, $user['password']);

        if (!$password_matches) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and confirm password do not match.";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pass_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($pass_stmt, 'si', $password_hash, $user_id);
            if (mysqli_stmt_execute($pass_stmt)) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password. Please try again.";
            }
        }
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($check_stmt, 'si', $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_email = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_email) > 0) {
            $error = "Email already exists! Please use a different email.";
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, 'ssssi', $full_name, $email, $phone, $address, $user_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $success = "Profile updated successfully!";

                $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                $user = mysqli_fetch_assoc($user_query);
                $edit_mode = false;
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Get user statistics
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id"))['count'];
$total_paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM payments WHERE user_id = $user_id AND payment_status = 'Paid'"))['total'] ?? 0;
$active_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status IN ('Approved', 'Processing', 'Delivered')"))['count'];
$completed_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE user_id = $user_id AND request_status = 'Returned'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AgriRMS</title>
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

        .profile-hero {
            background: linear-gradient(135deg, #1B4F2B 0%, #0d3b1a 100%);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,140,66,0.15), transparent);
            border-radius: 50%;
        }

        .profile-hero-content {
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 2;
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #FF8C42, #FFD966);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: #1B4F2B;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .profile-info h1 {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 0.3rem;
        }

        .profile-info .member-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.7rem;
            color: #FFD966;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
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
            width: 55px;
            height: 55px;
            background: rgba(255,140,66,0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: #FF8C42;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            color: #1B4F2B;
            font-weight: 700;
        }

        .stat-info p {
            color: #666;
            font-size: 0.7rem;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 1.8rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: all 0.3s;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f4f0;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-left i {
            width: 40px;
            height: 40px;
            background: rgba(255,140,66,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #FF8C42;
        }

        .card-header-left h2 {
            font-size: 1.3rem;
            color: #1B4F2B;
        }

        .btn-edit {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }

        .btn-edit:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.8rem;
        }

        .form-group label i {
            color: #FF8C42;
            width: 20px;
            margin-right: 5px;
        }

        .form-group input,
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
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.1);
        }

        .btn-update {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.8rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
        }

        .info-list {
            margin-top: 0.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e8f0e8;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: #666;
            font-size: 0.85rem;
        }

        .info-label i {
            width: 25px;
            color: #FF8C42;
        }

        .info-value {
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #d4edda;
            color: #155724;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .alert {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
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

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            .profile-hero-content {
                flex-direction: column;
                text-align: center;
            }
            .profile-container {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .card-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
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

    <div class="main-content">
        <!-- Hero Section -->
        <div class="profile-hero">
            <div class="profile-hero-content">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <span class="member-badge">
                        <i class="fas fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_requests; ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $active_requests; ?></h3>
                    <p>Active Requests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <h3><?php echo $completed_requests; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h3>৳ <?php echo number_format($total_paid, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>

        <div class="profile-container">
            <!-- Account Information Card (Always Visible) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-info-circle"></i>
                        <h2>Account Information</h2>
                    </div>
                    <?php if(!$edit_mode): ?>
                    <a href="?edit=1" class="btn-edit">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <?php endif; ?>
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-user"></i> Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if($user['phone']): ?>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-phone"></i> Phone Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($user['address']): ?>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['address']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-user-tag"></i> Role</span>
                        <span class="info-value"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar-alt"></i> Member Since</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-id-card"></i> User ID</span>
                        <span class="info-value">#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email Status</span>
                        <span class="info-value"><span class="status-badge"><i class="fas fa-check-circle"></i> Verified</span></span>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form (Shows only when edit mode is active) -->
            <?php if($edit_mode): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-user-edit"></i>
                        <h2>Edit Profile</h2>
                    </div>
                    <a href="profile.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="address" rows="3" placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
            <?php else: ?>
            <!-- Empty placeholder when not in edit mode -->
            <div class="card" style="display: flex; align-items: center; justify-content: center; text-align: center; min-height: 300px;">
                <div>
                    <i class="fas fa-user-edit" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p style="color: #888; margin-bottom: 1rem;">Click the "Edit Profile" button to update your information</p>
                    <a href="?edit=1" class="btn-edit" style="display: inline-flex;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-key"></i>
                        <h2>Change Password</h2>
                    </div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="8" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-update">
                        <i class="fas fa-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved.</p>
    </footer>
</body>
</html>
