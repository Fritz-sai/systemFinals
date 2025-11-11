/**
 * Customer Orders Page JavaScript
 * 
 * Handles:
 * - View proof modal functionality
 * - Modal open/close interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    const viewProofModal = document.getElementById('viewProofModal');
    const viewProofButtons = document.querySelectorAll('.view-proof-btn');
    const modalClose = document.querySelector('#viewProofModal .modal-close');
    
    // Star rating interaction - improved with better feedback
    document.querySelectorAll('.rating-input').forEach(ratingInput => {
        const starLabels = ratingInput.querySelectorAll('.star-label');
        const inputs = ratingInput.querySelectorAll('input[type="radio"]');
        const ratingText = ratingInput.querySelector('.rating-text');
        
        const updateStars = (selectedIndex) => {
            starLabels.forEach((label, index) => {
                const star = label.querySelector('.star');
                const starValue = index + 1; // 1-5
                if (starValue <= selectedIndex) {
                    star.style.color = '#fbbf24';
                    star.style.transform = 'scale(1.1)';
                } else {
                    star.style.color = '#d1d5db';
                    star.style.transform = 'scale(1)';
                }
            });
            
            // Update rating text
            if (ratingText && selectedIndex > 0) {
                const ratingLabels = ['', '1 Star - Poor', '2 Stars - Fair', '3 Stars - Good', '4 Stars - Very Good', '5 Stars - Excellent'];
                ratingText.textContent = ratingLabels[selectedIndex];
                ratingText.style.color = '#374151';
            } else if (ratingText) {
                ratingText.textContent = 'Select rating';
                ratingText.style.color = '#6b7280';
            }
        };
        
        starLabels.forEach((label, index) => {
            const starIndex = index + 1; // 1-5
            
            label.addEventListener('mouseenter', () => {
                updateStars(starIndex);
            });
            
            label.addEventListener('mouseleave', () => {
                const checked = ratingInput.querySelector('input[type="radio"]:checked');
                if (checked) {
                    const checkedIndex = parseInt(checked.value);
                    updateStars(checkedIndex);
                } else {
                    updateStars(0);
                }
            });
            
            label.addEventListener('click', (e) => {
                e.preventDefault();
                inputs[index].checked = true;
                updateStars(starIndex);
            });
        });
    });

    // View Proof Button Handlers
    viewProofButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const proofPath = btn.getAttribute('data-proof-path');
            if (proofPath) {
                document.getElementById('proof_viewer_img').src = proofPath;
                openModal(viewProofModal);
            }
        });
    });

    // Modal Close Handler
    if (modalClose) {
        modalClose.addEventListener('click', () => {
            closeModal(viewProofModal);
        });
    }

    // Close modal when clicking outside
    if (viewProofModal) {
        viewProofModal.addEventListener('click', (e) => {
            if (e.target === viewProofModal) {
                closeModal(viewProofModal);
            }
        });
    }

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && viewProofModal.classList.contains('show')) {
            closeModal(viewProofModal);
        }
    });
    
    // Handle review form submission via AJAX
    document.querySelectorAll('.review-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const orderId = form.getAttribute('data-order-id');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            const reviewSection = document.getElementById('review-section-' + orderId);
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            try {
                const response = await fetch('php/handle_reviews.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message and update UI without reloading
                    if (reviewSection) {
                        reviewSection.innerHTML = `
                            <div style="padding: 1rem; background: #d1fae5; border-radius: 8px; color: #065f46; text-align: center;">
                                <strong>✓ Thank you! Your review has been submitted.</strong>
                            </div>
                        `;
                    }
                    
                    // Show a flash message at the top
                    const flashDiv = document.createElement('div');
                    flashDiv.className = 'flash-message flash-success';
                    flashDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; background: #10b981; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000;';
                    flashDiv.textContent = '✓ Review submitted successfully!';
                    document.body.appendChild(flashDiv);
                    
                    // Remove flash message after 3 seconds
                    setTimeout(() => {
                        flashDiv.style.opacity = '0';
                        flashDiv.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => flashDiv.remove(), 300);
                    }, 3000);
                } else {
                    alert(result.error || 'Failed to submit review. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Review submission error:', error);
                alert('An error occurred while submitting your review. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });
});

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

