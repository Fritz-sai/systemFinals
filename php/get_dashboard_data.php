<?php
/**
 * API Endpoint for Dashboard Data
 * Returns JSON data for the enhanced dashboard based on period filter
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/admin_functions.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$period = $_GET['period'] ?? 'this_month';

// Get data for the selected period
$summary = getDashboardSummary($conn, $period);
$comparison = getMonthComparison($conn);
$topProducts = getTopProductsByQuantity($conn, 5, $period);

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'comparison' => $comparison,
    'topProducts' => $topProducts
]);

?>

