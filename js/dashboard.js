/**
 * Enhanced Dashboard JavaScript
 * 
 * Handles:
 * - Period filter (This Month, Last Month, All Time)
 * - Chart rendering and updates
 * - Export functionality
 * - Dynamic data loading
 */

document.addEventListener('DOMContentLoaded', () => {
    if (typeof salesData === 'undefined' || typeof Chart === 'undefined') {
        return;
    }

    // Initialize charts
    initializeCharts();

    // Period filter handler
    const periodFilter = document.getElementById('period-filter');
    if (periodFilter) {
        periodFilter.addEventListener('change', handlePeriodChange);
    }

    // Export button handler
    const exportBtn = document.getElementById('export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', handleExport);
    }
});

let monthComparisonChart = null;
let topProductsChart = null;

/**
 * Initialize all charts
 */
function initializeCharts() {
    initializeMonthComparisonChart();
    initializeTopProductsChart();
}

/**
 * Initialize Month Comparison Chart
 */
function initializeMonthComparisonChart() {
    const ctx = document.getElementById('monthComparisonChart');
    if (!ctx || !salesData.monthComparison) return;

    const data = salesData.monthComparison;
    const labels = ['Orders', 'Sales ($)', 'Pending Bookings', 'Completed Bookings', 'Profit ($)'];
    const thisMonthData = [
        data.this_month.sold_orders,
        data.this_month.sales,
        data.this_month.pending_bookings || 0,
        data.this_month.completed_bookings || 0,
        data.this_month.profit
    ];
    const lastMonthData = [
        data.last_month.sold_orders,
        data.last_month.sales,
        data.last_month.pending_bookings || 0,
        data.last_month.completed_bookings || 0,
        data.last_month.profit
    ];

    monthComparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'This Month',
                    data: thisMonthData,
                    backgroundColor: 'rgba(42, 115, 255, 0.7)',
                    borderColor: '#2a73ff',
                    borderWidth: 1
                },
                {
                    label: 'Last Month',
                    data: lastMonthData,
                    backgroundColor: 'rgba(66, 186, 150, 0.7)',
                    borderColor: '#42ba96',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            // Format based on label
                            const label = this.chart.data.labels[index];
                            if (label.includes('$')) {
                                return '$' + value.toFixed(0);
                            }
                            return value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y;
                            const dataLabel = context.label;
                            if (dataLabel.includes('$')) {
                                return label + ': $' + value.toFixed(2);
                            }
                            return label + ': ' + value;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize Top Products Pie Chart
 */
function initializeTopProductsChart() {
    const ctx = document.getElementById('topProductsChart');
    if (!ctx || !salesData.topProductsByQuantity || salesData.topProductsByQuantity.length === 0) return;

    const products = salesData.topProductsByQuantity;
    const labels = products.map(p => p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name);
    const quantities = products.map(p => p.quantity_sold);
    const colors = [
        'rgba(42, 115, 255, 0.8)',
        'rgba(66, 186, 150, 0.8)',
        'rgba(255, 152, 0, 0.8)',
        'rgba(255, 193, 7, 0.8)',
        'rgba(156, 39, 176, 0.8)'
    ];

    topProductsChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: quantities,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value + ' units (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Handle period filter change
 */
async function handlePeriodChange(e) {
    const period = e.target.value;
    
    // Show loading state
    showLoadingState();

    try {
        // Fetch new data for the selected period
        const response = await fetch(`php/get_dashboard_data.php?period=${period}`);
        const data = await response.json();

        if (data.success) {
            // Update summary cards
            updateSummaryCards(data.summary, data.comparison);
            
            // Update charts
            updateCharts(data);
            
            // Update products table
            updateProductsTable(data.topProducts);
        } else {
            console.error('Failed to fetch dashboard data:', data.error);
            showNotification('Failed to load data for selected period', 'error');
        }
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
        showNotification('An error occurred while loading data', 'error');
    } finally {
        hideLoadingState();
    }
}

/**
 * Update summary cards with new data
 */
function updateSummaryCards(summary, comparison) {
    // Update sold orders
    const soldOrdersEl = document.getElementById('summary-sold-orders');
    if (soldOrdersEl) soldOrdersEl.textContent = summary.sold_orders;
    
    // Update pending orders
    const pendingOrdersEl = document.getElementById('summary-pending-orders');
    if (pendingOrdersEl) pendingOrdersEl.textContent = summary.pending_orders;
    
    // Update quantity sold
    const quantityEl = document.getElementById('summary-quantity');
    if (quantityEl) quantityEl.textContent = summary.sold_quantity;
    
    // Update pending quantity
    const pendingQuantityEl = document.getElementById('summary-pending-quantity');
    if (pendingQuantityEl) pendingQuantityEl.textContent = summary.pending_quantity;
    
    // Update sales
    const salesEl = document.getElementById('summary-sales');
    if (salesEl) salesEl.textContent = '$' + summary.sales.toFixed(2);
    
    // Update pending bookings
    const pendingBookingsEl = document.getElementById('summary-pending-bookings');
    if (pendingBookingsEl) pendingBookingsEl.textContent = summary.pending_bookings || 0;
    
    // Update completed bookings
    const completedBookingsEl = document.getElementById('summary-completed-bookings');
    if (completedBookingsEl) completedBookingsEl.textContent = summary.completed_bookings || 0;
    
    // Update profit
    const profitEl = document.getElementById('summary-profit');
    if (profitEl) profitEl.textContent = '$' + summary.profit.toFixed(2);

    // Update changes
    updateChangeIndicator('summary-sold-orders-change', comparison.changes.sold_orders);
    updateChangeIndicator('summary-pending-orders-change', comparison.changes.pending_orders);
    updateChangeIndicator('summary-quantity-change', comparison.changes.quantity);
    updateChangeIndicator('summary-sales-change', comparison.changes.sales);
    updateChangeIndicator('summary-pending-bookings-change', comparison.changes.pending_bookings || 0);
    updateChangeIndicator('summary-completed-bookings-change', comparison.changes.completed_bookings || 0);
    updateChangeIndicator('summary-profit-change', comparison.changes.profit);
}

/**
 * Update change indicator
 */
function updateChangeIndicator(elementId, change) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = (change >= 0 ? '+' : '') + change + '%';
        element.className = 'card-change ' + (change >= 0 ? 'positive' : 'negative');
    }
}

