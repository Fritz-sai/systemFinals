document.addEventListener('DOMContentLoaded', () => {
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
});

