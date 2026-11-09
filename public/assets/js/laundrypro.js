/**
 * LaundryPro Main JavaScript
 */

window.LaundryPro = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

    init() {
        this.initTheme();
        this.initSidebar();
        this.initTooltips();
        this.initAutoDismissAlerts();
    },

    initTheme() {
        const savedTheme = localStorage.getItem('lp_theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        const toggleBtn = document.getElementById('themeToggleBtn');
        if (toggleBtn) {
            toggleBtn.innerHTML = savedTheme === 'dark' ? '<i class="bi bi-sun-fill text-warning"></i>' : '<i class="bi bi-moon-stars-fill"></i>';
            toggleBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-bs-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('lp_theme', next);
                toggleBtn.innerHTML = next === 'dark' ? '<i class="bi bi-sun-fill text-warning"></i>' : '<i class="bi bi-moon-stars-fill"></i>';
            });
        }
    },

    initSidebar() {
        const toggle = document.getElementById('sidebarToggleBtn');
        const sidebar = document.querySelector('.app-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    },

    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    initAutoDismissAlerts() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            });
        }, 5000);
    },

    async fetch(url, options = {}) {
        options.headers = options.headers || {};
        if (!options.headers['X-CSRF-TOKEN'] && this.csrfToken) {
            options.headers['X-CSRF-TOKEN'] = this.csrfToken;
        }
        if (!options.headers['Accept']) {
            options.headers['Accept'] = 'application/json';
        }

        const res = await fetch(url, options);
        if (res.status === 419) {
            this.toast('Token kedaluwarsa, memuat ulang...', 'warning');
            setTimeout(() => window.location.reload(), 1200);
            return null;
        }
        return res;
    },

    toast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' || type === 'error' ? 'bg-danger text-white' : 'bg-primary text-white');
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center ${bgClass} border-0 shadow`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-medium">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    },

    confirm(message, onConfirm) {
        if (confirm(message)) {
            onConfirm();
        }
    },

    formatRupiah(number) {
        return 'Rp ' + Number(number || 0).toLocaleString('id-ID');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    LaundryPro.init();
});

window.SimLaundry = window.LaundryPro;
