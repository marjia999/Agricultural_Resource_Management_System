<?php
include '../includes/session.php';
redirectIfNotAdmin();
include '../database.php';

// Assign resource to request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_resource'])) {
    $request_id = $_POST['request_id'];
    $resource_id = $_POST['resource_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Create assignment
    mysqli_query($conn, "INSERT INTO request_assignments (request_id, resource_id) VALUES ($request_id, $resource_id)");
    $assignment_id = mysqli_insert_id($conn);
    
    // Create schedule
    mysqli_query($conn, "INSERT INTO schedules (assignment_id, start_date, end_date, delivery_address, status) 
                         VALUES ($assignment_id, '$start_date', '$end_date', '$address', 'Scheduled')");
    
    // Update resource status
    mysqli_query($conn, "UPDATE resources SET status = 'In_Use' WHERE id = $resource_id");
    
    // Update request status
    mysqli_query($conn, "UPDATE service_requests SET status = 'Assigned' WHERE id = $request_id");
}

$pending_requests = mysqli_query($conn, "SELECT sr.*, u.first_name, u.last_name FROM service_requests sr 
                                         JOIN users u ON sr.client_id = u.id 
                                         WHERE sr.status = 'Approved'");

$available_resources = mysqli_query($conn, "SELECT * FROM resources WHERE status = 'Available'");

$assignments = mysqli_query($conn, "SELECT sch.*, r.name as resource_name, u.first_name, u.last_name, sr.description 
                                   FROM schedules sch 
                                   JOIN request_assignments ra ON sch.assignment_id = ra.id 
                                   JOIN resources r ON ra.resource_id = r.id 
                                   JOIN service_requests sr ON ra.request_id = sr.id 
                                   JOIN users u ON sr.client_id = u.id 
                                   ORDER BY sch.start_date DESC");
?>
<?php include '../includes/header.php'; ?>
<h1>Logistics & Scheduling</h1>

<div class="card">
    <h3>Assign Resource to Request</h3>
    <form method="POST">
        <div class="form-group">
            <label>Select Approved Request</label>
            <select name="request_id" required>
                <option value="">Select Request</option>
                <?php while($row = mysqli_fetch_assoc($pending_requests)): ?>
                <option value="<?php echo $row['id']; ?>">#<?php echo $row['id']; ?> - <?php echo $row['first_name'] . ' ' . $row['last_name']; ?> - <?php echo $row['resource_type']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Select Resource</label>
            <select name="resource_id" required>
                <option value="">Select Resource</option>
                <?php while($row = mysqli_fetch_assoc($available_resources)): ?>
                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?> (<?php echo $row['type']; ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Start Date & Time</label>
            <input type="datetime-local" name="start_date" required>
        </div>
        <div class="form-group">
            <label>End Date & Time</label>
            <input type="datetime-local" name="end_date" required>
        </div>
        <div class="form-group">
            <label>Delivery Address</label>
            <textarea name="address" rows="3" required></textarea>
        </div>
        <button type="submit" name="assign_resource" class="btn btn-primary">Assign & Schedule</button>
    </form>
</div>

<div class="card">
    <h3>Scheduled Assignments</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Client</th><th>Resource</th><th>Start Date</th><th>End Date</th><th>Address</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($assignments)): ?>
                <tr>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?><br><small><?php echo $row['description']; ?></small></td>
                    <td><?php echo $row['resource_name']; ?></td>
                    <td><?php echo $row['start_date']; ?></td>
                    <td><?php echo $row['end_date']; ?></td>
                    <td><?php echo $row['delivery_address']; ?></td>
                    <td><span class="status status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>