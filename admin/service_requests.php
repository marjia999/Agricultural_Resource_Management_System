<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

// Update request status
if (isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE service_requests SET status = '$status' WHERE id = $request_id");
}

$requests = mysqli_query($conn, "SELECT sr.*, u.first_name, u.last_name, u.email FROM service_requests sr 
                                JOIN users u ON sr.client_id = u.id 
                                ORDER BY sr.request_date DESC");
?>
<?php include '../includes/header.php'; ?>
<h1>Service Requests</h1>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>ID</th><th>Client</th><th>Resource Type</th><th>Description</th><th>Status</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($requests)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?><br><small><?php echo $row['email']; ?></small></td>
                    <td><?php echo $row['resource_type']; ?></td>
                    <td><?php echo substr($row['description'], 0, 50); ?>...</td>
                    <td><span class="status status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                    <td><?php echo $row['request_date']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo $row['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Assigned" <?php echo $row['status'] == 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="Completed" <?php echo $row['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $row['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="submit" name="update_status" value="Update" class="btn btn-primary" style="padding: 0.2rem 0.5rem;">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>