<?php
session_start();
include '../database.php';
include '../includes/security.php';

setSecurityHeaders();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}

$rangeStart = $from . ' 00:00:00';
$rangeEnd = $to . ' 23:59:59';

$summaryStmt = mysqli_prepare($conn, "SELECT
    COUNT(DISTINCT sr.id) AS total_requests,
    COUNT(DISTINCT CASE WHEN sr.request_status='Approved' THEN sr.id END) AS approved_requests,
    COUNT(DISTINCT CASE WHEN sr.request_status='Cancelled' THEN sr.id END) AS cancelled_requests,
    COALESCE(SUM(CASE WHEN p.payment_status='Paid' THEN p.total_amount ELSE 0 END),0) AS paid_revenue,
    COALESCE(SUM(CASE WHEN p.payment_status!='Paid' THEN p.due_amount ELSE 0 END),0) AS due_revenue
    FROM service_requests sr
    LEFT JOIN payments p ON p.booking_id = sr.id
    WHERE sr.created_at BETWEEN ? AND ?");
mysqli_stmt_bind_param($summaryStmt, 'ss', $rangeStart, $rangeEnd);
mysqli_stmt_execute($summaryStmt);
$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summary = $summaryResult ? mysqli_fetch_assoc($summaryResult) : [];
mysqli_stmt_close($summaryStmt);

$resourceStmt = mysqli_prepare($conn, "SELECT r.name, r.model, COUNT(sr.id) AS total_bookings, COALESCE(SUM(sr.total_cost),0) AS gross_value
FROM resources r
LEFT JOIN service_requests sr ON sr.resource_id = r.id AND sr.created_at BETWEEN ? AND ?
GROUP BY r.id
ORDER BY total_bookings DESC, gross_value DESC
LIMIT 10");
mysqli_stmt_bind_param($resourceStmt, 'ss', $rangeStart, $rangeEnd);
mysqli_stmt_execute($resourceStmt);
$resourceResult = mysqli_stmt_get_result($resourceStmt);
$topResources = [];
while ($resourceResult && ($row = mysqli_fetch_assoc($resourceResult))) {
    $topResources[] = $row;
}
mysqli_stmt_close($resourceStmt);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="agrirms-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Metric', 'Value']);
    fputcsv($out, ['Total Requests', $summary['total_requests'] ?? 0]);
    fputcsv($out, ['Approved Requests', $summary['approved_requests'] ?? 0]);
    fputcsv($out, ['Cancelled Requests', $summary['cancelled_requests'] ?? 0]);
    fputcsv($out, ['Collected Revenue', $summary['paid_revenue'] ?? 0]);
    fputcsv($out, ['Outstanding Due', $summary['due_revenue'] ?? 0]);
    fputcsv($out, []);
    fputcsv($out, ['Top Resources']);
    fputcsv($out, ['Resource', 'Model', 'Bookings', 'Gross Value']);
    foreach ($topResources as $resource) {
        fputcsv($out, [$resource['name'], $resource['model'], $resource['total_bookings'], $resource['gross_value']]);
    }
    fclose($out);
    exit();
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reports - AgriRMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:Inter,sans-serif;margin:0;background:#f5f7f5;color:#1a2e1f}.wrap{max-width:1100px;margin:2rem auto;padding:0 1rem}.top{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}.btn{background:#1B4F2B;color:#fff;padding:.6rem 1rem;border-radius:10px;text-decoration:none;border:none;cursor:pointer}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin:1rem 0}.card{background:#fff;border:1px solid #e8f0e8;border-radius:14px;padding:1rem}.metric{font-size:1.5rem;font-weight:700;color:#1B4F2B}.table{background:#fff;border:1px solid #e8f0e8;border-radius:14px;padding:1rem}table{width:100%;border-collapse:collapse}th,td{padding:.7rem;border-bottom:1px solid #ecf3ec;text-align:left}.filter{background:#fff;border:1px solid #e8f0e8;border-radius:14px;padding:1rem;display:flex;gap:1rem;align-items:end;flex-wrap:wrap}.field{display:flex;flex-direction:column;gap:.3rem}.field input{padding:.5rem;border:1px solid #cfe0cf;border-radius:8px}</style></head><body>
<div class="wrap">
<div class="top"><h1><i class="fas fa-chart-line"></i> Reports</h1><div><a class="btn" href="notifications.php">Notifications</a> <a class="btn" href="dashboard.php">Dashboard</a></div></div>
<form class="filter" method="get">
<div class="field"><label>From</label><input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>"></div>
<div class="field"><label>To</label><input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>"></div>
<button class="btn" type="submit">Apply</button>
<a class="btn" href="reports.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=csv">Export CSV</a>
</form>
<div class="grid">
<div class="card"><div>Total Requests</div><div class="metric"><?php echo (int)($summary['total_requests'] ?? 0); ?></div></div>
<div class="card"><div>Approved</div><div class="metric"><?php echo (int)($summary['approved_requests'] ?? 0); ?></div></div>
<div class="card"><div>Cancelled</div><div class="metric"><?php echo (int)($summary['cancelled_requests'] ?? 0); ?></div></div>
<div class="card"><div>Collected Revenue</div><div class="metric">৳ <?php echo number_format((float)($summary['paid_revenue'] ?? 0),2); ?></div></div>
<div class="card"><div>Outstanding Due</div><div class="metric">৳ <?php echo number_format((float)($summary['due_revenue'] ?? 0),2); ?></div></div>
</div>
<div class="table">
<h3>Top Resources</h3>
<table><thead><tr><th>Resource</th><th>Model</th><th>Bookings</th><th>Gross Value</th></tr></thead><tbody>
<?php if (!$topResources): ?><tr><td colspan="4">No resource activity in this range.</td></tr><?php endif; ?>
<?php foreach ($topResources as $resource): ?>
<tr>
<td><?php echo htmlspecialchars($resource['name']); ?></td>
<td><?php echo htmlspecialchars($resource['model']); ?></td>
<td><?php echo (int)$resource['total_bookings']; ?></td>
<td>৳ <?php echo number_format((float)$resource['gross_value'],2); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div></body></html>
