(function () {
    'use strict';

    // ---- Sidebar Toggle (Mobile) ----
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // ---- CSRF Token Helper for AJAX ----
    window.AdminPanel = window.AdminPanel || {};

    AdminPanel.getCsrfToken = function () {
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    };

    AdminPanel.ajaxPost = function (url, data, callback) {
        data.csrf_token = AdminPanel.getCsrfToken();

        const formData = new FormData();
        for (const key in data) {
            formData.append(key, data[key]);
        }

        fetch(url, {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((result) => {
                if (typeof callback === 'function') callback(result);
            })
            .catch((error) => {
                console.error('AJAX Error:', error);
                AdminPanel.showToast('An error occurred. Please try again.', 'danger');
            });
    };

    // ---- Confirm Dialog for Dangerous Actions ----
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-confirm]');
        if (btn) {
            const message = btn.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });

    // ---- Toast Notification System ----
    AdminPanel.showToast = function (message, type) {
        type = type || 'info';

        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const iconMap = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
        };

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconMap[type] || iconMap.info} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    };

    // ---- Auto-dismiss Alerts ----
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
})();
