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
    <title>FAQ - AgriRMS</title>
    <style>
        body{font-family:Inter,Arial,sans-serif;background:#f5f7f5;color:#1a2e1f;margin:0}
        .wrap{max-width:900px;margin:2rem auto;padding:0 1rem}
        .card{background:#fff;border:1px solid #e4ede4;border-radius:16px;padding:1.2rem;margin-bottom:1rem}
        a{color:#1B4F2B}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Frequently Asked Questions</h1>
    <p><a href="index.php">← Back to Home</a></p>
    <div class="card"><h3>How do approvals work?</h3><p>Admins review requests and move them through Pending, Approved, Processing, Delivered, and Returned states.</p></div>
    <div class="card"><h3>Can I download invoices as PDF?</h3><p>Open an invoice and use the Print / Save as PDF button to save a PDF copy from your browser.</p></div>
    <div class="card"><h3>Where can I track notifications?</h3><p>Both admin and client dashboards include a Notifications page where unread items can be marked as read.</p></div>
</div>
</body>
</html>
