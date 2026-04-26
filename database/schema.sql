-- Tech Application Inventory Management System
-- Database Schema for Nigerian Retail Business
-- MySQL 5.7+

-- Create database
CREATE DATABASE IF NOT EXISTS sheriff_inventory
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sheriff_inventory;

-- =============================================================================
-- USERS TABLE
-- =============================================================================
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'cashier', 'warehouse_staff') NOT NULL DEFAULT 'cashier',
    permissions JSON,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME,
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- =============================================================================
-- SETTINGS/CONFIGURATION TABLE
-- =============================================================================
CREATE TABLE settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, category) VALUES
('currency_symbol', '₦', 'currency'),
('currency_code', 'NGN', 'currency'),
('tax_rate', '0', 'tax'),
('business_name', 'Tech Application', 'business'),
('business_address', '', 'business'),
('business_phone', '', 'business'),
('business_email', '', 'business'),
('low_stock_threshold', '10', 'inventory'),
('session_timeout', '900', 'security'),
('timezone', 'Africa/Lagos', 'system');

-- =============================================================================
-- SUPPLIERS TABLE
-- =============================================================================
CREATE TABLE suppliers (
    supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(150),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    payment_terms VARCHAR(50),
    account_name VARCHAR(100),
    account_number VARCHAR(11),
    bank_name VARCHAR(100),
    lead_time_days INT DEFAULT 7,
    is_preferred TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_name (company_name),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_is_preferred (is_preferred),
    CONSTRAINT chk_account_number CHECK (account_number IS NULL OR LENGTH(account_number) = 11)
) ENGINE=InnoDB;

-- =============================================================================
-- PRODUCTS TABLE
-- =============================================================================
CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    sku VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(200) NOT NULL,
    category ENUM('chargers', 'cables', 'adapters', 'power_supplies', 'hubs', 'other') NOT NULL,
    subcategory VARCHAR(100),
    description TEXT,
    specifications JSON,
    cost_price DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    markup_percentage DECIMAL(5, 2) GENERATED ALWAYS AS (
        CASE 
            WHEN cost_price > 0 THEN ((selling_price - cost_price) / cost_price) * 100 
            ELSE 0 
        END
    ) STORED,
    reorder_level INT NOT NULL DEFAULT 10,
    unit_of_measurement ENUM('pieces', 'sets', 'packs') DEFAULT 'pieces',
    image_url VARCHAR(500),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sku (sku),
    INDEX idx_product_name (product_name),
    INDEX idx_category (category),
    INDEX idx_subcategory (subcategory),
    INDEX idx_is_active (is_active),
    INDEX idx_cost_price (cost_price),
    INDEX idx_selling_price (selling_price),
    CONSTRAINT chk_cost_price CHECK (cost_price >= 0),
    CONSTRAINT chk_selling_price CHECK (selling_price >= 0),
    CONSTRAINT chk_reorder_level CHECK (reorder_level >= 0)
) ENGINE=InnoDB;

-- =============================================================================
-- PRODUCT SUPPLIERS (Many-to-Many)
-- =============================================================================
CREATE TABLE product_suppliers (
    product_supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    supplier_sku VARCHAR(100),
    supplier_cost DECIMAL(12, 2),
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    UNIQUE KEY uk_product_supplier (product_id, supplier_id),
    INDEX idx_product_id (product_id),
    INDEX idx_supplier_id (supplier_id)
) ENGINE=InnoDB;

-- =============================================================================
-- INVENTORY/STOCK TABLE
-- =============================================================================
CREATE TABLE inventory (
    inventory_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL UNIQUE,
    quantity_on_hand INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    quantity_available INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
    last_restock_date DATE,
    warehouse_location VARCHAR(50),
    batch_number VARCHAR(100),
    serial_numbers JSON,
    status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_quantity_on_hand (quantity_on_hand),
    INDEX idx_last_restock_date (last_restock_date),
    CONSTRAINT chk_quantity_on_hand CHECK (quantity_on_hand >= 0),
    CONSTRAINT chk_quantity_reserved CHECK (quantity_reserved >= 0)
) ENGINE=InnoDB;

