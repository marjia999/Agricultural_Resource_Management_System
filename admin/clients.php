<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

$clients = mysqli_query($conn, "SELECT * FROM users WHERE role = 'Client' ORDER BY created_at DESC");
?>
<?php include '../includes/header.php'; ?>
<h1>Client Management</h1>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Registered Date</th><th>Total Requests</th></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($clients)): 
                    $request_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE client_id = ".$row['id']))['count'];
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td><?php echo $request_count; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>