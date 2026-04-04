<?php
include '../includes/session.php';
redirectIfNotClient();
include '../database.php';

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    mysqli_query($conn, "UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email' WHERE id=$user_id");
    
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['email'] = $email;
    
    echo "<script>showAlert('Profile updated successfully!', 'success');</script>";
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
}
?>
<?php include '../includes/header.php'; ?>
<h1>My Profile</h1>

<div class="card">
    <form method="POST">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" required>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<div class="card">
    <h3>Account Information</h3>
    <p><strong>Role:</strong> <?php echo $user['role']; ?></p>
    <p><strong>Member Since:</strong> <?php echo $user['created_at']; ?></p>
</div>
<?php include '../includes/footer.php'; ?>