-- =============================================================================
-- PURCHASE ORDERS TABLE
-- =============================================================================
CREATE TABLE purchase_orders (
    po_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    po_date DATE NOT NULL,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    status ENUM('draft', 'submitted', 'approved', 'received', 'partially_received', 'cancelled') DEFAULT 'draft',
    total_amount DECIMAL(12, 2) DEFAULT 0.00,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    notes TEXT,
    created_by INT UNSIGNED,
    approved_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_po_number (po_number),
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_status (status),
    INDEX idx_po_date (po_date),
    INDEX idx_payment_status (payment_status),
    CONSTRAINT chk_total_amount CHECK (total_amount >= 0)
) ENGINE=InnoDB;

-- =============================================================================
-- PURCHASE ORDER ITEMS TABLE
-- =============================================================================
CREATE TABLE purchase_order_items (
    po_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity_ordered INT NOT NULL DEFAULT 0,
    quantity_received INT NOT NULL DEFAULT 0,
    unit_cost DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(12, 2) GENERATED ALWAYS AS (quantity_ordered * unit_cost) STORED,
    status ENUM('pending', 'received', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    INDEX idx_po_id (po_id),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    CONSTRAINT chk_quantity_ordered CHECK (quantity_ordered > 0),
    CONSTRAINT chk_quantity_received CHECK (quantity_received >= 0),
    CONSTRAINT chk_unit_cost CHECK (unit_cost >= 0)
) ENGINE=InnoDB;

-- =============================================================================
-- SALES TRANSACTIONS TABLE
-- =============================================================================
CREATE TABLE sales_transactions (
    transaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    sale_date DATE NOT NULL,
    sale_time TIME NOT NULL,
    customer_name VARCHAR(150),
    customer_phone VARCHAR(20),
    customer_email VARCHAR(150),
    customer_address TEXT,
    product_id INT UNSIGNED NOT NULL,
    quantity_sold INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12, 2) NOT NULL,
    line_total DECIMAL(12, 2) GENERATED ALWAYS AS (quantity_sold * unit_price) STORED,
    payment_method ENUM('cash', 'bank_transfer', 'paystack', 'pos') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    cashier_id INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_sale_date (sale_date),
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_product_id (product_id),
    INDEX idx_payment_method (payment_method),
    INDEX idx_payment_status (payment_status),
    INDEX idx_cashier_id (cashier_id),
    CONSTRAINT chk_quantity_sold CHECK (quantity_sold > 0),
    CONSTRAINT chk_unit_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

-- =============================================================================
-- PAYMENT TRANSACTIONS TABLE (For Paystack Integration)
-- =============================================================================
CREATE TABLE payment_transactions (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED,
    paystack_reference VARCHAR(50) NOT NULL UNIQUE,
    amount DECIMAL(12, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NGN',
    status ENUM('pending', 'success', 'failed', 'disputed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    customer_email VARCHAR(150),
    customer_phone VARCHAR(20),
    paystack_response JSON,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales_transactions(transaction_id) ON DELETE SET NULL,
    INDEX idx_paystack_reference (paystack_reference),
    INDEX idx_sale_id (sale_id),
    INDEX idx_status (status),
    INDEX idx_customer_email (customer_email),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- =============================================================================
-- STOCK ADJUSTMENTS TABLE
-- =============================================================================
CREATE TABLE stock_adjustments (
    adjustment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    adjustment_type ENUM('recount', 'damage', 'loss', 'return', 'stock_take', 'bonus') NOT NULL,
    quantity_adjusted INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reason TEXT,
    adjusted_by INT UNSIGNED,
    adjustment_date DATE NOT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (adjusted_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_adjustment_type (adjustment_type),
    INDEX idx_adjustment_date (adjustment_date),
    INDEX idx_adjustment_status (approval_status),
    INDEX idx_adjusted_by (adjusted_by)
) ENGINE=InnoDB;

-- =============================================================================
-- AUDIT LOG TABLE
-- =============================================================================
CREATE TABLE audit_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action ENUM('CREATE', 'READ', 'UPDATE', 'DELETE', 'EXPORT', 'LOGIN', 'LOGOUT', 'APPROVE', 'REJECT') NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- =============================================================================
-- STOCK RESERVATIONS TABLE (For order processing)
-- =============================================================================
CREATE TABLE stock_reservations (
    reservation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    quantity_reserved INT NOT NULL,
    order_reference VARCHAR(50),
    status ENUM('active', 'released', 'fulfilled') DEFAULT 'active',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    CONSTRAINT chk_quantity_reserved CHECK (quantity_reserved > 0)
) ENGINE=InnoDB;

-- =============================================================================
-- PROCEDURES
-- =============================================================================

DELIMITER //

-- Procedure to auto-update inventory status
CREATE PROCEDURE update_inventory_status()
BEGIN
    UPDATE inventory i
    JOIN products p ON i.product_id = p.product_id
    SET i.status = CASE
        WHEN i.quantity_on_hand <= 0 THEN 'out_of_stock'
        WHEN i.quantity_on_hand <= p.reorder_level THEN 'low_stock'
        ELSE 'in_stock'
    END
    WHERE p.is_active = 1;
END //

-- Procedure to generate PO number
CREATE PROCEDURE generate_po_number(IN prefix VARCHAR(10), OUT po_num VARCHAR(20))
BEGIN
    DECLARE next_num INT DEFAULT 1;
    DECLARE year_str VARCHAR(4);
    
    SELECT YEAR(CURRENT_DATE) INTO year_str;
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(po_number, 9) AS UNSIGNED)), 0) + 1
    INTO next_num
    FROM purchase_orders
    WHERE po_number LIKE CONCAT(prefix, '-', year_str, '%');
    
    SET po_num = CONCAT(prefix, '-', year_str, '-', LPAD(next_num, 4, '0'));
END //

-- Procedure to generate Invoice number
CREATE PROCEDURE generate_invoice_number(IN prefix VARCHAR(10), OUT inv_num VARCHAR(20))
BEGIN
    DECLARE next_num INT DEFAULT 1;
    DECLARE year_str VARCHAR(4);
    
    SELECT YEAR(CURRENT_DATE) INTO year_str;
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number, 9) AS UNSIGNED)), 0) + 1
    INTO next_num
    FROM sales_transactions
    WHERE invoice_number LIKE CONCAT(prefix, '-', year_str, '%');
    
    SET inv_num = CONCAT(prefix, '-', year_str, '-', LPAD(next_num, 4, '0'));
