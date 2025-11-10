document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Navigation Functionality
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const sections = document.querySelectorAll('[id$="-section"], #sales-overview');

    // Handle sidebar link clicks with smooth scroll
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('data-section');
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Remove active class from all links
                sidebarLinks.forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                link.classList.add('active');
                
                // Smooth scroll to target
                const offset = 100; // Offset for sticky header
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - offset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Update active link based on scroll position
    const updateActiveLink = () => {
        const scrollPosition = window.scrollY + 150; // Offset for better detection

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            const sectionId = section.getAttribute('id');

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                sidebarLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('data-section') === sectionId) {
                        link.classList.add('active');
                    }
                });
            }
        });
    };

        // Check on scroll
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(updateActiveLink, 100);
        });

        // Initial check
        updateActiveLink();

    // Handle product edit button clicks
    const editButtons = document.querySelectorAll('.edit-product-btn');
    const productForm = document.getElementById('product-form');
    const formName = document.getElementById('form-name');
    const formDescription = document.getElementById('form-description');
    const formPrice = document.getElementById('form-price');
    const formImage = document.getElementById('form-image');
    const formAction = document.getElementById('form-action');
    const formProductId = document.getElementById('form-product-id');
    const formSubmitBtn = document.getElementById('form-submit-btn');
    const formCancelBtn = document.getElementById('form-cancel-btn');
    const formSection = document.getElementById('product-form-section');
    const formTitle = formSection.querySelector('h3');
    const currentImagePreview = document.getElementById('current-image-preview');
    const currentImageImg = currentImagePreview.querySelector('img');
    const keepExistingImage = document.getElementById('keep-existing-image');

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = btn.getAttribute('data-product-id');
            const productName = btn.getAttribute('data-product-name');
            const productDescription = btn.getAttribute('data-product-description');
            const productPrice = btn.getAttribute('data-product-price');
            const productImage = btn.getAttribute('data-product-image');

            // Populate form
            formName.value = productName;
            formDescription.value = productDescription;
            formPrice.value = productPrice;
            formProductId.value = productId;
            formAction.value = 'update_product';
            formSubmitBtn.textContent = 'Update Product';
            formTitle.textContent = 'Edit Product';
            formCancelBtn.style.display = 'inline-flex';

            // Show current image preview
            if (productImage && productImage !== 'images/placeholder.png') {
                currentImageImg.src = productImage;
                currentImagePreview.style.display = 'block';
                keepExistingImage.value = '1';
            } else {
                currentImagePreview.style.display = 'none';
                keepExistingImage.value = '0';
            }

            // Clear file input
            formImage.value = '';

            // Scroll to form
            formSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    // Handle cancel button
    formCancelBtn.addEventListener('click', () => {
        // Reset form
        productForm.reset();
        formAction.value = 'add_product';
        formProductId.value = '';
        formSubmitBtn.textContent = 'Add Product';
        formTitle.textContent = 'Add New Product';
        formCancelBtn.style.display = 'none';
        currentImagePreview.style.display = 'none';
        keepExistingImage.value = '0';
    });

    // Handle file input change - if file is selected, don't keep existing
    formImage.addEventListener('change', () => {
        if (formImage.files.length > 0) {
            keepExistingImage.value = '0';
            currentImagePreview.style.display = 'none';
        } else if (formProductId.value && currentImageImg.src) {
            // If editing and no file selected, keep existing
            keepExistingImage.value = '1';
            currentImagePreview.style.display = 'block';
        }
    });

    // Handle delete product confirmation
    const deleteForms = document.querySelectorAll('.delete-product-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Validate file size before upload
    formImage.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                alert('File size exceeds 2MB limit. Please choose a smaller file.');
                formImage.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Only JPG, JPEG, and PNG images are allowed.');
                formImage.value = '';
                return;
            }
        }
    });

    // Sales Analysis Charts
    if (typeof salesData !== 'undefined' && typeof Chart !== 'undefined') {
        initializeSalesCharts();
    }

    // Handle booking completion with AJAX to update dashboard
    const bookingCompletionForms = document.querySelectorAll('form[action="admin.php"]');
    bookingCompletionForms.forEach(form => {
        const actionInput = form.querySelector('input[name="admin_action"]');
        if (actionInput && actionInput.value === 'mark_booking_completed') {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const bookingId = formData.get('booking_id');
                const button = form.querySelector('button[type="submit"]');
                const originalText = button.textContent;
                
                // Disable button and show loading state
                button.disabled = true;
                button.textContent = 'Processing...';
                
                try {
                    // Optimistic update: decrease pending, increase completed
                    const pendingBookingsEl = document.getElementById('summary-pending-bookings');
                    const completedBookingsEl = document.getElementById('summary-completed-bookings');
                    
                    if (pendingBookingsEl) {
                        const currentPending = parseInt(pendingBookingsEl.textContent) || 0;
                        pendingBookingsEl.textContent = Math.max(0, currentPending - 1);
                    }
                    
                    if (completedBookingsEl) {
                        const currentCompleted = parseInt(completedBookingsEl.textContent) || 0;
                        completedBookingsEl.textContent = currentCompleted + 1;
                    }
                    
                    const response = await fetch('admin.php', {
                        method: 'POST',
                        body: formData,
                        redirect: 'follow'
                    });
                    
                    if (response.ok || response.redirected) {
                        // Update booking count in dashboard overview
                        updateBookingCount();
                        
                        // Remove the booking row from active bookings table
                        const bookingRow = form.closest('tr');
                        if (bookingRow) {
                            bookingRow.style.opacity = '0.5';
                            bookingRow.style.transition = 'opacity 0.3s';
                            setTimeout(() => {
                                bookingRow.remove();
                                
                                // Check if table is empty
                                const tbody = bookingRow.closest('tbody');
                                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                    tbody.innerHTML = '<tr><td colspan="7">No active bookings.</td></tr>';
                                }
                            }, 300);
                        }
                        
                        // Show success message
                        showBookingNotification('Booking marked as completed successfully!', 'success');
                        
                        // Reload page after a short delay to refresh all data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        throw new Error('Failed to complete booking');
                    }
                } catch (error) {
                    console.error('Error completing booking:', error);
                    button.disabled = false;
                    button.textContent = originalText;
                    showBookingNotification('Failed to complete booking. Please try again.', 'error');
                }
            });
        }
    });
});

