/**
 * Installation & Maintenance Tracking System
 * Main JavaScript File
 * Compatible with all browsers (no ES2020+ features)
 */

// Global App Object
var App = {
    baseUrl: '',

    // Initialize
    init: function() {
        var baseUrlMeta = document.querySelector('meta[name="base-url"]');
        this.baseUrl = baseUrlMeta ? baseUrlMeta.content : '';
        this.initSidebar();
        this.initTooltips();
        this.initConfirmations();
        this.initNotifications();
    },

    // Sidebar Toggle
    initSidebar: function() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.querySelector('.sidebar-overlay');
        var toggleBtns = document.querySelectorAll('.mobile-toggle, .sidebar-toggle');

        toggleBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (sidebar) sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            });
        });

        if (overlay) {
            overlay.addEventListener('click', function() {
                if (sidebar) sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    },

    // Bootstrap Tooltips
    initTooltips: function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    },

    // Confirmation Dialogs
    initConfirmations: function() {
        document.querySelectorAll('[data-confirm]').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var message = this.dataset.confirm || 'Are you sure?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    },

    // Notifications Polling
    initNotifications: function() {
        var notifBadge = document.querySelector('.notification-badge');
        var self = this;
        if (notifBadge) {
            this.checkNotifications();
            setInterval(function() {
                self.checkNotifications();
            }, 60000); // Every minute
        }
    },

    checkNotifications: function() {
        this.ajax('../../ajax/notifications.php', { action: 'count' })
            .then(function(response) {
                if (response.success) {
                    var badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.textContent = response.count;
                        badge.style.display = response.count > 0 ? 'flex' : 'none';
                    }
                }
            });
    }
};

// AJAX Utility - Compatible version
App.ajax = function(url, data, method) {
    data = data || {};
    method = method || 'POST';

    var formData = new FormData();
    Object.keys(data).forEach(function(key) {
        if (data[key] instanceof File) {
            formData.append(key, data[key]);
        } else if (typeof data[key] === 'object' && data[key] !== null) {
            formData.append(key, JSON.stringify(data[key]));
        } else if (data[key] !== null && data[key] !== undefined) {
            formData.append(key, data[key]);
        }
    });

    return fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.text().then(function(text) {
                // Try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    // If JSON parse fails, check if it's an error response
                    if (!response.ok) {
                        console.error('Server Error:', response.status, response.statusText);
                        console.error('Response Body:', text.substring(0, 500));
                        return { success: false, message: 'Server error: ' + response.status };
                    }
                    console.error('Invalid JSON Response:', text.substring(0, 500));
                    return { success: false, message: 'Invalid response from server' };
                }
            });
        })
        .catch(function(error) {
            console.error('AJAX Error:', error);
            return { success: false, message: 'Connection error. Please try again.' };
        });
};

// Toast Notification
App.toast = function(message, type) {
    type = type || 'success';
    var toastContainer = document.getElementById('toast-container') || createToastContainer();

    var toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center text-white bg-' + type + ' border-0';
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML =
        '<div class="d-flex">' +
        '<div class="toast-body">' + message + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
        '</div>';

    toastContainer.appendChild(toastEl);
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
};