END //

-- Procedure to update product stock after sale
CREATE PROCEDURE deduct_stock(IN p_product_id INT, IN p_quantity INT)
BEGIN
    DECLARE current_stock INT;
    DECLARE new_stock INT;
    
    SELECT quantity_on_hand INTO current_stock
    FROM inventory
    WHERE product_id = p_product_id
    FOR UPDATE;
    
    IF current_stock >= p_quantity THEN
        SET new_stock = current_stock - p_quantity;
        UPDATE inventory 
        SET quantity_on_hand = new_stock,
            last_restock_date = CURRENT_DATE
        WHERE product_id = p_product_id;
        
        CALL update_inventory_status();
        
        SELECT new_stock AS new_stock_level;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Insufficient stock available';
    END IF;
END //

-- Procedure to add stock from PO receipt
CREATE PROCEDURE add_stock(IN p_product_id INT, IN p_quantity INT, IN p_unit_cost DECIMAL(12,2))
BEGIN
    DECLARE current_stock INT;
    DECLARE new_stock INT;
    DECLARE current_cost DECIMAL(12,2);
    
    SELECT COALESCE(quantity_on_hand, 0), COALESCE(cost_price, 0)
    INTO current_stock, current_cost
    FROM inventory
    WHERE product_id = p_product_id;
    
    SET new_stock = current_stock + p_quantity;
    
    IF current_stock = 0 THEN
        INSERT INTO inventory (product_id, quantity_on_hand, last_restock_date, status)
        VALUES (p_product_id, p_quantity, CURRENT_DATE, 'in_stock')
        ON DUPLICATE KEY UPDATE 
            quantity_on_hand = p_quantity,
            last_restock_date = CURRENT_DATE;
    ELSE
        UPDATE inventory 
        SET quantity_on_hand = new_stock,
            last_restock_date = CURRENT_DATE
        WHERE product_id = p_product_id;
    END IF;
    
    IF p_unit_cost > 0 AND p_unit_cost != current_cost THEN
        UPDATE products 
        SET cost_price = p_unit_cost
        WHERE product_id = p_product_id;
    END IF;
    
    CALL update_inventory_status();
    
    SELECT new_stock AS new_stock_level;
END //

