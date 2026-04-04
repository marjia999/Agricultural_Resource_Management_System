<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriRMS - Agricultural Resource Management System</title>
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

        /* Bright white for the subtitle */
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

        /* Hover effect for nav links: bright YELLOW (#FFD966) for better visibility */
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

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1B4F2B 0%, #0d3b1a 100%);
            padding: 4rem 5% 6rem;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 3rem;
        }

        .hero-text {
            flex: 1;
            min-width: 300px;
        }

        .hero-badge {
            background: rgba(255,140,66,0.2);
            color: #FFD966;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .hero-text h1 {
            font-size: 3rem;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-text p {
            color: #fef9e6;
            font-size: 1rem;
            margin-bottom: 2rem;
            max-width: 500px;
            font-weight: 400;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary-large {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.8rem 1.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-primary-large:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .btn-outline-large {
            background: transparent;
            border: 2px solid #FF8C42;
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-outline-large:hover {
            background: #FF8C42;
            color: #1B4F2B;
            transform: translateY(-2px);
        }

        .hero-stats {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat h3 {
            font-size: 2rem;
            color: #FFD966;
        }

        .hero-stat p {
            color: #fef9e6;
            font-size: 0.8rem;
            font-weight: 400;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        /* Section Header */
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-tag {
            color: #FF8C42;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .section-header h2 {
            font-size: 2.2rem;
            color: #1B4F2B;
            margin-bottom: 0.5rem;
        }

        .section-header p {
            color: #555;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            transition: 0.3s;
            border: 1px solid #e8f0e8;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #FF8C42;
        }

        .service-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,140,66,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .service-icon i {
            font-size: 1.8rem;
            color: #FF8C42;
        }

        .service-card h3 {
            color: #1B4F2B;
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
        }

        .service-card p {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .service-link {
            color: #FF8C42;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }

        .service-link:hover {
            gap: 10px;
        }

        /* About Section - FIXED equal height with proper image */
        .about-section {
            margin: 4rem 0;
        }

        .about-grid {
            display: flex;
            gap: 3rem;
            align-items: stretch;
        }

        .about-image {
            flex: 1;
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            border-radius: 30px;
            overflow: hidden;
            position: relative;
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Real image from images/index.jpg */
        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .about-image:hover img {
            transform: scale(1.02);
        }

        .about-content {
            flex: 1;
            background: white;
            border-radius: 30px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .about-content .section-tag {
            margin-bottom: 0.5rem;
        }

        .about-content h2 {
            font-size: 2rem;
            color: #1B4F2B;
            margin-bottom: 1rem;
        }

        .about-content > p {
            color: #444;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .mission-vision {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .mv-item {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .mv-item i {
            font-size: 1.5rem;
            color: #FF8C42;
        }

        .mv-item h4 {
            color: #1B4F2B;
            margin-bottom: 0.3rem;
        }

        .mv-item p {
            color: #555;
            font-size: 0.9rem;
        }

        .why-choose h4 {
            color: #1B4F2B;
            margin-bottom: 1rem;
        }

        .why-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .why-grid span {
            background: #f0f7f0;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            color: #1B4F2B;
        }

        .why-grid span i {
            color: #FF8C42;
            margin-right: 5px;
        }

        /* How It Works */
        .how-it-works {
            margin: 4rem 0;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .step-card {
            text-align: center;
            padding: 2rem 1rem;
            background: white;
            border-radius: 20px;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: 0.3s;
        }

        .step-card:hover {
            transform: translateY(-5px);
            border-color: #FF8C42;
        }

        .step-number {
            position: absolute;
            top: -15px;
            left: 20px;
            background: #FF8C42;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .step-icon {
            width: 70px;
            height: 70px;
            background: rgba(255,140,66,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem auto;
        }

        .step-icon i {
            font-size: 2rem;
            color: #FF8C42;
        }

        .step-card h3 {
            color: #1B4F2B;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .step-card p {
            color: #555;
            font-size: 0.85rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #1B4F2B, #0d3b1a);
            border-radius: 30px;
            padding: 4rem;
            text-align: center;
            margin-top: 3rem;
        }

        .cta-content h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta-content p {
            color: #fef9e6;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .btn-cta {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-cta:hover {
            background: #e67e22;
            transform: translateY(-2px);
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

        /* Responsive */
        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
            .steps-grid {
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
            .hero-text h1 {
                font-size: 2rem;
            }
            .hero-stats {
                width: 100%;
                justify-content: center;
            }
            .about-grid {
                flex-direction: column;
                gap: 2rem;
            }
            .about-image {
                min-height: 280px;
            }
            .about-content {
                padding: 1.5rem;
            }
            .steps-grid {
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
        }

        /* Additional bright white for any other white text elements */
        .hero-text h1, .cta-content h2, .footer-brand h3 {
            letter-spacing: -0.2px;
        }
        .hero-stat p, .cta-content p, .footer-brand p {
            opacity: 0.95;
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
            <a href="#home">Home</a>
            <a href="#services">Services</a>
            <a href="#about">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text">
                <span class="hero-badge">Welcome to AgriRMS</span>
                <h1>Smart Agricultural<br>Resource Management</h1>
                <p>Efficiently manage agricultural resources, equipment, and services all in one platform. Streamline your farming operations with our comprehensive solution.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn-primary-large">Get Started <i class="fas fa-arrow-right"></i></a>
                    <a href="#services" class="btn-outline-large">Learn More <i class="fas fa-play"></i></a>
                </div>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <h3 id="resource-count">0</h3>
                    <p>Resources Available</p>
                </div>
                <div class="hero-stat">
                    <h3 id="client-count">0</h3>
                    <p>Happy Clients</p>
                </div>
                <div class="hero-stat">
                    <h3 id="request-count">0</h3>
                    <p>Services Completed</p>
                </div>
            </div>
        </div>
        <div class="hero-shape"></div>
    </section>

    <div class="container">
        <!-- Services Section -->
        <section id="services" class="services-section">
            <div class="section-header">
                <span class="section-tag">What We Offer</span>
                <h2>Our Services</h2>
                <p>Comprehensive solutions for all your agricultural resource needs</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tractor"></i>
                    </div>
                    <h3>Resource Management</h3>
                    <p>Track and manage agricultural machinery, storage units, and equipment efficiently.</p>
                    <a href="#" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>Service Requests</h3>
                    <p>Submit and track service requests with real-time status updates and notifications.</p>
                    <a href="#" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Logistics & Scheduling</h3>
                    <p>Efficient delivery scheduling and resource allocation system for timely operations.</p>
                    <a href="#" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Billing System</h3>
                    <p>Easy payments via mobile banking (bKash, Rocket, Nagad) or credit card.</p>
                    <a href="#" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics Dashboard</h3>
                    <p>Real-time insights and reports for better decision making and planning.</p>
                    <a href="#" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Dedicated customer support team available round the clock for assistance.</p>
                    <a href="#" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <!-- About Section - Now with actual image from images/index.jpg and equal height -->
        <section id="about" class="about-section">
            <div class="about-grid">
                <div class="about-image">
                    <img src="images/index.jpg" alt="Agriculture farming scene" onerror="this.onerror=null; this.src='https://placehold.co/600x400/1B4F2B/FFD966?text=AgriRMS+Image';">
                </div>
                <div class="about-content">
                    <span class="section-tag">About Us</span>
                    <h2>Empowering Agriculture Through Technology</h2>
                    <p>AgriRMS is a comprehensive platform designed to streamline agricultural resource management, connecting farmers and agricultural businesses with the resources they need to maximize productivity.</p>
                    
                    <div class="mission-vision">
                        <div class="mv-item">
                            <i class="fas fa-bullseye"></i>
                            <div>
                                <h4>Our Mission</h4>
                                <p>Simplify agricultural resource management through innovative technology solutions.</p>
                            </div>
                        </div>
                        <div class="mv-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h4>Our Vision</h4>
                                <p>To become the leading platform for sustainable agricultural resource management.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="why-choose">
                        <h4>Why Choose Us</h4>
                        <div class="why-grid">
                            <span><i class="fas fa-check-circle"></i> Real-time tracking</span>
                            <span><i class="fas fa-check-circle"></i> Fast processing</span>
                            <span><i class="fas fa-check-circle"></i> Secure payments</span>
                            <span><i class="fas fa-check-circle"></i> 24/7 support</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="how-it-works">
            <div class="section-header">
                <span class="section-tag">Simple Process</span>
                <h2>How It Works</h2>
                <p>Get started in just 4 easy steps</p>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                    <h3>Register Account</h3>
                    <p>Create your account as a client to access all services</p>
                </div>
                <div class="step-card">
                    <div class="step-number">02</div>
                    <div class="step-icon"><i class="fas fa-paper-plane"></i></div>
                    <h3>Submit Request</h3>
                    <p>Request machinery, storage, or equipment services</p>
                </div>
                <div class="step-card">
                    <div class="step-number">03</div>
                    <div class="step-icon"><i class="fas fa-check-double"></i></div>
                    <h3>Get Assigned</h3>
                    <p>Admin assigns resources and schedules delivery</p>
                </div>
                <div class="step-card">
                    <div class="step-number">04</div>
                    <div class="step-icon"><i class="fas fa-credit-card"></i></div>
                    <h3>Make Payment</h3>
                    <p>Complete payment via your preferred method</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="cta-content">
                <h2>Ready to Transform Your Agricultural Operations?</h2>
                <p>Join thousands of satisfied clients already using AgriRMS</p>
                <a href="register.php" class="btn-cta">Create Free Account <i class="fas fa-arrow-right"></i></a>
            </div>
        </section>
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
                <a href="#home">Home</a>
                <a href="#services">Services</a>
                <a href="#about">About</a>
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

    <script>
        function animateCounter(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.innerText = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Fetch stats or fallback to demo numbers
        fetch('get_stats.php')
            .then(response => response.json())
            .then(data => {
                animateCounter(document.getElementById('resource-count'), 0, data.resources, 1500);
                animateCounter(document.getElementById('client-count'), 0, data.clients, 1500);
                animateCounter(document.getElementById('request-count'), 0, data.requests, 1500);
            })
            .catch(error => {
                // fallback numbers for demo (visually appealing)
                animateCounter(document.getElementById('resource-count'), 0, 156, 1500);
                animateCounter(document.getElementById('client-count'), 0, 234, 1500);
                animateCounter(document.getElementById('request-count'), 0, 189, 1500);
            });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>