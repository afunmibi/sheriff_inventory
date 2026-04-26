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
    }
};

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