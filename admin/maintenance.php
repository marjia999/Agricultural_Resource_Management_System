<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

// Get resource ID from URL
$resource_id = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;

// Get resource details
$resource_query = mysqli_query($conn, "SELECT * FROM resources WHERE id = $resource_id");
$resource = mysqli_fetch_assoc($resource_query);

if (!$resource) {
    header("Location: resources.php");
    exit();
}

// Handle add maintenance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_maintenance'])) {
    $maintenance_date = mysqli_real_escape_string($conn, $_POST['maintenance_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $cost = mysqli_real_escape_string($conn, $_POST['cost']);
    
    $query = "INSERT INTO resource_maintenance (resource_id, maintenance_date, description, cost) 
              VALUES ($resource_id, '$maintenance_date', '$description', '$cost')";
    
    if (mysqli_query($conn, $query)) {
        // Update next maintenance date in resources table
        if (!empty($_POST['next_maintenance'])) {
            $next_maintenance = mysqli_real_escape_string($conn, $_POST['next_maintenance']);
            mysqli_query($conn, "UPDATE resources SET next_maintenance = '$next_maintenance', last_maintenance = '$maintenance_date' WHERE id = $resource_id");
        }
        $success = "Maintenance record added successfully!";
    } else {
        $error = "Failed to add maintenance record.";
    }
}

// Handle delete maintenance
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM resource_maintenance WHERE id = $id");
    header("Location: maintenance.php?resource_id=$resource_id");
    exit();
}

// Get all maintenance records for this resource
$maintenance_query = mysqli_query($conn, "SELECT * FROM resource_maintenance WHERE resource_id = $resource_id ORDER BY maintenance_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance History - <?php echo htmlspecialchars($resource['name']); ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
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
            font-size: 1.8rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 i {
            color: #FF8C42;
        }

        .btn-back {
            background: #1B4F2B;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #0d3b1a;
            transform: translateY(-2px);
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f4f0;
        }

        .card-header h3 {
            color: #1B4F2B;
            display: flex;
            align-items: center;
            gap: 8px;
        }

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
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
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

        .btn-submit {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e8f0e8;
        }

        th {
            background: #f8f9f8;
            color: #1B4F2B;
        }

        .cost {
            color: #FF8C42;
            font-weight: 600;
        }

        .footer {
            background: #0d2b18;
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .page-header {
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
        <div class="container">
            <div class="page-header">
                <h1>
                    Maintenance: <?php echo htmlspecialchars($resource['name']); ?>
                </h1>
                <a href="resources.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Resources
                </a>
            </div>

            <!-- Add Maintenance Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Log New Maintenance</h3>
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Maintenance Date *</label>
                            <input type="date" name="maintenance_date" required>
                        </div>
                        <div class="form-group">
                            <label>Cost (BDT)</label>
                            <input type="number" step="0.01" name="cost" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="3" required placeholder="Describe the maintenance work performed..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Next Scheduled Maintenance</label>
                        <input type="date" name="next_maintenance">
                    </div>
                    <button type="submit" name="add_maintenance" class="btn-submit">
                        <i class="fas fa-save"></i> Save
                    </button>
                </form>
            </div>

            <!-- Maintenance History Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Maintenance Records</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Cost</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($maintenance_query) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($maintenance_query)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['maintenance_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td class="cost"><?php echo $row['cost'] ? '৳ ' . number_format($row['cost'], 2) : '—'; ?></td>
                                    <td>
                                        <a href="?resource_id=<?php echo $resource_id; ?>&delete=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('Delete this maintenance record?')" 
                                           class="btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: #888;">
                                        <i class="fas fa-info-circle"></i> No maintenance records found.
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
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System</p>
    </footer>
</body>
</html>