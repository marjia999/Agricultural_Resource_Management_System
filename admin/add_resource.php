<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $unit_id = mysqli_real_escape_string($conn, $_POST['unit_id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $last_maintenance = $_POST['last_maintenance'] ?: null;
    $next_maintenance = $_POST['next_maintenance'] ?: null;
    
    $query = "INSERT INTO resources (name, unit_id, type, status, price, last_maintenance, next_maintenance) 
              VALUES ('$name', '$unit_id', '$type', '$status', '$price', '$last_maintenance', '$next_maintenance')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: resources.php?success=added");
        exit();
    } else {
        $error = "Failed to add resource.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Resource - AgriRMS</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e8f0e8;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .form-group label .required {
            color: #dc3545;
            margin-left: 2px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e8f0e8;
            border-radius: 16px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            background: #fefefe;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255,140,66,0.1);
        }

        .form-group input::placeholder {
            color: #bbb;
        }

        /* Price input styling */
        .price-input-wrapper {
            position: relative;
        }

        .price-currency {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: 500;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .price-input-wrapper input {
            padding-left: 40px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* Help Text */
        .help-text {
            color: #888;
            font-size: 0.7rem;
            display: block;
            margin-top: 0.3rem;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-submit {
            flex: 1;
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.9rem;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,140,66,0.3);
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        .btn-back {
            flex: 1;
            background: #1B4F2B;
            color: white;
            padding: 0.9rem;
            border-radius: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #0d3b1a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(27,79,43,0.3);
        }

        .alert {
            padding: 0.9rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #c0392b;
            border-left: 4px solid #c0392b;
        }

        .alert-error i {
            color: #c0392b;
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
            .form-card {
                padding: 1.5rem;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .button-group {
                flex-direction: column;
                gap: 0.8rem;
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
            <a href="dashboard.php">Dashboard</a>
            <a href="resources.php">Resources</a>
            <a href="service_requests.php">Requests</a>
            <a href="logistics.php">Logistics</a>
            <a href="billing.php">Billing</a>
            <a href="clients.php">Clients</a>
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>
                    <i class="fas fa-plus-circle"></i>
                    Add New Resource
                </h1>
                <p>Fill in the details below to add a new agricultural resource</p>
            </div>

            <div class="form-card">
                <?php if(isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Row 1: Resource Name -->
                    <div class="form-group">
                        <label>Resource Name <span class="required">*</span></label>
                        <input type="text" name="name" required placeholder="Enter resource name (e.g., John Deere Tractor)">
                    </div>

                    <!-- Row 2: Unit ID and Type -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit ID / Serial Number <span class="required">*</span></label>
                            <input type="text" name="unit_id" required placeholder="Enter unique ID (e.g., TR-001, ST-001)">
                            <div class="help-text">Unique identifier to differentiate multiple resources of the same type</div>
                        </div>

                        <div class="form-group">
                            <label>Resource Type <span class="required">*</span></label>
                            <select name="type" required>
                                <option value="Machinery">Machinery</option>
                                <option value="Storage">Storage</option>
                                <option value="Equipment">Equipment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Status and Daily Rate -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status" required>
                                <option value="Available">Available</option>
                                <option value="In_Use">In Use</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Daily Rate (BDT) <span class="required">*</span></label>
                            <div class="price-input-wrapper">
                                <span class="price-currency">৳</span>
                                <input type="number" name="price" required placeholder="0.00" step="100" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Last Maintenance and Next Maintenance -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Maintenance</label>
                            <input type="date" name="last_maintenance">
                        </div>

                        <div class="form-group">
                            <label>Next Maintenance</label>
                            <input type="date" name="next_maintenance">
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Add Resource
                        </button>
                        <a href="resources.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Resources
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
    </footer>
</body>
</html>