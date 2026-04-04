<?php
include '../includes/session.php';
redirectIfNotClient();
include '../database.php';

$user_id = $_SESSION['user_id'];
$requests = mysqli_query($conn, "SELECT * FROM service_requests WHERE client_id = $user_id ORDER BY request_date DESC");
?>
<?php include '../includes/header.php'; ?>
<h1>My Service Requests</h1>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Request ID</th><th>Resource Type</th><th>Description</th><th>Status</th><th>Request Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($requests)): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo $row['resource_type']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td><span class="status status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                    <td><?php echo $row['request_date']; ?></td>
                    <td>
                        <?php if($row['status'] == 'Completed'): ?>
                            <a href="payments.php" class="btn btn-primary" style="padding: 0.3rem 0.8rem;">Make Payment</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>