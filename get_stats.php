<?php
include 'includes/security.php';
setSecurityHeaders();

header('Content-Type: application/json');
include 'database.php';

$resources = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM resources"))['count'];
$clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Client'"))['count'];
$requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM service_requests WHERE request_status = 'Returned'"))['count'];

echo json_encode([
    'resources' => $resources,
    'clients' => $clients,
    'requests' => $requests
]);
?>