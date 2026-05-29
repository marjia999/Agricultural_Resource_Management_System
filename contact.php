<?php
include 'includes/session.php';
include 'database.php';
include 'includes/security.php';
include 'includes/mailer.php';

setSecurityHeaders();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token. Please refresh and try again.';
    } else {
        $name = cleanText($_POST['name'] ?? '', 100);
        $email = trim($_POST['email'] ?? '');
        $subject = cleanText($_POST['subject'] ?? '', 255);
        $message = cleanText($_POST['message'] ?? '', 3000);

        if ($name === '' || $email === '' || $subject === '' || $message === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } else {
            $stmt = mysqli_prepare($conn, 'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $subject, $message);
                if (mysqli_stmt_execute($stmt)) {
                    $to = getenv('AGRI_ADMIN_EMAIL') ?: '';
                    $mail_subject = 'New Contact Message: ' . $subject;
                    $mail_body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
                    if ($to !== '') {
                        sendPlatformEmail($to, $mail_subject, $mail_body, $email);
                    }
                    $success = 'Thanks! Your message was sent successfully.';
                } else {
                    $error = 'Failed to save your message. Please try again.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = 'Contact messages table is missing. Please update database schema.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - AgriRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{font-family:Inter,sans-serif;margin:0;background:#f5f7f5;color:#1a2e1f}
        .header{background:#1B4F2B;padding:1rem 5%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
        .header a{color:#fff;text-decoration:none;margin-left:1rem}
        .logo h2{margin:0;color:#FF8C42}.logo p{margin:0;color:#f0f7f0;font-size:.75rem}
        .main{max-width:900px;margin:0 auto;padding:2rem 1rem}
        .card{background:#fff;border:1px solid #e8f0e8;border-radius:18px;padding:1.5rem}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
        .field{margin-bottom:1rem}.field label{display:block;margin-bottom:.4rem;font-weight:600}
        .field input,.field textarea{width:100%;padding:.8rem;border:1px solid #dbe9db;border-radius:10px}
        .btn{background:#FF8C42;color:#fff;border:none;border-radius:10px;padding:.75rem 1.2rem;cursor:pointer}
        .alert{padding:.8rem 1rem;border-radius:10px;margin-bottom:1rem}.ok{background:#d4edda;color:#155724}.err{background:#f8d7da;color:#721c24}
        .footer{background:#0d2b18;color:#c0ddc0;text-align:center;padding:1rem;margin-top:2rem}
        @media(max-width:768px){.grid{grid-template-columns:1fr}.header{padding:1rem}}
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><h2><i class="fas fa-leaf"></i> AgriRMS</h2><p>Agricultural Resource Management System</p></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#about">About</a>
        <a href="faq.php">FAQ</a>
        <a href="terms.php">Terms</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo isAdmin() ? 'admin/dashboard.php' : 'client/dashboard.php'; ?>">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main class="main">
    <div class="card">
        <h1><i class="fas fa-envelope"></i> Contact Us</h1>
        <p>Send your questions, support requests, or feedback.</p>

        <?php if ($success): ?><div class="alert ok"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <?php echo csrfInput(); ?>
            <div class="grid">
                <div class="field">
                    <label>Name</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="field">
                <label>Subject</label>
                <input type="text" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
            </div>
            <div class="field">
                <label>Message</label>
                <textarea name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button class="btn" type="submit"><i class="fas fa-paper-plane"></i> Send Message</button>
        </form>
    </div>
</main>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> AgriRMS.</p>
    <p>
        <a href="https://facebook.com" aria-label="Facebook" style="color:#c0ddc0;"><i class="fab fa-facebook"></i></a>
        <a href="https://linkedin.com" aria-label="LinkedIn" style="color:#c0ddc0; margin-left:8px;"><i class="fab fa-linkedin"></i></a>
        <a href="https://youtube.com" aria-label="YouTube" style="color:#c0ddc0; margin-left:8px;"><i class="fab fa-youtube"></i></a>
    </p>
</footer>
</body>
</html>
