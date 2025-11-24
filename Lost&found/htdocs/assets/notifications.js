// Notifications JavaScript
class NotificationManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateNotificationCount();
        this.startAutoRefresh();
    }

    bindEvents() {
        // Mark notification as read from dropdown
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mark-read-btn')) {
                e.preventDefault();
                this.markAsRead(e.target.dataset.notificationId);
            }
        });

        // Mark all notifications as read
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mark-all-read-btn')) {
                e.preventDefault();
                this.markAllAsRead();
            }
        });

        // Notification item click
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-item')) {
                const item = e.target.closest('.notification-item');
                const notificationId = item.dataset.notificationId;
                if (notificationId && !item.classList.contains('read')) {
                    this.markAsRead(notificationId);
                }
            }
        });
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            });

            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                    
                    // Remove unread indicator
                    const unreadIndicator = notificationItem.querySelector('.unread-indicator');
                    if (unreadIndicator) {
                        unreadIndicator.remove();
                    }
                }
                
                this.updateNotificationCount();
                this.showToast('Notification marked as read', 'success');
            } else {
                this.showToast('Failed to mark notification as read', 'error');
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
            this.showToast('Error marking notification as read', 'error');
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_all=1'
            });

            const data = await response.json();
            
            if (data.success) {
                // Update all unread notifications
                const unreadItems = document.querySelectorAll('.notification-item.unread');
                unreadItems.forEach(item => {
                    item.classList.remove('unread');
                    item.classList.add('read');
                    
                    const unreadIndicator = item.querySelector('.unread-indicator');
                    if (unreadIndicator) {
                        unreadIndicator.remove();
                    }
                });
                
                this.updateNotificationCount();
                this.showToast('All notifications marked as read', 'success');
            } else {
                this.showToast('Failed to mark all notifications as read', 'error');
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
            this.showToast('Error marking all notifications as read', 'error');
        }
    }

    async updateNotificationCount() {
        try {
            const response = await fetch('get_notification_count.php');
            const data = await response.json();
            
            if (data.success) {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Error updating notification count:', error);
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('get_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    renderNotifications(notifications) {
        const container = document.querySelector('.notification-body');
        if (!container) return;

        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bell-slash" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p class="mt-2 mb-0">No notifications yet</p>
                </div>
            `;
            return;
        }

        const html = notifications.map(notification => this.renderNotificationItem(notification)).join('');
        container.innerHTML = html;
    }

    renderNotificationItem(notification) {
        const iconClass = this.getNotificationIconClass(notification.type);
        const timeAgo = this.formatTimeAgo(notification.created_at);
        const unreadClass = notification.is_read ? '' : 'unread';
        
        return `
            <div class="notification-item ${unreadClass}" data-notification-id="${notification.notification_id}">
                <div class="d-flex align-items-start">
                    <div class="notification-icon ${iconClass}">
                        <i class="bi ${this.getNotificationIcon(notification.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                        <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                    ${!notification.is_read ? '<div class="unread-indicator ms-2"><div class="bg-danger rounded-circle" style="width: 8px; height: 8px;"></div></div>' : ''}
                </div>
            </div>
        `;
    }

    getNotificationIcon(type) {
        const icons = {
            'photo_approved': 'bi-check-circle-fill',
            'photo_rejected': 'bi-x-circle-fill',
            'report_approved': 'bi-check-circle-fill',
            'report_rejected': 'bi-x-circle-fill',
            'item_matched': 'bi-link-45deg',
            'admin_message': 'bi-envelope-fill',
            'system_alert': 'bi-exclamation-triangle-fill'
        };
        return icons[type] || 'bi-bell-fill';
    }

    getNotificationIconClass(type) {
        const classes = {
            'photo_approved': 'success',
            'photo_rejected': 'danger',
            'report_approved': 'success',
            'report_rejected': 'danger',
            'item_matched': 'primary',
            'admin_message': 'info',
            'system_alert': 'warning'
        };
        return classes[type] || 'secondary';
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000); // seconds

        if (diff < 60) {
            return 'Just now';
        } else if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diff < 604800) {
            const days = Math.floor(diff / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else {
            return time.toLocaleDateString();
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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

    startAutoRefresh() {
        // Update notification count every 30 seconds
        setInterval(() => {
            this.updateNotificationCount();
        }, 30000);
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new NotificationManager();
});

// Add toast notification styles
const toastStyles = `
<style>
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

document.head.insertAdjacentHTML('beforeend', toastStyles); 