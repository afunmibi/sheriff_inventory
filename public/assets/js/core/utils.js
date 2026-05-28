/**
 * Core Utilities
 */

const Utils = {
    /**
     * HTTP Client
     */
    async request(url, options = {}) {
        const config = {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        };

        if (options.body) {
            config.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('Request error:', error);
            throw error;
        }
    },

    get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    },

    post(url, data, options = {}) {
        return this.request(url, { ...options, method: 'POST', body: data });
    },

    put(url, data, options = {}) {
        return this.request(url, { ...options, method: 'PUT', body: data });
    },

    delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    },

    /**
     * DOM Helpers
     */
    $(selector) {
        return document.querySelector(selector);
    },

    $$(selector) {
        return document.querySelectorAll(selector);
    },

    createElement(tag, attrs = {}, children = []) {
        const el = document.createElement(tag);
        
        for (const [key, value] of Object.entries(attrs)) {
            if (key === 'class') {
                el.className = value;
            } else if (key === 'style' && typeof value === 'object') {
                Object.assign(el.style, value);
            } else if (key.startsWith('data_')) {
                el.setAttribute(key.replace('_', '-'), value);
            } else {
                el.setAttribute(key, value);
            }
        }

        for (const child of children) {
            if (typeof child === 'string') {
                el.appendChild(document.createTextNode(child));
            } else if (child instanceof Node) {
                el.appendChild(child);
            }
        }

        return el;
    },

    addClass(el, ...classes) {
        el.classList.add(...classes);
        return el;
    },

    removeClass(el, ...classes) {
        el.classList.remove(...classes);
        return el;
    },

    toggleClass(el, className, force) {
        el.classList.toggle(className, force);
        return el;
    },

    hasClass(el, className) {
        return el.classList.contains(className);
    },

    /**
     * Event Delegation
     */
    on(element, event, selector, handler) {
        element.addEventListener(event, (e) => {
            const target = e.target.closest(selector);
            if (target) {
                handler.call(target, e, target);
            }
        });
        return () => element.removeEventListener(event, handler);
    },

    /**
     * Date Formatting
     */
    formatDate(date, format = 'dd M, yyyy') {
        const d = new Date(date);
        const map = {
            dd: String(d.getDate()).padStart(2, '0'),
            MM: String(d.getMonth() + 1).padStart(2, '0'),
            yyyy: d.getFullYear(),
            HH: String(d.getHours()).padStart(2, '0'),
            mm: String(d.getMinutes()).padStart(2, '0'),
            ss: String(d.getSeconds()).padStart(2, '0')
        };
        return format.replace(/dd|MM|yyyy|HH|mm|ss/g, m => map[m]);
    },

    formatDateTime(date) {
        return this.formatDate(date, 'dd M, yyyy HH:mm');
    },

    /**
     * Currency Formatting (NGN)
     */
    formatCurrency(amount, currency = 'NGN') {
        const symbols = { NGN: '₦', USD: '$', GBP: '£' };
        const symbol = symbols[currency] || currency;
        return `${symbol}${Number(amount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    },

    parseCurrency(value) {
        return parseFloat(value.replace(/[^0-9.-]/g, ''));
    },

    /**
     * Phone Formatting
     */
    formatPhone(phone) {
        phone = phone.replace(/\D/g, '');
        if (phone.length === 10 && phone[0] === '0') {
            return '+234' + phone.substring(1);
        }
        return phone;
    },

    validateNUBAN(accountNumber) {
        return /^\d{10,11}$/.test(accountNumber.replace(/\D/g, ''));
    },

    /**
     * Form Serialization
     */
    serializeForm(form) {
        const formData = new FormData(form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    },

    getFormData(form) {
        const data = {};
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox') {
                data[input.name] = input.checked;
            } else if (input.type !== 'file') {
                data[input.name] = input.value;
            }
        });
        return data;
    },

    /**
     * Validation
     */
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    validatePhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10;
    },

    validateRequired(value) {
        if (typeof value === 'string') return value.trim().length > 0;
        if (typeof value === 'number') return true;
        return value !== null && value !== undefined;
    },

    /**
     * Notification System
     */
    notifications: [],

    showNotification(message, type = 'info', duration = 3000) {
        const container = Utils.$('.notification-container') || this.createNotificationContainer();
        
        const notification = this.createElement('div', {
            class: `notification notification-${type}`,
            data_type: type
        }, [
            this.createElement('span', {}, [message])
        ]);

        container.appendChild(notification);
        this.notifications.push(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    },

    createNotificationContainer() {
        const container = this.createElement('div', { class: 'notification-container' });
        document.body.appendChild(container);
        return container;
    },

    showSuccess(message) {
        this.showNotification(message, 'success');
    },

    showError(message) {
        this.showNotification(message, 'error');
    },

    showWarning(message) {
        this.showNotification(message, 'warning');
    },

    showInfo(message) {
        this.showNotification(message, 'info');
    },

    /**
     * Loading State
     */
    showLoader(element) {
        if (!element) element = document.body;
        const loader = this.createElement('div', { class: 'loader-overlay' }, [
            this.createElement('div', { class: 'loader' })
        ]);
        element.dataset.loader = 'true';
        element.appendChild(loader);
    },

    hideLoader(element) {
        if (!element) element = document.body;
        const loader = element.querySelector('.loader-overlay');
        if (loader) loader.remove();
    },

    /**
     * Debounce & Throttle
     */
    debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    },

    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Local Storage
     */
    storage: {
        get(key, defaultValue = null) {
            const value = localStorage.getItem(key);
            try {
                return value ? JSON.parse(value) : defaultValue;
            } catch {
                return value || defaultValue;
            }
        },
        set(key, value) {
            localStorage.setItem(key, JSON.stringify(value));
        },
        remove(key) {
            localStorage.removeItem(key);
        },
        clear() {
            localStorage.clear();
        }
    },

    /**
     * Hub Context Management
     */
    getHubContext() {
        const params = new URLSearchParams(window.location.search);
        const hub = params.get('hub');
        if (hub) return hub;
        
        // Fallback to referring dashboard or current page
        const pathname = window.location.pathname;
        if (pathname.includes('ecommerce_dashboard.html') || pathname.includes('ecommerce-dashboard') || pathname.includes('ecommerce_dashboard')) return 'store';
        if (pathname.includes('dashboard.html') && !pathname.includes('ecommerce-dashboard')) return 'ops';
        
        // Persistent context from memory if possible
        return this.storage.get('current_hub', 'ops');
    },

    setHubContext(hub) {
        this.storage.set('current_hub', hub);
    },

    /**
     * Hub-Aware Sidebar Builder
     * Generates sidebar navigation HTML based on hub context.
     */
    buildSidebar(hub) {
        const isActive = (path) => {
            const current = window.location.pathname.split('/').pop().split('?')[0].replace(/\.html$/, '');
            return current === path.replace(/\.html$/, '') ? 'active' : '';
        };

        const storeLinks = `
            <a href="gateway.html" class="nav-item" style="background: rgba(59, 130, 246, 0.12); margin-bottom: 6px; border-radius: 8px;"><span>🏠</span> Hub Selection</a>
            <a href="ecommerce-dashboard" class="nav-item ${isActive('ecommerce_dashboard.html') || isActive('ecommerce-dashboard')}"><span>🌐</span> Dashboard</a>
            <div style="margin: 8px 16px; border-top: 1px solid rgba(255,255,255,0.06);"></div>
            <a href="products.html?hub=store" class="nav-item ${isActive('products.html')}"><span>✨</span> Products</a>
            <a href="carousel.html" class="nav-item ${isActive('carousel.html')}"><span>🎠</span> Carousel</a>
            <a href="sales.html?hub=store" class="nav-item ${isActive('sales.html')}"><span>🛒</span> Orders</a>
            <a href="customers.html" class="nav-item ${isActive('customers.html')}"><span>👥</span> Customers</a>
            <a href="settings.html?hub=store" class="nav-item ${isActive('settings.html')}"><span>⚙️</span> Settings</a>`;

        const opsLinks = `
            <a href="gateway.html" class="nav-item" style="background: rgba(14, 165, 233, 0.12); margin-bottom: 6px; border-radius: 8px;"><span>🏠</span> Hub Selection</a>
            <a href="dashboard.html" class="nav-item ${isActive('dashboard.html')}"><span>📊</span> Dashboard</a>
            <a href="sales.html?hub=ops" class="nav-item ${isActive('sales.html')}"><span>💰</span> Sales</a>
            <div style="margin: 8px 16px; border-top: 1px solid rgba(255,255,255,0.06);"></div>
            <a href="products.html?hub=ops" class="nav-item ${isActive('products.html')}"><span>📦</span> Products</a>
            <a href="categories.html" class="nav-item ${isActive('categories.html')}"><span>🏷️</span> Categories</a>
            <a href="suppliers.html" class="nav-item ${isActive('suppliers.html')}"><span>🤝</span> Suppliers</a>
            <a href="purchase-orders.html" class="nav-item ${isActive('purchase-orders.html')}"><span>📄</span> POs</a>
            <a href="inventory.html" class="nav-item ${isActive('inventory.html')}"><span>📋</span> Inventory</a>
            <a href="reports.html" class="nav-item ${isActive('reports.html')}"><span>📈</span> Reports</a>
            <a href="customers.html" class="nav-item ${isActive('customers.html')}"><span>👥</span> Customers</a>
            <a href="settings.html?hub=ops" class="nav-item ${isActive('settings.html')}"><span>⚙️</span> Settings</a>`;

        return hub === 'store' ? storeLinks : opsLinks;
    },

    /**
     * Role-Based Access Control (RBAC) & Theme Application
     */
    checkPermissions() {
        const user = this.storage.get('user');
        if (!user) return;

        const hub = this.getHubContext();
        this.setHubContext(hub); // Persist

        // Apply Global Hub Themes
        if (hub === 'store') {
            document.documentElement.style.setProperty('--primary', '#3b82f6');
            document.documentElement.style.setProperty('--primary-dark', '#1e40af');
            document.documentElement.style.setProperty('--primary-light', '#60a5fa');
            document.documentElement.style.setProperty('--bg-body', '#0f172a');
            document.documentElement.style.setProperty('--bg-card', '#1e293b');
            document.documentElement.style.setProperty('--border', '#1e293b');
            document.documentElement.style.setProperty('--text-primary', '#f1f5f9');
            document.documentElement.style.setProperty('--text-muted', '#94a3b8');
            document.documentElement.style.setProperty('--text-dim', '#64748b');
            document.body.style.background = '#0f172a';
            document.body.style.color = '#f1f5f9';
            document.body.classList.add('hub-store');
        } else {
            document.documentElement.style.setProperty('--primary', '#0ea5e9');
            document.documentElement.style.setProperty('--primary-dark', '#0369a1');
            document.documentElement.style.setProperty('--primary-light', '#38bdf8');
            document.documentElement.style.setProperty('--bg-body', '#f1f5f9');
            document.documentElement.style.setProperty('--bg-card', '#ffffff');
            document.documentElement.style.setProperty('--border', '#e2e8f0');
            document.documentElement.style.setProperty('--text-primary', '#0f172a');
            document.documentElement.style.setProperty('--text-muted', '#64748b');
            document.documentElement.style.setProperty('--text-dim', '#94a3b8');
            document.body.style.background = '#f1f5f9';
            document.body.style.color = '#0f172a';
            document.body.classList.add('hub-ops');
        }

        let role = (user.role || 'Staff').charAt(0).toUpperCase() + (user.role || 'Staff').slice(1).toLowerCase();
        if (role === 'Cashier') role = 'Staff';
        if (role === 'Administrator') role = 'Admin';

        const pathname = window.location.pathname;
        const page = pathname.split('/').pop().split('?')[0].replace(/\.html$/, '');

        const rules = {
            'Staff': ['gateway', 'dashboard', 'ecommerce-dashboard', 'ecommerce_dashboard', 'sales', 'inventory', 'returns', 'reports', 'products', 'customers'],
            'Manager': ['gateway', 'dashboard', 'ecommerce-dashboard', 'ecommerce_dashboard', 'sales', 'inventory', 'returns', 'suppliers', 'purchase-orders', 'reports', 'products', 'customers'],
            'Admin': ['gateway', 'dashboard', 'ecommerce-dashboard', 'ecommerce_dashboard', 'sales', 'inventory', 'returns', 'products', 'categories', 'suppliers', 'purchase-orders', 'reports', 'settings', 'carousel', 'customers']
        };

        const allowedPages = rules[role] || rules['Staff'];
        if (page && page !== '' && page !== 'index.php' && page !== 'login.php' && page !== 'landing.php' && !allowedPages.includes(page)) {
            window.location.href = 'gateway.html';
            return;
        }

        // Store allowed pages for later use by sidebar filter
        this._allowedPages = allowedPages;
    },

    /**
     * Mobile Menu Handler
     */
    initResponsive() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return; // Don't add menu if no sidebar exists

        // Only add hamburger if it doesn't exist
        if (!document.querySelector('.hamburger')) {
            const btn = document.createElement('button');
            btn.className = 'hamburger';
            btn.innerHTML = '☰';
            document.body.appendChild(btn);

            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);

            btn.onclick = () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            };

            overlay.onclick = () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            };

            // Close menu on nav click (for mobile)
            document.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            });
        }
    }
};

/**
 * Centralized sidebar builder — reads hub from URL or detects from page.
 */
Utils.initSidebar = function() {
    const sidebar = document.getElementById('dynamicSidebar');
    if (!sidebar) return;
    const hub = new URLSearchParams(window.location.search).get('hub')
        || (window.location.pathname.includes('ecommerce_dashboard') || window.location.pathname.includes('ecommerce-dashboard') ? 'store' : null)
        || Utils.storage.get('current_hub', 'ops');
    sidebar.innerHTML = Utils.buildSidebar(hub);
    const logo = document.querySelector('.sidebar-logo');
    if (logo) {
        logo.textContent = hub === 'store' ? '🏪 STORE HUB' : '⚙️ OPS HUB';
    }
    // Hide sidebar items the user doesn't have access to
    if (Utils._allowedPages) {
        document.querySelectorAll('.nav-item').forEach(item => {
            const hrefAttr = item.getAttribute('href') || '';
            const href = hrefAttr.split('?')[0].replace(/\.html$/, '');
            if (href && href !== '#' && href !== 'login.php' && href !== 'gateway') {
                if (!Utils._allowedPages.includes(href)) {
                    item.style.display = 'none';
                }
            }
        });
    }
};

// Auto-run: permissions check, sidebar init, responsive menu
document.addEventListener('DOMContentLoaded', () => {
    Utils.checkPermissions();
    Utils.initSidebar();
    Utils.initResponsive();
});

/**
 * State Management
 */
class Store {
    constructor() {
        this.state = {};
        this.listeners = [];
    }

    getState() {
        return this.state;
    }

    setState(newState) {
        const oldState = { ...this.state };
        this.state = { ...this.state, ...newState };
        this.notify(oldState);
    }

    subscribe(listener) {
        this.listeners.push(listener);
        return () => {
            this.listeners = this.listeners.filter(l => l !== listener);
        };
    }

    notify(oldState) {
        this.listeners.forEach(listener => listener(this.state, oldState));
    }

    reset() {
        this.state = {};
        this.notify({});
    }
}

/**
 * Base Component
 */
class BaseComponent {
    constructor(element, options = {}) {
        this.element = element;
        this.options = { ...this.defaultOptions(), ...options };
        this.init();
    }

    defaultOptions() {
        return {};
    }

    init() {
        this.render();
        this.bindEvents();
    }

    render() {
        // Override in subclass
    }

    bindEvents() {
        // Override in subclass
    }

    mount() {
        // Called when added to DOM
    }

    unmount() {
        // Called when removed from DOM
    }

    on(event, selector, handler) {
        this.element.addEventListener(event, (e) => {
            const target = e.target.closest(selector);
            if (target) handler.call(target, e, target);
        });
    }
}

/**
 * Modal Component
 */
class Modal {
    constructor(options = {}) {
        this.options = {
            title: '',
            content: '',
            size: 'medium',
            closable: true,
            ...options
        };
        this.element = null;
    }

    open() {
        if (!this.element) {
            this.create();
        }
        document.body.appendChild(this.element);
        requestAnimationFrame(() => {
            this.element.classList.add('show');
        });
    }

    close() {
        if (this.element) {
            this.element.classList.remove('show');
            setTimeout(() => {
                if (this.element && this.element.parentNode) {
                    this.element.parentNode.removeChild(this.element);
                }
                this.element = null;
            }, 300);
        }
    }

    create() {
        this.element = Utils.createElement('div', { class: 'modal-wrapper' }, [
            Utils.createElement('div', { class: `modal modal-${this.options.size}` }, [
                this.options.closable ? Utils.createElement('button', { class: 'modal-close', data_dismiss: 'modal' }, ['&times;']) : null,
                Utils.createElement('div', { class: 'modal-header' }, [
                    Utils.createElement('h3', { class: 'modal-title' }, [this.options.title])
                ]),
                Utils.createElement('div', { class: 'modal-body' }, [
                    typeof this.options.content === 'string' ? this.options.content : ''
                ]),
                this.options.footer ? Utils.createElement('div', { class: 'modal-footer' }, [this.options.footer]) : null
            ].filter(Boolean))
        ]);

        if (this.options.closable) {
            Utils.on(this.element, 'click', '[data-dismiss="modal"]', () => this.close());
            Utils.on(this.element, 'click', '.modal-wrapper', (e) => {
                if (e.target.classList.contains('modal-wrapper')) this.close();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.element) this.close();
        });
    }

    setContent(html) {
        if (this.element) {
            const body = this.element.querySelector('.modal-body');
            if (body) body.innerHTML = html;
        }
    }
}

/**
 * Data Table Component
 */
class DataTable {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            columns: [],
            data: [],
            sortable: true,
            searchable: true,
            pagination: true,
            perPage: 20,
            ...options
        };
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.searchQuery = '';
        this.init();
    }

    init() {
        this.render();
    }

    render() {
        this.container.innerHTML = '';
        this.container.className = 'data-table-container';

        const header = this.renderHeader();
        const body = this.renderBody();
        const footer = this.options.pagination ? this.renderFooter() : null;

        this.container.appendChild(header);
        this.container.appendChild(body);
        if (footer) this.container.appendChild(footer);
    }

    renderHeader() {
        const thead = Utils.createElement('thead');
        const tr = Utils.createElement('tr');

        this.options.columns.forEach(col => {
            const th = Utils.createElement('th', { 
                class: col.sortable !== false ? 'sortable' : '',
                data_column: col.key
            }, [col.label]);

            if (col.sortable !== false) {
                th.addEventListener('click', () => this.sort(col.key));
            }

            tr.appendChild(th);
        });

        thead.appendChild(tr);
        return thead;
    }

    renderBody() {
        const tbody = Utils.createElement('tbody');
        const data = this.getFilteredData();

        if (data.length === 0) {
            const tr = Utils.createElement('tr');
            tr.appendChild(Utils.createElement('td', { 
                colspan: this.options.columns.length,
                class: 'empty-state'
            }, ['No data available']));
            tbody.appendChild(tr);
            return tbody;
        }

        const start = (this.currentPage - 1) * this.options.perPage;
        const pageData = data.slice(start, start + this.options.perPage);

        pageData.forEach(row => {
            const tr = Utils.createElement('tr');
            this.options.columns.forEach(col => {
                const td = Utils.createElement('td');
                if (col.render) {
                    td.innerHTML = col.render(row[col.key], row);
                } else {
                    td.textContent = row[col.key] ?? '';
                }
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        return tbody;
    }

    renderFooter() {
        const data = this.getFilteredData();
        const totalPages = Math.ceil(data.length / this.options.perPage);
        
        const footer = Utils.createElement('div', { class: 'table-footer' });
        
        footer.appendChild(Utils.createElement('span', {}, [
            `Showing ${(this.currentPage - 1) * this.options.perPage + 1}-${Math.min(this.currentPage * this.options.perPage, data.length)} of ${data.length}`
        ]));

        const pagination = Utils.createElement('div', { class: 'pagination' });
        
        if (this.currentPage > 1) {
            pagination.appendChild(Utils.createElement('button', { class: 'btn btn-sm' }, ['Previous']).addEventListener('click', () => {
                this.currentPage--;
                this.render();
            }));
        }

        if (this.currentPage < totalPages) {
            pagination.appendChild(Utils.createElement('button', { class: 'btn btn-sm' }, ['Next']).addEventListener('click', () => {
                this.currentPage++;
                this.render();
            }));
        }

        footer.appendChild(pagination);
        return footer;
    }

    getFilteredData() {
        let data = [...this.options.data];

        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            data = data.filter(row => 
                Object.values(row).some(val => 
                    String(val).toLowerCase().includes(query)
                )
            );
        }

        if (this.sortColumn) {
            data.sort((a, b) => {
                const aVal = a[this.sortColumn];
                const bVal = b[this.sortColumn];
                if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
        }

        return data;
    }

    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        this.render();
    }

    setData(data) {
        this.options.data = data;
        this.currentPage = 1;
        this.render();
    }

    search(query) {
        this.searchQuery = query;
        this.currentPage = 1;
        this.render();
    }
}

/**
 * Alert Widget
 */
class AlertWidget {
    constructor(container) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
    }

    show(type, message, dismissible = true) {
        const alert = Utils.createElement('div', { 
            class: `alert alert-${type} ${dismissible ? 'alert-dismissible' : ''}`
        }, [
            message,
            dismissible ? Utils.createElement('button', { 
                class: 'btn-close',
                type: 'button'
            }, ['&times;']) : null
        ].filter(Boolean));

        if (dismissible) {
            alert.querySelector('.btn-close').addEventListener('click', () => alert.remove());
        }

        this.container.appendChild(alert);
        return alert;
    }

    success(message) { return this.show('success', message); }
    error(message) { return this.show('error', message); }
    warning(message) { return this.show('warning', message); }
    info(message) { return this.show('info', message); }
}

/**
 * Stats Card Component
 */
class StatsCard {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            label: '',
            value: 0,
            icon: '',
            trend: null,
            color: 'primary',
            ...options
        };
    }

    render() {
        const card = Utils.createElement('div', { class: `stats-card stats-card-${this.options.color}` }, [
            this.options.icon ? Utils.createElement('div', { class: 'stats-icon' }, [this.options.icon]) : null,
            Utils.createElement('div', { class: 'stats-content' }, [
                Utils.createElement('span', { class: 'stats-label' }, [this.options.label]),
                Utils.createElement('span', { class: 'stats-value' }, [
                    typeof this.options.value === 'number' ? this.options.value.toLocaleString() : this.options.value
                ])
            ]),
            this.options.trend ? Utils.createElement('div', { 
                class: `stats-trend ${this.options.trend > 0 ? 'positive' : 'negative'}`
            }, [
                this.options.trend > 0 ? '↑' : '↓',
                Math.abs(this.options.trend) + '%'
            ]) : null
        ]);

        this.container.appendChild(card);
        return card;
    }

    update(value) {
        this.options.value = value;
        const valueEl = this.container.querySelector('.stats-value');
        if (valueEl) valueEl.textContent = value.toLocaleString();
    }
}

// Export for global use
window.Utils = Utils;
window.Store = Store;
window.BaseComponent = BaseComponent;
window.Modal = Modal;
window.DataTable = DataTable;
window.AlertWidget = AlertWidget;
window.StatsCard = StatsCard;