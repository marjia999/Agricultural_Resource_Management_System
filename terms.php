<?php
include 'includes/session.php';
include 'includes/security.php';

setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Privacy - AgriRMS</title>
    <style>
        body{font-family:Inter,Arial,sans-serif;background:#f5f7f5;color:#1a2e1f;margin:0}
        .wrap{max-width:900px;margin:2rem auto;padding:0 1rem}
        .card{background:#fff;border:1px solid #e4ede4;border-radius:16px;padding:1.2rem;margin-bottom:1rem}
        a{color:#1B4F2B}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Terms & Privacy</h1>
    <p><a href="index.php">← Back to Home</a></p>
    <div class="card">
        <h3>Usage Terms</h3>
        <p>Users must provide accurate booking and payment information and use the platform only for lawful agricultural service requests.</p>
    </div>
    <div class="card">
        <h3>Payment and Invoice Policy</h3>
        <p>Payments are recorded against approved bookings. Invoices can be viewed online and shared through the built-in email action.</p>
    </div>
    <div class="card">
        <h3>Privacy</h3>
        <p>AgriRMS stores profile, booking, and payment data to deliver services and does not intentionally expose personal data to public users.</p>
    </div>
</div>
</body>
</html>