function createToastContainer() {
    var container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Loading Spinner
App.showLoading = function() {
    var overlay = document.querySelector('.spinner-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'spinner-overlay';
        overlay.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
};

App.hideLoading = function() {
    var overlay = document.querySelector('.spinner-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
};

// Form Validation
App.validateForm = function(form) {
    var isValid = true;
    var requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(function(field) {
        field.classList.remove('is-invalid');
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        }
    });

    return isValid;
};

// Format Currency
App.formatCurrency = function(amount, symbol) {
    symbol = symbol || '₱';
    return symbol + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

// Format Date
App.formatDate = function(dateString, format) {
    format = format || 'short';
    var date = new Date(dateString);
    var options = format === 'long' ? { year: 'numeric', month: 'long', day: 'numeric' } : { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-PH', options);
};

// Debounce Function
App.debounce = function(func, wait) {
    var timeout;
    return function() {
        var context = this;
        var args = arguments;
        var later = function() {
            clearTimeout(timeout);
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// DataTable Defaults
App.initDataTable = function(selector, options) {
    options = options || {};
    var defaults = {
        responsive: true,
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'All']
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: '_MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries found',
            emptyTable: 'No data available'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    };

    // Merge defaults with options
    for (var key in options) {
        defaults[key] = options[key];
    }

    return $(selector).DataTable(defaults);
};

// GPS Location
App.getLocation = function() {
    return new Promise(function(resolve, reject) {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation is not supported'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                });
            },
            function(error) {
                var message = 'Unable to get location';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Location permission denied';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Location information unavailable';
                        break;
                    case error.TIMEOUT:
                        message = 'Location request timed out';
                        break;
                }
                reject(new Error(message));
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
};

// Image Preview
App.previewImage = function(input, previewElement) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.querySelector(previewElement);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
};

// File Size Validation
App.validateFileSize = function(file, maxSizeMB) {
    maxSizeMB = maxSizeMB || 10;
    var maxBytes = maxSizeMB * 1024 * 1024;
    if (file.size > maxBytes) {
        return {
            valid: false,
            message: 'File size exceeds ' + maxSizeMB + 'MB limit'
        };
    }
    return { valid: true };
};

// Print Content
App.printContent = function(elementId) {
    var content = document.getElementById(elementId);
    if (!content) return;

    var printWindow = window.open('', '_blank');
    printWindow.document.write(
        '<!DOCTYPE html>' +
        '<html>' +
        '<head>' +
        '<title>Print</title>' +
        '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
        '<style>' +
        'body { padding: 20px; }' +
        '@media print { .no-print { display: none !important; } }' +
        '</style>' +
        '</head>' +
        '<body>' +
        content.innerHTML +
        '<script>window.onload = function() { window.print(); window.close(); }<\/script>' +
        '</body>' +
        '</html>'
    );
    printWindow.document.close();
};

// Export Table to CSV
App.exportTableToCSV = function(tableId, filename) {
    filename = filename || 'export.csv';
    var table = document.getElementById(tableId);
    if (!table) return;

    var csv = [];
    var rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        var cols = row.querySelectorAll('td, th');
        var rowData = [];
        cols.forEach(function(col) {
            var text = col.innerText.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
};

// Confirm Modal
App.confirm = function(message, onConfirm, onCancel) {
    var modal = document.getElementById('confirmModal') || createConfirmModal();
    var modalBody = modal.querySelector('.modal-body');
    var confirmBtn = modal.querySelector('.btn-confirm');
    var cancelBtn = modal.querySelector('.btn-cancel');

    modalBody.textContent = message;

    var bsModal = new bootstrap.Modal(modal);

    confirmBtn.onclick = function() {
        bsModal.hide();
        onConfirm();
    };

    cancelBtn.onclick = function() {
        bsModal.hide();
        if (onCancel) onCancel();
    };

    bsModal.show();
};

function createConfirmModal() {
    var modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.className = 'modal fade';
    modal.innerHTML =
        '<div class="modal-dialog modal-dialog-centered">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<h5 class="modal-title">Confirm Action</h5>' +
        '<button type="button" class="btn-close btn-cancel" data-bs-dismiss="modal"></button>' +
        '</div>' +
        '<div class="modal-body"></div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">Cancel</button>' +
        '<button type="button" class="btn btn-primary btn-confirm">Confirm</button>' +
        '</div>' +
        '</div>' +
        '</div>';
    document.body.appendChild(modal);
    return modal;
}

// Search Filter
App.initSearchFilter = function(inputSelector, targetSelector, itemSelector) {
    var input = document.querySelector(inputSelector);
    var target = document.querySelector(targetSelector);

    if (!input || !target) return;

    input.addEventListener('input', App.debounce(function() {
        var searchTerm = this.value.toLowerCase();
        var items = target.querySelectorAll(itemSelector);

        items.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }, 300));
};

// Number Input Validation
App.initNumberInputs = function() {
    document.querySelectorAll('input[type="number"]').forEach(function(input) {
        input.addEventListener('input', function() {
            var min = parseFloat(this.min) || 0;
            var max = parseFloat(this.max) || Infinity;
            var value = parseFloat(this.value);

            if (isNaN(value)) value = min;
            if (value < min) value = min;
            if (value > max) value = max;

            this.value = value;
        });
    });
};

// Auto-save Draft
App.initAutoSave = function(formId, storageKey) {
    var form = document.getElementById(formId);
    if (!form) return;

    // Load saved data
    var savedData = localStorage.getItem(storageKey);
    if (savedData) {
        try {
            var data = JSON.parse(savedData);
            Object.keys(data).forEach(function(key) {
                var field = form.querySelector('[name="' + key + '"]');
                if (field && field.type !== 'file') {
                    field.value = data[key];
                }
            });
        } catch (e) {}
    }

    // Save on input
    form.addEventListener('input', App.debounce(function() {
        var formData = new FormData(form);
        var data = {};
        formData.forEach(function(value, key) {
            if (typeof value === 'string') {
                data[key] = value;
            }
        });
        localStorage.setItem(storageKey, JSON.stringify(data));
    }, 1000));

    // Clear on submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
    });
};

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    App.init();
});