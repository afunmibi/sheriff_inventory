# Tech Application - Inventory Management System (IMS)

A complete Inventory Management System for Nigerian retail businesses selling computer accessories (chargers, cables, adapters, power supplies, USB hubs).

## Technology Stack

- **Backend:** PHP 8.1+ with MySQLi
- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Database:** MySQL 5.7+
- **Payment:** Paystack (Nigeria)

## Features

### Core Functionality
- Product management with SKU tracking
- Real-time inventory management
- Stock adjustments (recount, damage, loss, returns)
- Sales transactions with multiple payment methods
- Purchase order management
- Supplier management with NUBAN bank accounts

### Reports & Analytics
- Daily/Monthly sales reports
- Inventory valuation
- Top products analysis
- Financial reports (revenue, profit margins)

### Security
- Role-based access control (Admin, Manager, Cashier, Warehouse)
- Password hashing with bcrypt
- Audit logging for all operations

## Installation

### 1. Database Setup
```bash
# Create database and import schema
mysql -u root -p < database/schema.sql
```

### 2. Environment Configuration
```bash
# Copy .env.example to .env and configure
cp .env.example .env
```

Edit `.env`:
```env
DB_HOST=localhost
DB_DATABASE=sheriff_inventory
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Start Server
```bash
php -S localhost:8080 -t public
```

### 4. Access Application
```
http://localhost:8080/login.html
```

### Default Login
- **Email:** admin@techapp.com
- **Password:** Admin@123

## Project Structure

```
sheriff_inventory/
├── app/
│   ├── config/          # Database & Config
│   ├── controllers/    # API Controllers
│   ├── core/          # Logger, Router
│   ├── exceptions/    # Custom Exceptions
│   ├── helpers/       # Helper Functions
│   └── models/        # Data Models
├── database/
│   └── schema.sql      # Database Schema
├── public/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── core/
│   │   │   └── utils.js
│   │   └── services/
│   │       └── apiClient.js
│   ├── login.html
│   ├── dashboard.html
│   ├── products.html
│   ├── inventory.html
│   ├── sales.html
│   ├── suppliers.html
│   ├── purchase-orders.html
│   ├── reports.html
│   └── settings.html
├── .env
├── index.php
└── setup.php
```

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth/login` | POST | User login |
| `/api/auth/logout` | POST | User logout |
| `/api/products` | GET/POST | List/Create products |
| `/api/products/{id}` | GET/PUT/DELETE | Product operations |
| `/api/inventory` | GET | List inventory |
| `/api/inventory/{id}/adjust` | POST | Adjust stock |
| `/api/sales` | GET/POST | List/Create sales |
| `/api/sales/daily` | GET | Daily sales summary |
| `/api/suppliers` | GET/POST | List/Create suppliers |
| `/api/purchase-orders` | GET/POST | List/Create POs |
| `/api/dashboard` | GET | Dashboard metrics |
| `/api/reports` | GET | Reports |

## Database Tables

| Table | Description |
|-------|-------------|
| `users` | System users |
| `products` | Product catalog |
| `inventory` | Stock levels |
| `suppliers` | Supplier records |
| `purchase_orders` | Purchase orders |
| `purchase_order_items` | PO line items |
| `sales_transactions` | Sales records |
| `payment_transactions` | Payment records |
| `stock_adjustments` | Stock adjustments |
| `audit_logs` | Audit trail |
| `settings` | System settings |

## Nigerian Market Features

- **Currency:** NGN (₦)
- **Payment Methods:** Cash, Bank Transfer, Paystack, POS
- **Phone Format:** +234 support
- **NUBAN:** 11-digit bank account validation
- **States:** All Nigerian states dropdown

## User Roles & Permissions

| Role | Permissions |
|------|-------------|
| Admin | All operations |
| Manager | Products, Inventory, Sales, Reports, Supplier management |
| Cashier | Sales, View products/inventory |
| Warehouse | Inventory management only |

## Screenshots

The application includes:
- Login page with form validation
- Dashboard with stats cards and charts
- Products management with CRUD
- Inventory tracking with adjustments
- Sales recording with payment selection
- Supplier management
- Purchase order creation
- Reports generation

## Troubleshooting

### Database Connection Error
Check `.env` credentials match your MySQL credentials.

### 404 on Pages
Ensure PHP server is running: `php -S localhost:8080`

### Login Fails
Run: `php create_admin.php` to reset admin user.

## License

Proprietary - Tech Application