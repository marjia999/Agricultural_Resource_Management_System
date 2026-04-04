<?php
include_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriRMS - Agricultural Resource Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <h2><i class="fas fa-leaf"></i> AgriRMS</h2>
            <p>Agricultural Resource Management System</p>
        </div>
        <nav class="nav-links">
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php">Dashboard</a>
                    <a href="../admin/resources.php">Resources</a>
                    <a href="../admin/service_requests.php">Requests</a>
                    <a href="../admin/logistics.php">Logistics</a>
                    <a href="../admin/billing.php">Billing</a>
                    <a href="../admin/clients.php">Clients</a>
                <?php else: ?>
                    <a href="../client/dashboard.php">Dashboard</a>
                    <a href="../client/request_service.php">Request Service</a>
                    <a href="../client/my_requests.php">My Requests</a>
                    <a href="../client/payments.php">Payments</a>
                    <a href="../client/profile.php">Profile</a>
                <?php endif; ?>
                <span class="user-info"><i class="fas fa-user-circle"></i> <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="../logout.php" class="btn btn-danger" style="padding: 0.3rem 1rem;">Logout</a>
            <?php else: ?>
                <a href="../index.php#home">Home</a>
                <a href="../index.php#services">Services</a>
                <a href="../index.php#about">About</a>
                <a href="../index.php#contact">Contact</a>
                <a href="../login.php" class="btn-outline" style="background: transparent; border: 2px solid white; color: white; padding: 0.5rem 1.2rem; border-radius: 8px; text-decoration: none; transition: 0.3s;">Login</a>
                <a href="../register.php" class="btn-accent" style="background: #FF8C42; color: white; padding: 0.5rem 1.2rem; border-radius: 8px; text-decoration: none; transition: 0.3s;">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <div class="container">