DELIMITER ;

-- =============================================================================
-- VIEWS
-- =============================================================================

-- View: Products with stock information
CREATE VIEW v_products_with_stock AS
SELECT 
    p.*,
    COALESCE(i.quantity_on_hand, 0) AS stock_quantity,
    COALESCE(i.quantity_available, 0) AS available_quantity,
    COALESCE(i.quantity_reserved, 0) AS reserved_quantity,
    COALESCE(i.status, 'out_of_stock') AS stock_status,
    i.warehouse_location,
    i.last_restock_date
FROM products p
LEFT JOIN inventory i ON p.product_id = i.product_id
WHERE p.is_active = 1;

-- View: Sales with product and user details
CREATE VIEW v_sales_details AS
SELECT 
    s.*,
    p.product_name,
    p.sku,
    p.category,
    u.name AS cashier_name,
    u.role AS cashier_role
FROM sales_transactions s
JOIN products p ON s.product_id = p.product_id
LEFT JOIN users u ON s.cashier_id = u.user_id;

-- View: Purchase Orders with supplier info
CREATE VIEW v_purchase_orders_details AS
SELECT 
    po.*,
    s.company_name,
    s.contact_person,
    s.phone AS supplier_phone,
    s.email AS supplier_email,
    u_creator.name AS created_by_name,
    u_approver.name AS approved_by_name
FROM purchase_orders po
JOIN suppliers s ON po.supplier_id = s.supplier_id
LEFT JOIN users u_creator ON po.created_by = u_creator.user_id
LEFT JOIN users u_approver ON po.approved_by = u_approver.user_id;

-- View: Low stock products
CREATE VIEW v_low_stock_products AS
SELECT 
    p.product_id,
    p.sku,
    p.product_name,
    p.category,
    p.reorder_level,
    COALESCE(i.quantity_on_hand, 0) AS current_stock,
    COALESCE(i.quantity_available, 0) AS available_stock,
    (p.reorder_level - COALESCE(i.quantity_on_hand, 0)) AS deficit
FROM products p
LEFT JOIN inventory i ON p.product_id = i.product_id
WHERE p.is_active = 1 
AND COALESCE(i.quantity_on_hand, 0) <= p.reorder_level
ORDER BY deficit DESC;

-- View: Daily sales summary
CREATE VIEW v_daily_sales_summary AS
SELECT 
    sale_date,
    COUNT(*) AS transaction_count,
    SUM(line_total) AS total_revenue,
    AVG(line_total) AS average_sale,
    SUM(quantity_sold) AS total_items_sold
FROM sales_transactions
WHERE payment_status = 'completed'
GROUP BY sale_date;

-- =============================================================================
-- TRIGGERS
-- =============================================================================

DELIMITER //

-- Trigger: Update inventory status after stock change
CREATE TRIGGER tr_inventory_after_update
AFTER UPDATE ON inventory
FOR EACH ROW
BEGIN
    DECLARE reorder INT;
    
    SELECT COALESCE(p.reorder_level, 10) INTO reorder
    FROM products p WHERE product_id = NEW.product_id;
    
    IF NEW.quantity_on_hand <= 0 THEN
        UPDATE inventory SET status = 'out_of_stock' WHERE inventory_id = NEW.inventory_id;
    ELSEIF NEW.quantity_on_hand <= reorder THEN
        UPDATE inventory SET status = 'low_stock' WHERE inventory_id = NEW.inventory_id;
    ELSE
        UPDATE inventory SET status = 'in_stock' WHERE inventory_id = NEW.inventory_id;
    END IF;
END //

-- Trigger: Log new product creation
CREATE TRIGGER tr_product_after_insert
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_values, created_at)
    VALUES (NULL, 'CREATE', 'product', NEW.product_id, 
            JSON_OBJECT('product_name', NEW.product_name, 'sku', NEW.sku, 'selling_price', NEW.selling_price),
            NOW());
END //

-- Trigger: Log new sale
CREATE TRIGGER tr_sale_after_insert
AFTER INSERT ON sales_transactions
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_values, created_at)
    VALUES (NEW.cashier_id, 'CREATE', 'sale', NEW.transaction_id,
            JSON_OBJECT('invoice_number', NEW.invoice_number, 'line_total', NEW.line_total),
            NOW());
END //

DELIMITER ;
