<?php
include 'includes/session.php';
include 'database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: client/dashboard.php");
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'Admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: client/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgriRMS</title>
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

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        .auth-container {
            background: white;
            border-radius: 32px;
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
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

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h2 {
            color: #1B4F2B;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #666;
            font-size: 0.9rem;
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

        .form-group label i {
            color: #FF8C42;
            width: 20px;
            margin-right: 5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF8C42;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 2px solid #e8f0e8;
            border-radius: 16px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            background: #fefefe;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255,140,66,0.1);
        }

        .form-group input::placeholder {
            color: #bbb;
        }

        .btn-primary {
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
            width: 100%;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,140,66,0.3);
            background: linear-gradient(135deg, #e67e22, #d35400);
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

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e8f0e8;
        }

        .auth-footer p {
            color: #666;
            font-size: 0.85rem;
        }

        .auth-footer a {
            color: #FF8C42;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .auth-footer a:hover {
            color: #e67e22;
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background: #0d2b18;
            color: white;
            padding: 3rem 5% 1rem;
            margin-top: auto;
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
            font-weight: 400;
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
            .auth-container {
                padding: 1.8rem;
                margin: 1rem;
            }
            .auth-header h2 {
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
            <a href="index.php">Home</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#about">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="auth-container">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Login to access your AgriRMS dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Create an account</a></p>
            </div>
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
            </div>
            <div class="footer-contact">
                <h4>Contact Info</h4>
                <p><i class="fas fa-map-marker-alt"></i> 123 Agricultural Street, Dhaka</p>
                <p><i class="fas fa-phone"></i> +597 853 2905</p>
                <p><i class="fas fa-envelope"></i> agrirms@gmail.com</p>
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
            <p>&copy; 2024 AgriRMS. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>