/**
 * Update booking count in dashboard overview
 */
async function updateBookingCount() {
    try {
        // Fetch updated dashboard data
        const response = await fetch('php/get_dashboard_data.php?period=this_month');
        const data = await response.json();
        
        if (data.success && data.summary) {
            // Update pending bookings count
            const pendingBookingsEl = document.getElementById('summary-pending-bookings');
            if (pendingBookingsEl) {
                pendingBookingsEl.textContent = data.summary.pending_bookings || 0;
            }
            
            // Update completed bookings count
            const completedBookingsEl = document.getElementById('summary-completed-bookings');
            if (completedBookingsEl) {
                completedBookingsEl.textContent = data.summary.completed_bookings || 0;
            }
            
            // Update booking change indicators if available
            if (data.comparison && data.comparison.changes) {
                // Update pending bookings change
                const pendingBookingsChangeEl = document.getElementById('summary-pending-bookings-change');
                if (pendingBookingsChangeEl) {
                    const change = data.comparison.changes.pending_bookings || 0;
                    pendingBookingsChangeEl.textContent = (change >= 0 ? '+' : '') + change + '%';
                    pendingBookingsChangeEl.className = 'card-change ' + (change >= 0 ? 'positive' : 'negative');
                }
                
                // Update completed bookings change
                const completedBookingsChangeEl = document.getElementById('summary-completed-bookings-change');
                if (completedBookingsChangeEl) {
                    const change = data.comparison.changes.completed_bookings || 0;
                    completedBookingsChangeEl.textContent = (change >= 0 ? '+' : '') + change + '%';
                    completedBookingsChangeEl.className = 'card-change ' + (change >= 0 ? 'positive' : 'negative');
                }
            }
        }
    } catch (error) {
        console.error('Error updating booking count:', error);
        // Fallback: update counts manually
        const pendingBookingsEl = document.getElementById('summary-pending-bookings');
        const completedBookingsEl = document.getElementById('summary-completed-bookings');
        
        if (pendingBookingsEl) {
            const currentPending = parseInt(pendingBookingsEl.textContent) || 0;
            pendingBookingsEl.textContent = Math.max(0, currentPending - 1);
        }
        
        if (completedBookingsEl) {
            const currentCompleted = parseInt(completedBookingsEl.textContent) || 0;
            completedBookingsEl.textContent = currentCompleted + 1;
        }
    }
}

/**
 * Show booking notification
 */
function showBookingNotification(message, type = 'success') {
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
        animation: slideIn 0.3s ease-out;
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Initialize Sales Analysis Charts
function initializeSalesCharts() {
    // Revenue Trend Chart (Last 30 Days)
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx && salesData.dailySales) {
        const dailyData = salesData.dailySales;
        const labels = dailyData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const revenues = dailyData.map(item => parseFloat(item.revenue) || 0);

        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: revenues,
                    borderColor: '#2a73ff',
                    backgroundColor: 'rgba(42, 115, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
    }

    // Orders by Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && salesData.ordersByStatus) {
        const statusData = salesData.ordersByStatus;
        const labels = statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
        const counts = statusData.map(item => parseInt(item.count) || 0);
        const colors = ['#ff9800', '#ffc107', '#42ba96', '#d32f2f'];

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
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
                    }
                }
            }
        });
    }

    // Monthly Sales Chart (Last 6 Months)
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx && salesData.monthlySales) {
        const monthlyData = salesData.monthlySales;
        const labels = monthlyData.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const revenues = monthlyData.map(item => parseFloat(item.revenue) || 0);
        const orders = monthlyData.map(item => parseInt(item.order_count) || 0);

        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: revenues,
                        backgroundColor: 'rgba(42, 115, 255, 0.6)',
                        borderColor: '#2a73ff',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: orders,
                        type: 'line',
                        borderColor: '#42ba96',
                        backgroundColor: 'rgba(66, 186, 150, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    // Top Products Chart
    const productsCtx = document.getElementById('productsChart');
    if (productsCtx && salesData.topProducts) {
        const productsData = salesData.topProducts;
        const labels = productsData.map(item => item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name);
        const revenues = productsData.map(item => parseFloat(item.total_revenue) || 0);

        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: revenues,
                    backgroundColor: [
                        'rgba(42, 115, 255, 0.8)',
                        'rgba(66, 186, 150, 0.8)',
                        'rgba(255, 152, 0, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(156, 39, 176, 0.8)'
                    ],
                    borderColor: [
                        '#2a73ff',
                        '#42ba96',
                        '#ff9800',
                        '#ffc107',
                        '#9c27b0'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
    }
}

