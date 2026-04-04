<?php
include 'includes/session.php';
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

        .nav-links a:not(.btn-login):not(.btn-register):not(.btn-danger):hover {
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

        .btn-danger {
            background: #dc3545;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
        }

        .btn-danger:hover {
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

        .contact-container {
            background: white;
            border-radius: 32px;
            padding: 2.5rem;
            width: 100%;
            max-width: 800px;
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

        .contact-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .contact-header h2 {
            color: #1B4F2B;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .contact-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 0;
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

        .form-group label span {
            color: #FF8C42;
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

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 2px solid #e8f0e8;
            border-radius: 16px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            background: #fefefe;
        }

        .form-group textarea {
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255,140,66,0.1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
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
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-success i {
            color: #28a745;
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
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
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
            .contact-container {
                padding: 1.8rem;
                margin: 1rem;
            }
            .contact-header h2 {
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
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="client/dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-danger">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="main-content">
        <div class="contact-container">
            <div class="contact-header">
                <h2>Contact Us</h2>
                <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>

            <form id="contactForm" onsubmit="return submitContactForm()">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="contact_name" required placeholder="Enter your full name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="contact_email" required placeholder="Enter your email">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="contact_phone" placeholder="Enter your phone number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <div class="input-wrapper">
                            <i class="fas fa-tag"></i>
                            <input type="text" id="contact_subject" placeholder="What is this regarding?">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message <span>*</span></label>
                    <div class="input-wrapper">
                        <textarea id="contact_message" rows="6" required placeholder="Tell us about your inquiry..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
                <div id="contactResponse"></div>
            </form>
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
                <p><i class="fas fa-map-marker-alt"></i> 123 Agricultural Street, Dhaka, Bangladesh</p>
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
            <p>&copy; 2024 AgriRMS. All rights reserved. | Designed with <i class="fas fa-heart"></i> for agriculture</p>
        </div>
    </footer>

    <script>
        function submitContactForm() {
            const name = document.getElementById('contact_name').value;
            const email = document.getElementById('contact_email').value;
            const message = document.getElementById('contact_message').value;
            
            if (!name || !email || !message) {
                document.getElementById('contactResponse').innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> Please fill all required fields!</div>';
                return false;
            }
            
            document.getElementById('contactResponse').innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Thank you! We will respond within 24 hours.</div>';
            
            document.getElementById('contact_name').value = '';
            document.getElementById('contact_email').value = '';
            document.getElementById('contact_phone').value = '';
            document.getElementById('contact_subject').value = '';
            document.getElementById('contact_message').value = '';
            
            setTimeout(() => {
                document.getElementById('contactResponse').innerHTML = '';
            }, 5000);
            
            return false;
        }
    </script>
</body>
</html>
