// Client Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize client dashboard functionality
    initializeClientDashboard();
});

function initializeClientDashboard() {
    // Real-time updates
    initializeRealTimeUpdates();
    
    // Notification system
    initializeNotificationSystem();
    
    // Form enhancements
    initializeFormEnhancements();
    
    // Table enhancements
    initializeTableEnhancements();
    
    // Chart functionality
    initializeCharts();
    
    // Message system
    initializeMessageSystem();
    
    // Keyboard shortcuts
    initializeKeyboardShortcuts();
    
    // Auto-save functionality
    initializeAutoSave();
    
    // Progress tracking
    initializeProgressTracking();
}

// Real-time updates
function initializeRealTimeUpdates() {
    // Update counts every 30 seconds
    setInterval(updateCounts, 30000);
    
    // Check for new notifications every minute
    setInterval(checkNewNotifications, 60000);
    
    // Update loan statuses every 2 minutes
    setInterval(updateLoanStatuses, 120000);
    
    // Initial update
    updateCounts();
}

function updateCounts() {
    // Update unread notifications count
    fetch('/client/api/notifications/unread')
        .then(response => response.json())
        .then(data => {
            updateBadgeCount('unread-notifications-count', data.count);
        })
        .catch(error => console.error('Error updating notification count:', error));

    // Update unread messages count
    fetch('/client/api/messages/unread-count')
        .then(response => response.json())
        .then(data => {
            updateBadgeCount('unread-messages-count', data.count);
        })
        .catch(error => console.error('Error updating message count:', error));
}

function updateBadgeCount(elementId, count) {
    const badge = document.getElementById(elementId);
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline';
            badge.classList.add('animate-fade-in');
        } else {
            badge.style.display = 'none';
        }
    }
}

function checkNewNotifications() {
    fetch('/client/api/messages/new')
        .then(response => response.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(message => {
                    showToastNotification('Nouveau message', message.content, 'info');
                });
            }
        })
        .catch(error => console.error('Error checking new notifications:', error));
}

function updateLoanStatuses() {
    const loanElements = document.querySelectorAll('[data-loan-id]');
    loanElements.forEach(element => {
        const loanId = element.dataset.loanId;
        if (loanId) {
            fetch(`/client/api/loan-status/${loanId}`)
                .then(response => response.json())
                .then(data => {
                    updateLoanStatusDisplay(element, data);
                })
                .catch(error => console.error(`Error updating loan ${loanId} status:`, error));
        }
    });
}

function updateLoanStatusDisplay(element, loanData) {
    const statusBadge = element.querySelector('.status-badge, .badge');
    if (statusBadge) {
        let badgeClass = 'bg-warning';
        let statusText = '<i class="fas fa-clock me-1"></i>En attente';
        let statusIndicator = 'status-indicator text-warning';
        
        switch (loanData.status) {
            case 'approved':
                badgeClass = 'bg-success';
                statusText = '<i class="fas fa-check me-1"></i>Approuvé';
                statusIndicator = 'status-indicator text-success';
                break;
            case 'rejected':
                badgeClass = 'bg-danger';
                statusText = '<i class="fas fa-times me-1"></i>Rejeté';
                statusIndicator = 'status-indicator text-danger';
                break;
        }
        
        if (statusBadge.innerHTML !== statusText) {
            statusBadge.className = `badge ${badgeClass}`;
            statusBadge.innerHTML = statusText;
            statusBadge.classList.add('animate-fade-in');
            
            // Add status indicator animation
            if (statusBadge.parentElement) {
                statusBadge.parentElement.className = statusIndicator;
            }
            
            // Show notification of status change
            showToastNotification('Statut mis à jour', `Le prêt #${loanData.id} est maintenant ${loanData.status}`, 'info');
        }
    }
}

// Notification system
function initializeNotificationSystem() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1050';
        document.body.appendChild(container);
    }
}

