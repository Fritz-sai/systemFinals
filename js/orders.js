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

