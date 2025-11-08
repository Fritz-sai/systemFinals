<?php
/**
 * Export Sales Report
 * 
 * Exports dashboard data as CSV or PDF
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/admin_functions.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    die('Unauthorized access');
}

$period = $_GET['period'] ?? 'this_month';
$format = $_GET['format'] ?? 'csv';

// Get data
$summary = getDashboardSummary($conn, $period);
$comparison = getMonthComparison($conn);
$topProducts = getTopProductsByQuantity($conn, 5, $period);
$recentTransactions = getRecentTransactions($conn, 50);

if ($format === 'csv') {
    exportCSV($summary, $comparison, $topProducts, $recentTransactions, $period);
} else {
    exportPDF($summary, $comparison, $topProducts, $recentTransactions, $period);
}

function exportCSV($summary, $comparison, $topProducts, $transactions, $period) {
    $filename = 'sales_report_' . $period . '_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['Sales Report - ' . ucfirst(str_replace('_', ' ', $period))]);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Metric', 'Value', 'Change from Last Month']);
    fputcsv($output, ['Total Orders', $summary['orders'], $comparison['changes']['orders'] . '%']);
    fputcsv($output, ['Total Sales', '$' . number_format($summary['sales'], 2), $comparison['changes']['sales'] . '%']);
    fputcsv($output, ['Total Bookings', $summary['bookings'], $comparison['changes']['bookings'] . '%']);
    fputcsv($output, ['Total Profit', '$' . number_format($summary['profit'], 2), $comparison['changes']['profit'] . '%']);
    fputcsv($output, []);
    
    // Top Products
    fputcsv($output, ['TOP PRODUCTS']);
    fputcsv($output, ['Product', 'Quantity Sold', 'Total Revenue', 'Percentage Share']);
    foreach ($topProducts as $product) {
        fputcsv($output, [
            $product['name'],
            $product['quantity_sold'],
            '$' . number_format($product['total_revenue'], 2),
            $product['percentage'] . '%'
        ]);
    }
    fputcsv($output, []);
    
    // Recent Transactions
    fputcsv($output, ['RECENT TRANSACTIONS']);
    fputcsv($output, ['Date', 'Customer', 'Product', 'Amount', 'Status']);
    foreach ($transactions as $transaction) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($transaction['order_date'])),
            $transaction['customer_name'],
            $transaction['product_name'],
            '$' . number_format($transaction['total'], 2),
            $transaction['order_status'] ?? $transaction['status'] ?? 'pending'
        ]);
    }
    
    fclose($output);
    exit;
}

function exportPDF($summary, $comparison, $topProducts, $transactions, $period) {
    // Simple HTML-based PDF export (requires browser print to PDF)
    // For full PDF generation, you'd need a library like TCPDF or FPDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    $periodLabel = ucfirst(str_replace('_', ' ', $period));
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report - ' . htmlspecialchars($periodLabel) . '</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #2a73ff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2a73ff; color: white; }
        .summary { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .summary-item { margin: 10px 0; }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body>
    <h1>Sales Report - ' . htmlspecialchars($periodLabel) . '</h1>
    <p>Generated: ' . date('Y-m-d H:i:s') . '</p>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-item"><strong>Total Orders:</strong> ' . $summary['orders'] . ' (' . ($comparison['changes']['orders'] >= 0 ? '+' : '') . $comparison['changes']['orders'] . '%)</div>
        <div class="summary-item"><strong>Total Sales:</strong> $' . number_format($summary['sales'], 2) . ' (' . ($comparison['changes']['sales'] >= 0 ? '+' : '') . $comparison['changes']['sales'] . '%)</div>
        <div class="summary-item"><strong>Total Bookings:</strong> ' . $summary['bookings'] . ' (' . ($comparison['changes']['bookings'] >= 0 ? '+' : '') . $comparison['changes']['bookings'] . '%)</div>
        <div class="summary-item"><strong>Total Profit:</strong> $' . number_format($summary['profit'], 2) . ' (' . ($comparison['changes']['profit'] >= 0 ? '+' : '') . $comparison['changes']['profit'] . '%)</div>
    </div>
    
    <h2>Top Products</h2>
    <table>
        <tr>
            <th>Product</th>
            <th>Quantity Sold</th>
            <th>Total Revenue</th>
            <th>% Share</th>
        </tr>';
    
    foreach ($topProducts as $product) {
        echo '<tr>
            <td>' . htmlspecialchars($product['name']) . '</td>
            <td>' . $product['quantity_sold'] . '</td>
            <td>$' . number_format($product['total_revenue'], 2) . '</td>
            <td>' . $product['percentage'] . '%</td>
        </tr>';
    }
    
    echo '</table>
    
    <h2>Recent Transactions</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>';
    
    foreach ($transactions as $transaction) {
        echo '<tr>
            <td>' . date('Y-m-d H:i:s', strtotime($transaction['order_date'])) . '</td>
            <td>' . htmlspecialchars($transaction['customer_name']) . '</td>
            <td>' . htmlspecialchars($transaction['product_name']) . '</td>
            <td>$' . number_format($transaction['total'], 2) . '</td>
            <td>' . htmlspecialchars($transaction['order_status'] ?? $transaction['status'] ?? 'pending') . '</td>
        </tr>';
    }
    
    echo '</table>
    
    <button onclick="window.print()">Print / Save as PDF</button>
</body>
</html>';
    
    exit;
}

?>

