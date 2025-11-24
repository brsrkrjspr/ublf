// Form handling JavaScript
class FormManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeFileUploads();
        this.initializeFormValidation();
    }

    bindEvents() {
        // Form submission
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('needs-validation')) {
                e.preventDefault();
                this.handleFormSubmission(e.target);
            }
        });

        // File input change
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file') {
                this.handleFileSelect(e.target);
            }
        });

        // Real-time validation
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('validate-on-input')) {
                this.validateField(e.target);
            }
        });

        // Form reset
        document.addEventListener('reset', (e) => {
            this.resetForm(e.target);
        });
    }

    initializeFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            this.setupFileUpload(input);
        });
    }

    setupFileUpload(input) {
        const previewContainer = input.parentElement.querySelector('.file-preview');
        if (!previewContainer) return;

        input.addEventListener('change', (e) => {
            const files = e.target.files;
            this.displayFilePreview(files, previewContainer);
        });
    }

    displayFilePreview(files, container) {
        container.innerHTML = '';
        
        if (files.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                this.createImagePreview(file, container);
            } else {
                this.createFilePreview(file, container);
            }
        });
    }

    createImagePreview(file, container) {
        const reader = new FileReader();
        const preview = document.createElement('div');
        preview.className = 'file-preview-item';
        
        reader.onload = (e) => {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                <div class="file-info">
                    <small class="text-muted">${file.name}</small>
                    <small class="text-muted">(${this.formatFileSize(file.size)})</small>
                </div>
                <button type="button" class="btn-remove-file btn btn-sm btn-outline-danger">
                    <i class="bi bi-x"></i>
                </button>
            `;
        };
        
        reader.readAsDataURL(file);
        container.appendChild(preview);
        
        // Remove file functionality
        preview.querySelector('.btn-remove-file').addEventListener('click', () => {
            container.removeChild(preview);
            if (container.children.length === 0) {
                container.style.display = 'none';
            }
        });
    }

    createFilePreview(file, container) {
        const preview = document.createElement('div');
        preview.className = 'file-preview-item';
        preview.innerHTML = `
            <div class="file-icon">
                <i class="bi bi-file-earmark"></i>
            </div>
            <div class="file-info">
                <small class="text-muted">${file.name}</small>
                <small class="text-muted">(${this.formatFileSize(file.size)})</small>
            </div>
            <button type="button" class="btn-remove-file btn btn-sm btn-outline-danger">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        container.appendChild(preview);
        
        // Remove file functionality
        preview.querySelector('.btn-remove-file').addEventListener('click', () => {
            container.removeChild(preview);
            if (container.children.length === 0) {
                container.style.display = 'none';
            }
        });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    initializeFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            this.setupFormValidation(form);
        });
    }

    setupFormValidation(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address.';
            }
        }

        // Phone validation
        if (field.name === 'phoneNo' && value) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number.';
            }
        }

        // Student number validation
        if (field.name === 'studentNo' && value) {
            if (value.length < 5) {
                isValid = false;
                errorMessage = 'Student number must be at least 5 characters.';
            }
        }

        // File validation
        if (field.type === 'file' && field.files.length > 0) {
            const file = field.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (file.size > maxSize) {
                isValid = false;
                errorMessage = 'File size must be less than 5MB.';
            } else if (!allowedTypes.includes(file.type)) {
                isValid = false;
                errorMessage = 'Please upload an image file (JPG, PNG, GIF, WebP).';
            }
        }

        this.showFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    showFieldValidation(field, isValid, message = '') {
        const container = field.parentElement;
        const existingFeedback = container.querySelector('.invalid-feedback, .valid-feedback');
        
        if (existingFeedback) {
            existingFeedback.remove();
        }

        field.classList.remove('is-valid', 'is-invalid');
        
        if (isValid) {
            field.classList.add('is-valid');
            if (message) {
                const feedback = document.createElement('div');
                feedback.className = 'valid-feedback';
                feedback.textContent = message;
                container.appendChild(feedback);
            }
        } else {
            field.classList.add('is-invalid');
            if (message) {
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                container.appendChild(feedback);
            }
        }
    }

    async handleFormSubmission(form) {
        // Validate all fields
        const inputs = form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            this.showToast('Please correct the errors in the form.', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.showToast(result.message || 'Form submitted successfully!', 'success');
                this.resetForm(form);
                
                // Close modal if form is in modal
                const modal = form.closest('.modal');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                
                // Reload page if specified
                if (result.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                this.showToast(result.message || 'Form submission failed.', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showToast('An error occurred while submitting the form.', 'error');
        } finally {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    resetForm(form) {
        form.reset();
        
        // Clear validation states
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
        
        // Clear feedback messages
        const feedbacks = form.querySelectorAll('.invalid-feedback, .valid-feedback');
        feedbacks.forEach(feedback => feedback.remove());
        
        // Clear file previews
        const previews = form.querySelectorAll('.file-preview');
        previews.forEach(preview => {
            preview.innerHTML = '';
            preview.style.display = 'none';
        });
    }

    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-notification-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="bi ${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        // Add to page
        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    getToastIcon(type) {
        const icons = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-x-circle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        };
        return icons[type] || 'bi-info-circle-fill';
    }
}

// Initialize form manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FormManager();
});

// Add file preview styles
const filePreviewStyles = `
<style>
.file-preview {
    margin-top: 1rem;
    display: none;
}

.file-preview-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.75rem;
    margin-bottom: 0.5rem;
    position: relative;
}

.file-icon {
    font-size: 2rem;
    color: #6c757d;
}

.file-info {
    flex: 1;
}

.btn-remove-file {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 24px;
    height: 24px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 8px 32px rgba(128,0,0,0.15);
    padding: 1rem 1.5rem;
    z-index: 9999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 350px;
}

.toast-notification.show {
    transform: translateX(0);
}

.toast-notification-success {
    border-left: 4px solid #28a745;
}

.toast-notification-error {
    border-left: 4px solid #dc3545;
}

.toast-notification-warning {
    border-left: 4px solid #ffc107;
}

.toast-notification-info {
    border-left: 4px solid #17a2b8;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.toast-content i {
    font-size: 1.2rem;
}

.toast-notification-success .toast-content i {
    color: #28a745;
}

.toast-notification-error .toast-content i {
    color: #dc3545;
}

.toast-notification-warning .toast-content i {
    color: #ffc107;
}

.toast-notification-info .toast-content i {
    color: #17a2b8;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', filePreviewStyles); 