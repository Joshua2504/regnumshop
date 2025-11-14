// Main JavaScript file for Regnum Online Shop

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Format price inputs
    const priceInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    priceInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });

    // Quantity input validation
    const quantityInputs = document.querySelectorAll('input[type="number"][name="quantity"]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseInt(this.min) || 1;
            const max = parseInt(this.max) || 9999;
            let value = parseInt(this.value);

            if (value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
                alert(`Maximum quantity is ${max}`);
            }
        });
    });

    // Image preview for URL input
    const imageUrlInputs = document.querySelectorAll('input[name="image_url"]');
    imageUrlInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const url = this.value.trim();
            if (url) {
                // Create preview element if it doesn't exist
                let preview = this.parentElement.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'image-preview mt-2';
                    this.parentElement.appendChild(preview);
                }

                // Show loading state
                preview.innerHTML = '<small class="text-muted">Loading preview...</small>';

                // Try to load image
                const img = new Image();
                img.onload = function() {
                    preview.innerHTML = `<img src="${url}" alt="Preview" style="max-width: 200px; max-height: 200px; border: 1px solid #dee2e6; border-radius: 4px;">`;
                };
                img.onerror = function() {
                    preview.innerHTML = '<small class="text-danger">Failed to load image. Please check the URL.</small>';
                };
                img.src = url;
            }
        });
    });

    // Form validation helper
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Stock level indicators
    const stockElements = document.querySelectorAll('[data-stock]');
    stockElements.forEach(element => {
        const stock = parseInt(element.dataset.stock);
        if (stock === 0) {
            element.classList.add('text-stock-out');
            element.innerHTML = '<strong>Out of Stock</strong>';
        } else if (stock < 10) {
            element.classList.add('text-stock-low');
            element.innerHTML = `<strong>Low Stock (${stock})</strong>`;
        }
    });

    // Update cart badge dynamically (if needed)
    function updateCartBadge() {
        // This could be extended to use AJAX to update the cart count
        // without page reload
    }

    // Smooth scroll to top
    const scrollToTopBtn = document.getElementById('scrollToTop');
    if (scrollToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });

        scrollToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Payment method selection visual feedback
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove selected class from all payment options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary', 'bg-light');
            });

            // Add selected class to chosen option
            const parentOption = this.closest('.payment-option');
            if (parentOption) {
                parentOption.classList.add('border-primary', 'bg-light');
            }
        });
    });

    // Console info
    console.log('%c Regnum Online Shop ', 'background: #0d6efd; color: white; font-size: 16px; padding: 5px 10px;');
    console.log('Shop initialized successfully');
});

// Utility functions
function formatPrice(price) {
    return '€' + parseFloat(price).toFixed(2).replace('.', ',');
}

function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}
