<?php
session_start();
include 'database.php';

// Helper functions for security
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'Admin';
}

function cleanText($input, $maxLength = 100) {
    $cleaned = trim(strip_tags($input));
    return substr($cleaned, 0, $maxLength);
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !$token) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            color: #1a2e1f;
            line-height: 1.5;
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

        .nav-links a:not(.btn-login):not(.btn-register):hover {
            color: #FFD966 !important;
            transform: translateY(-1px);
        }

        .btn-login {
            background: transparent;
            border: 2px solid #FF8C42;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
        }

        .btn-login:hover {
            background: #FF8C42;
            color: #1B4F2B !important;
            transform: translateY(-2px);
        }

        .btn-register {
            background: #FF8C42;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: #1B4F2B !important;
        }

        .btn-register:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #1B4F2B 0%, #0d3b1a 100%);
            padding: 3rem 5%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #fef9e6;
            font-size: 1rem;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        /* Contact Info Cards */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,140,66,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-icon i {
            font-size: 1.8rem;
            color: #FF8C42;
        }

        .info-content h3 {
            color: #1B4F2B;
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
        }

        .info-content p {
            color: #666;
            font-size: 0.85rem;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
        }

        .contact-form h2 {
            color: #1B4F2B;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
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
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,140,66,0.3);
        }

        /* Map Section */
        .map-section {
            margin-top: 3rem;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #e8f0e8;
        }

        .map-section iframe {
            width: 100%;
            height: 300px;
            border: none;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #fee2e2;
            color: #c0392b;
            border-left: 4px solid #c0392b;
        }

        /* Footer */
        .footer {
            background: #0d2b18;
            color: white;
            padding: 3rem 5% 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding-bottom: 2rem;
        }

        .footer-brand h3 {
            color: #FFD966;
            margin-bottom: 0.5rem;
        }

        .footer-brand p {
            color: #f0f7f0;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            gap: 0.8rem;
        }

        .social-links a {
            width: 35px;
            height: 35px;
            background: #1a3a2b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .social-links a:hover {
            background: #FFD966;
            color: #1B4F2B;
        }

        .footer-links h4, .footer-contact h4, .footer-newsletter h4 {
            color: #FFD966;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .footer-links a {
            display: block;
            color: #e0f0e0;
            text-decoration: none;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            transition: 0.3s;
        }

        .footer-links a:hover {
            color: #FFD966;
        }

        .footer-contact p {
            color: #e0f0e0;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .footer-contact p i {
            width: 25px;
            color: #FFD966;
        }

        .footer-newsletter p {
            color: #e0f0e0;
            font-size: 0.85rem;
            margin-bottom: 0.8rem;
        }

        .newsletter-form {
            display: flex;
            gap: 0.5rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 0.6rem;
            border: none;
            border-radius: 8px;
            background: #1a3a2b;
            color: white;
        }

        .newsletter-form input::placeholder {
            color: #c0ddc0;
        }

        .newsletter-form button {
            background: #FFD966;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            color: #1B4F2B;
        }

        .newsletter-form button:hover {
            background: #FF8C42;
            transform: scale(1.02);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #2a5a3a;
            color: #c0ddc0;
            font-size: 0.8rem;
        }

        .footer-bottom i {
            color: #FFD966;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            .contact-grid {
                grid-template-columns: 1fr;
            }
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .social-links {
                justify-content: center;
            }
            .newsletter-form {
                max-width: 300px;
                margin: 0 auto;
            }
            .page-header h1 {
                font-size: 1.8rem;
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
            <a href="index.php">Home</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#about">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you. Get in touch with our team.</p>
    </div>

    <div class="container">
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Visit Us</h3>
                        <p>123 Agricultural Street,<br>Dhaka, Bangladesh</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Call Us</h3>
                        <p>+880 1234 567890<br>+880 1234 567891</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Us</h3>
                        <p>info@agrirms.com<br>support@agrirms.com</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <p>Sunday - Thursday: 9:00 AM - 6:00 PM<br>Friday - Saturday: Closed</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2><i class="fas fa-paper-plane"></i> Send Us a Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?php echo csrfInput(); ?>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Your Name</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Subject</label>
                        <input type="text" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Message</label>
                        <textarea name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Google Map -->
        <div class="map-section">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3652.023546556839!2d90.40659631498273!3d23.750586894575!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b898991d9b4d%3A0x92b3d6c9c0b7a9f!2sDhaka!5e0!3m2!1sen!2sbd!4v1647899999999!5m2!1sen!2sbd" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <h3><i class="fas fa-leaf"></i> AgriRMS</h3>
                <p>Agricultural Resource Management System</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="index.php">Home</a>
                <a href="index.php#services">Services</a>
                <a href="index.php#about">About</a>
                <a href="contact.php">Contact</a>
                <a href="terms.php">Terms & Conditions</a>
                <a href="privacy.php">Privacy Policy</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Info</h4>
                <p><i class="fas fa-map-marker-alt"></i> 123 Agricultural Street, Dhaka</p>
                <p><i class="fas fa-phone"></i> +880 1234 567890</p>
                <p><i class="fas fa-envelope"></i> info@agrirms.com</p>
            </div>
            <div class="footer-newsletter">
                <h4>Newsletter</h4>
                <p>Subscribe for updates</p>
                <div class="newsletter-form">
                    <input type="email" placeholder="Your email">
                    <button><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> AgriRMS. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
        </div>
    </footer>
</body>
</html>