function showToastNotification(title, message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast align-items-center text-bg-${type} border-0 animate-slide-in`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        delay: 5000
    });
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
    
    return toastId;
}

// Form enhancements
function initializeFormEnhancements() {
    // Add loading states to buttons
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.dataset.skipLoading) {
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi...';
                submitButton.disabled = true;
                
                // Restore button after 5 seconds (failsafe)
                setTimeout(() => {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }, 5000);
            }
        });
    });
    
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Character counters
    document.querySelectorAll('textarea[maxlength], input[maxlength]').forEach(element => {
        const maxLength = element.getAttribute('maxlength');
        if (maxLength) {
            const counter = document.createElement('small');
            counter.className = 'text-muted';
            counter.textContent = `0/${maxLength}`;
            element.parentNode.appendChild(counter);
            
            element.addEventListener('input', function() {
                const currentLength = this.value.length;
                counter.textContent = `${currentLength}/${maxLength}`;
                counter.className = currentLength > maxLength * 0.9 ? 'text-warning' : 'text-muted';
            });
        }
    });
}

// Table enhancements
function initializeTableEnhancements() {
    // Make tables more interactive
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Table sorting
    initializeTableSorting();
    
    // Table filtering
    initializeTableFiltering();
}

function initializeTableSorting() {
    document.querySelectorAll('[onclick*="sortTable"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const table = this.closest('table');
            const columnIndex = Array.from(this.closest('tr').children).indexOf(this.closest('th'));
            sortTable(table, columnIndex);
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isNumeric = rows.some(row => {
        const cellText = row.cells[columnIndex].textContent.trim();
        return !isNaN(parseFloat(cellText.replace(/[^\d.-]/g, '')));
    });
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        if (isNumeric) {
            return parseFloat(bVal.replace(/[^\d.-]/g, '')) - parseFloat(aVal.replace(/[^\d.-]/g, ''));
        } else {
            return aVal.localeCompare(bVal);
        }
    });
    
    // Re-append sorted rows with animation
    rows.forEach((row, index) => {
        setTimeout(() => {
            tbody.appendChild(row);
            row.classList.add('animate-fade-in');
        }, index * 50);
    });
}

function initializeTableFiltering() {
    document.querySelectorAll('[id*="Filter"]').forEach(filter => {
        filter.addEventListener('change', applyTableFilters);
        filter.addEventListener('input', debounce(applyTableFilters, 300));
    });
}

function applyTableFilters() {
    // This would be implemented based on specific table requirements
    console.log('Applying table filters...');
}

// Chart functionality
function initializeCharts() {
    // Initialize any charts on the page
    const chartElements = document.querySelectorAll('canvas[id*="Chart"]');
    chartElements.forEach(canvas => {
        // Chart initialization would be specific to each chart type
        console.log('Initializing chart:', canvas.id);
    });
}

// Message system
function initializeMessageSystem() {
    // Auto-scroll to bottom of message containers
    const messageContainers = document.querySelectorAll('.messages-container');
    messageContainers.forEach(container => {
        container.scrollTop = container.scrollHeight;
    });
    
    // Enhanced message composition
    const messageTextareas = document.querySelectorAll('textarea[name*="content"]');
    messageTextareas.forEach(textarea => {
        // Add emoji picker (simplified)
        addEmojiSupport(textarea);
        
        // Add file attachment support
        addFileAttachmentSupport(textarea);
    });
}

function addEmojiSupport(textarea) {
    // Simple emoji shortcuts
    const emojiMap = {
        ':)': '😊',
        ':(': '😞',
        ':D': '😄',
        ':P': '😛',
        '<3': '❤️',
        ':thumbsup:': '👍',
        ':thumbsdown:': '👎'
    };
    
    textarea.addEventListener('input', function() {
        let value = this.value;
        for (const [shortcut, emoji] of Object.entries(emojiMap)) {
            value = value.replace(new RegExp(escapeRegExp(shortcut), 'g'), emoji);
        }
        if (value !== this.value) {
            this.value = value;
        }
    });
}

function addFileAttachmentSupport(textarea) {
    // Add drag and drop support
    textarea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-primary');
    });
    
    textarea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-primary');
    });
    
    textarea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-primary');
        // Handle file drop (would need backend support)
        console.log('Files dropped:', e.dataTransfer.files);
    });
}

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+/ for help
        if (e.ctrlKey && e.key === '/') {
            e.preventDefault();
            showKeyboardShortcutsHelp();
        }
        
        // Ctrl+R for refresh data
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            refreshCurrentPageData();
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });
        }
    });
}

function showKeyboardShortcutsHelp() {
    const shortcuts = [
        'Ctrl+/ - Afficher l\'aide',
        'Ctrl+R - Actualiser les données',
        'Ctrl+Enter - Envoyer (dans les formulaires)',
        'Échap - Fermer les modales'
    ];
    
    showToastNotification('Raccourcis clavier', shortcuts.join('<br>'), 'info');
}

function refreshCurrentPageData() {
    // Refresh data based on current page
    if (window.location.pathname.includes('/loans')) {
        updateLoanStatuses();
    } else if (window.location.pathname.includes('/messages')) {
        window.location.reload();
    } else if (window.location.pathname.includes('/notifications')) {
        updateCounts();
    } else {
        updateCounts();
        updateLoanStatuses();
    }
    
    showToastNotification('Actualisation', 'Données mises à jour', 'success');
}

// Auto-save functionality
function initializeAutoSave() {
    const autoSaveForms = document.querySelectorAll('[data-auto-save]');
    autoSaveForms.forEach(form => {
        const formData = new FormData(form);
        let timeoutId;
        
        form.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                autoSaveForm(form);
            }, 2000);
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    const savedData = {};
    
    for (const [key, value] of formData.entries()) {
        savedData[key] = value;
    }
    
    localStorage.setItem(`autosave_${form.id}`, JSON.stringify(savedData));
    
    // Show subtle save indicator
    const saveIndicator = document.createElement('small');
    saveIndicator.className = 'text-muted';
    saveIndicator.textContent = 'Sauvegardé automatiquement';
    saveIndicator.style.opacity = '0';
    saveIndicator.style.transition = 'opacity 0.3s ease';
    
    form.appendChild(saveIndicator);
    
    setTimeout(() => {
        saveIndicator.style.opacity = '1';
        setTimeout(() => {
            saveIndicator.style.opacity = '0';
            setTimeout(() => saveIndicator.remove(), 300);
        }, 2000);
    }, 100);
}

// Progress tracking
function initializeProgressTracking() {
    // Track page views for analytics
    trackPageView();
    
    // Track user interactions
    trackUserInteractions();
    
    // Monitor performance
    monitorPerformance();
}

function trackPageView() {
    const pageData = {
        url: window.location.pathname,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent
    };
    
    // Send to analytics endpoint (if available)
    console.log('Page view tracked:', pageData);
}

function trackUserInteractions() {
    // Track button clicks
    document.addEventListener('click', function(e) {
        if (e.target.matches('button, .btn, a[href]')) {
            console.log('User interaction:', {
                element: e.target.tagName,
                text: e.target.textContent.trim(),
                timestamp: new Date().toISOString()
            });
        }
    });
}

function monitorPerformance() {
    // Monitor page load performance
    window.addEventListener('load', function() {
        const perfData = performance.getEntriesByType('navigation')[0];
        console.log('Page performance:', {
            loadTime: perfData.loadEventEnd - perfData.loadEventStart,
            domContentLoaded: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
            timestamp: new Date().toISOString()
        });
    });
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Export functions for global use
window.ClientDashboard = {
    showToastNotification,
    updateCounts,
    updateLoanStatuses,
    refreshCurrentPageData,
    formatCurrency,
    formatDate
};

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    showToastNotification('Erreur', 'Une erreur inattendue s\'est produite', 'danger');
});

// Service Worker registration (for offline support)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(error => console.log('SW registration failed'));
    });
}