/**
 * Update charts with new data
 */
function updateCharts(data) {
    // Update month comparison chart if it exists
    if (monthComparisonChart && data.comparison) {
        monthComparisonChart.data.datasets[0].data = [
            data.summary.sold_orders,
            data.summary.sold_quantity,
            data.summary.sales,
            data.summary.pending_bookings || 0,
            data.summary.completed_bookings || 0,
            data.summary.profit
        ];
        monthComparisonChart.data.datasets[1].data = [
            data.comparison.last_month.sold_orders,
            data.comparison.last_month.sold_quantity,
            data.comparison.last_month.sales,
            data.comparison.last_month.pending_bookings || 0,
            data.comparison.last_month.completed_bookings || 0,
            data.comparison.last_month.profit
        ];
        monthComparisonChart.data.labels = ['Sold Orders', 'Quantity Sold', 'Sales ($)', 'Pending Bookings', 'Completed Bookings', 'Profit ($)'];
        monthComparisonChart.update();
    }

    // Update top products chart
    if (topProductsChart && data.topProducts) {
        const labels = data.topProducts.map(p => p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name);
        const quantities = data.topProducts.map(p => p.quantity_sold);
        
        topProductsChart.data.labels = labels;
        topProductsChart.data.datasets[0].data = quantities;
        topProductsChart.update();
    }
}

/**
 * Update products table
 */
function updateProductsTable(products) {
    const tbody = document.getElementById('top-products-tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">No sales data available.</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(product.name)}</td>
            <td>${product.quantity_sold}</td>
            <td>$${product.total_revenue.toFixed(2)}</td>
            <td>${product.percentage}%</td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Handle export functionality
 */
function handleExport() {
    const period = document.getElementById('period-filter')?.value || 'this_month';
    const format = confirm('Export as CSV? (Click OK for CSV, Cancel for PDF)') ? 'csv' : 'pdf';
    
    window.location.href = `php/export_report.php?period=${period}&format=${format}`;
}

/**
 * Show loading state
 */
function showLoadingState() {
    const cards = document.querySelectorAll('.summary-card');
    cards.forEach(card => {
        card.style.opacity = '0.6';
        card.style.pointerEvents = 'none';
    });
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    const cards = document.querySelectorAll('.summary-card');
    cards.forEach(card => {
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#42ba96' : '#d32f2f'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

