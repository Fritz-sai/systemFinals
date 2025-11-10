/**
 * Admin Orders Management JavaScript
 * 
 * Handles:
 * - Proof upload modal and form submission
 * - Order status updates
 * - View proof modal
 * - File preview before upload
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize modals
    const proofUploadModal = document.getElementById('proofUploadModal');
    const viewProofModal = document.getElementById('viewProofModal');
    const proofUploadForm = document.getElementById('proofUploadForm');
    const proofImageInput = document.getElementById('proof_image');
    const proofPreview = document.getElementById('proof_preview');
    const proofPreviewImg = document.getElementById('proof_preview_img');

    // Upload Proof Button Handlers
    const uploadProofButtons = document.querySelectorAll('.upload-proof-btn');
    uploadProofButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const orderId = btn.getAttribute('data-order-id');
            document.getElementById('proof_order_id').value = orderId;
            openModal(proofUploadModal);
        });
    });

    // View Proof Button Handlers
    const viewProofButtons = document.querySelectorAll('.view-proof-btn');
    viewProofButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const proofPath = btn.getAttribute('data-proof-path');
            document.getElementById('proof_viewer_img').src = proofPath;
            openModal(viewProofModal);
        });
    });

    // Modal Close Handlers
    const modalCloses = document.querySelectorAll('.modal-close');
    modalCloses.forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(proofUploadModal);
            closeModal(viewProofModal);
        });
    });

    // Close modal when clicking outside
    [proofUploadModal, viewProofModal].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    // File Preview Handler
    if (proofImageInput) {
        proofImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, JPEG, and PNG images are allowed.');
                    e.target.value = '';
                    proofPreview.style.display = 'none';
                    return;
                }

                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File size exceeds 5MB limit. Please choose a smaller file.');
                    e.target.value = '';
                    proofPreview.style.display = 'none';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = (event) => {
                    proofPreviewImg.src = event.target.result;
                    proofPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                proofPreview.style.display = 'none';
            }
        });
    }

    // Proof Upload Form Submission
    if (proofUploadForm) {
        proofUploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(proofUploadForm);
            const submitBtn = proofUploadForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';

            try {
                const response = await fetch('php/handle_orders.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    showNotification(result.message || 'Proof uploaded successfully!', 'success');
                    
                    // Close modal and reset form
                    closeModal(proofUploadModal);
                    proofUploadForm.reset();
                    proofPreview.style.display = 'none';
                    
                    // Refresh sales analysis if auto-update was enabled (status changed to delivered)
                    const autoUpdate = document.getElementById('auto_update_status')?.checked;
                    if (autoUpdate) {
                        refreshSalesAnalysis();
                    } else {
                        // Reload page after a short delay to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    showNotification(result.error || 'Failed to upload proof', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Upload error:', error);
                showNotification('An error occurred while uploading the proof', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // Order Status Update Handlers
    const statusSelects = document.querySelectorAll('.order-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', async (e) => {
            const orderId = select.getAttribute('data-order-id');
            const newStatus = select.value;
            const currentStatus = select.getAttribute('data-current-status');

            // Don't update if status hasn't changed
            if (newStatus === currentStatus) {
                return;
            }

            // Confirm status change
            if (!confirm(`Are you sure you want to change order status to "${newStatus.replace('_', ' ')}"?`)) {
                select.value = currentStatus;
                return;
            }

            // Disable select during update
            select.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('order_id', orderId);
                formData.append('order_status', newStatus);

                const response = await fetch('php/handle_orders.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message || 'Order status updated successfully!', 'success');
                    select.setAttribute('data-current-status', newStatus);
                    
                    // If order is delivered or received, remove it from Orders Management table
                    if (newStatus === 'delivered' || newStatus === 'received') {
                        const orderRow = select.closest('tr');
                        if (orderRow) {
                            // Check if we're in the Orders Management section
                            const ordersManagementSection = orderRow.closest('#orders-management-section');
                            if (ordersManagementSection) {
                                // Fade out and remove the row
                                orderRow.style.transition = 'opacity 0.3s ease';
                                orderRow.style.opacity = '0.5';
                                setTimeout(() => {
                                    orderRow.remove();
                                    
                                    // Check if table is empty
                                    const tbody = ordersManagementSection.querySelector('tbody');
                                    if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                        tbody.innerHTML = '<tr><td colspan="9">No active orders found. All orders have been delivered or received.</td></tr>';
                                    }
                                }, 300);
                            }
                        }
                        refreshSalesAnalysis();
                    }
                } else {
                    showNotification(result.error || 'Failed to update order status', 'error');
                    select.value = currentStatus;
                }
            } catch (error) {
                console.error('Status update error:', error);
                showNotification('An error occurred while updating order status', 'error');
                select.value = currentStatus;
            } finally {
                select.disabled = false;
            }
        });
    });
});

/**
 * Refresh sales analysis section
 * Reloads the page to update all sales data and charts
 */
function refreshSalesAnalysis() {
    // Check if sales analysis section exists
    const salesAnalysisSection = document.getElementById('sales-analysis');
    if (!salesAnalysisSection) {
        return;
    }

    // Show loading indicator
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'sales-refresh-loading';
    loadingIndicator.textContent = 'Updating sales analysis...';
    loadingIndicator.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(42, 115, 255, 0.95);
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 10001;
    `;
    document.body.appendChild(loadingIndicator);

    // Reload page after a short delay to show the loading indicator
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

/**
 * Open a modal
 */
function openModal(modal) {
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close a modal
 */
function closeModal(modal) {
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

/**
 * Show notification message
 */
function showNotification(message, type = 'success') {
    // Create notification element
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
        animation: slideInRight 0.3s ease;
    `;

    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

