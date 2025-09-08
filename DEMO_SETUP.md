# TPT Free ERP - Demo Environment Setup Guide

**Version:** 1.0
**Date:** September 8, 2025
**Prepared by:** Development Team

This comprehensive guide provides step-by-step instructions for setting up a fully functional demo environment for TPT Free ERP, complete with sample data across all modules.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [Database Configuration](#database-configuration)
4. [Demo Data Creation](#demo-data-creation)
5. [Module-Specific Demo Data](#module-specific-demo-data)
6. [User Accounts Setup](#user-accounts-setup)
7. [Configuration Files](#configuration-files)
8. [Testing the Demo](#testing-the-demo)
9. [Maintenance](#maintenance)

---

## Prerequisites

### System Requirements
- **Operating System**: Linux, macOS, or Windows 10/11
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 8.1 or higher
- **Database**: PostgreSQL 13+ or MySQL 8.0+
- **Node.js**: Version 16+ (for frontend assets)
- **Composer**: Latest version
- **Git**: Latest version

### Hardware Requirements
- **RAM**: Minimum 4GB, Recommended 8GB+
- **Storage**: Minimum 10GB free space
- **CPU**: 2+ cores recommended

### Network Requirements
- **Domain/Host**: demo.tpt-free-erp.com (or localhost for local setup)
- **SSL Certificate**: Valid SSL certificate for HTTPS
- **Firewall**: Open ports 80, 443, and database port

---

## Environment Setup

### 1. Clone Repository
```bash
# Clone the repository
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp

# Switch to demo branch (if available)
git checkout demo-setup
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if applicable)
npm install --production
npm run build
```

### 3. Set Up Web Server

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName demo.tpt-free-erp.com
    DocumentRoot /var/www/tpt-free-erp/public

    <Directory /var/www/tpt-free-erp/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tpt-erp-demo_error.log
    CustomLog ${APACHE_LOG_DIR}/tpt-erp-demo_access.log combined

    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Frame-Options DENY
        Header always set X-Content-Type-Options nosniff
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    </IfModule>
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name demo.tpt-free-erp.com;
    root /var/www/tpt-free-erp/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "upload_max_filesize=50M \n post_max_size=50M";
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /(config|db|logs|backups)/ {
        deny all;
        return 404;
    }
}
```

### 4. SSL Certificate Setup
```bash
# Using Let's Encrypt (recommended)
certbot --apache -d demo.tpt-free-erp.com

# Or using self-signed certificate for testing
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/demo.key \
    -out /etc/ssl/certs/demo.crt
```

---

## Database Configuration

### 1. Create Database
```sql
-- PostgreSQL
CREATE DATABASE tpt_erp_demo;
CREATE USER tpt_demo_user WITH ENCRYPTED PASSWORD 'secure_password_2025';
GRANT ALL PRIVILEGES ON DATABASE tpt_erp_demo TO tpt_demo_user;

-- MySQL
CREATE DATABASE tpt_erp_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tpt_demo_user'@'localhost' IDENTIFIED BY 'secure_password_2025';
GRANT ALL PRIVILEGES ON tpt_erp_demo.* TO 'tpt_demo_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Run Migrations
```bash
# Run database migrations
php vendor/bin/phinx migrate -e demo

# Seed initial data
php vendor/bin/phinx seed:run -e demo
```

### 3. Demo Data Setup
```bash
# Run demo data seeder
php scripts/setup_demo_data.php

# Or manually run SQL files
psql -U tpt_demo_user -d tpt_erp_demo -f db/demo/demo_data.sql
```

---

## Demo Data Creation

### Core Company Setup
```php
// scripts/setup_demo_data.php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

class DemoDataSetup {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function setup() {
        $this->createDemoCompany();
        $this->createDemoUsers();
        $this->createDemoModules();
        $this->createModuleSpecificData();
        $this->createDemoSettings();
    }

    private function createDemoCompany() {
        $this->db->insert('companies', [
            'name' => 'TPT Free ERP Demo Company',
            'domain' => 'demo.tpt-free-erp.com',
            'industry' => 'Technology',
            'size' => '50-200',
            'timezone' => 'Pacific/Auckland',
            'currency' => 'USD',
            'fiscal_year_start' => '01-01',
            'created_at' => date('Y-m-d H:i:s'),
            'is_demo' => true
        ]);

        return $this->db->lastInsertId();
    }

    private function createDemoUsers() {
        $users = [
            [
                'email' => 'admin@demo.tpt-free-erp.com',
                'password' => password_hash('DemoPass2025!', PASSWORD_ARGON2ID),
                'first_name' => 'Demo',
                'last_name' => 'Administrator',
                'role' => 'admin',
                'is_demo' => true
            ],
            [
                'email' => 'manager@demo.tpt-free-erp.com',
                'password' => password_hash('DemoPass2025!', PASSWORD_ARGON2ID),
                'first_name' => 'Demo',
                'last_name' => 'Manager',
                'role' => 'manager',
                'is_demo' => true
            ],
            [
                'email' => 'user@demo.tpt-free-erp.com',
                'password' => password_hash('DemoPass2025!', PASSWORD_ARGON2ID),
                'first_name' => 'Demo',
                'last_name' => 'User',
                'role' => 'user',
                'is_demo' => true
            ]
        ];

        foreach ($users as $user) {
            $this->db->insert('users', $user);
        }
    }

    private function createDemoModules() {
        $modules = [
            'inventory', 'sales', 'hr', 'procurement', 'manufacturing',
            'reporting', 'project_management', 'quality_management',
            'finance', 'collaboration', 'ai_connectors', 'webhooks'
        ];

        foreach ($modules as $module) {
            $this->db->insert('modules', [
                'name' => $module,
                'display_name' => ucwords(str_replace('_', ' ', $module)),
                'is_active' => true,
                'is_demo' => true,
                'demo_data_available' => true
            ]);
        }
    }
}
```

### Sample Data Generation
```php
// Generate realistic sample data
class SampleDataGenerator {
    public function generateProducts($count = 100) {
        $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports'];
        $products = [];

        for ($i = 1; $i <= $count; $i++) {
            $products[] = [
                'sku' => 'DEMO-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => $this->generateProductName(),
                'description' => $this->generateProductDescription(),
                'category' => $categories[array_rand($categories)],
                'price' => rand(10, 1000),
                'cost' => rand(5, 800),
                'stock_quantity' => rand(0, 500),
                'min_stock_level' => rand(5, 50),
                'is_active' => true,
                'is_demo' => true
            ];
        }

        return $products;
    }

    public function generateCustomers($count = 50) {
        $customers = [];

        for ($i = 1; $i <= $count; $i++) {
            $customers[] = [
                'customer_number' => 'CUST-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'company_name' => $this->generateCompanyName(),
                'contact_person' => $this->generatePersonName(),
                'email' => "customer{$i}@demo.tpt-free-erp.com",
                'phone' => $this->generatePhoneNumber(),
                'address' => $this->generateAddress(),
                'credit_limit' => rand(1000, 50000),
                'payment_terms' => 'Net 30',
                'is_active' => true,
                'is_demo' => true
            ];
        }

        return $customers;
    }

    private function generateProductName() {
        $adjectives = ['Premium', 'Deluxe', 'Professional', 'Advanced', 'Smart'];
        $nouns = ['Widget', 'Gadget', 'Tool', 'Device', 'System'];

        return $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
    }

    private function generateCompanyName() {
        $prefixes = ['Tech', 'Global', 'Smart', 'Advanced', 'Premier'];
        $suffixes = ['Solutions', 'Systems', 'Technologies', 'Corp', 'Inc'];

        return $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)];
    }

    private function generatePersonName() {
        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Lisa'];
        $lastNames = ['Smith', 'Johnson', 'Brown', 'Williams', 'Jones', 'Garcia'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function generatePhoneNumber() {
        return '+1-' . rand(200, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999);
    }

    private function generateAddress() {
        $streets = ['Main St', 'Oak Ave', 'Maple Dr', 'Cedar Ln', 'Pine Rd'];
        return rand(100, 9999) . ' ' . $streets[array_rand($streets)];
    }
}
```

---

## Module-Specific Demo Data

### Finance Module Demo Data
```sql
-- Sample chart of accounts
INSERT INTO financial_accounts (company_id, account_code, account_name, account_type, account_category, is_demo) VALUES
(1, '1000', 'Cash and Cash Equivalents', 'asset', 'current_asset', true),
(1, '1100', 'Accounts Receivable', 'asset', 'current_asset', true),
(1, '1200', 'Inventory', 'asset', 'current_asset', true),
(1, '2000', 'Accounts Payable', 'liability', 'current_liability', true),
(1, '3000', 'Common Stock', 'equity', 'equity', true),
(1, '4000', 'Sales Revenue', 'revenue', 'revenue', true),
(1, '5000', 'Cost of Goods Sold', 'expense', 'cost_of_goods_sold', true),
(1, '6000', 'Operating Expenses', 'expense', 'operating_expense', true);

-- Sample transactions
INSERT INTO financial_transactions (company_id, account_id, transaction_description, transaction_type, amount, transaction_date, is_demo) VALUES
(1, 1, 'Initial Cash Deposit', 'cash_inflow', 100000.00, '2025-01-01', true),
(1, 4, 'Office Supplies Purchase', 'cash_outflow', 2500.00, '2025-01-15', true),
(1, 2, 'Customer Payment', 'cash_inflow', 15000.00, '2025-01-20', true);
```

### Inventory Module Demo Data
```sql
-- Sample products
INSERT INTO products (company_id, sku, name, description, category, price, cost, stock_quantity, min_stock_level, is_demo) VALUES
(1, 'DEMO-000001', 'Wireless Mouse', 'Ergonomic wireless mouse with USB receiver', 'Electronics', 29.99, 15.00, 150, 20, true),
(1, 'DEMO-000002', 'Mechanical Keyboard', 'RGB backlit mechanical keyboard', 'Electronics', 129.99, 75.00, 75, 10, true),
(1, 'DEMO-000003', 'Office Chair', 'Adjustable ergonomic office chair', 'Furniture', 299.99, 150.00, 25, 5, true);

-- Sample suppliers
INSERT INTO suppliers (company_id, name, contact_person, email, phone, address, is_demo) VALUES
(1, 'Tech Supplies Inc', 'John Smith', 'john@techsupplies.com', '+1-555-0101', '123 Tech Street, Silicon Valley, CA', true),
(1, 'Office Furniture Co', 'Sarah Johnson', 'sarah@officefurn.com', '+1-555-0102', '456 Office Blvd, Business City, NY', true);
```

### Sales Module Demo Data
```sql
-- Sample customers
INSERT INTO customers (company_id, customer_number, company_name, contact_person, email, phone, address, credit_limit, is_demo) VALUES
(1, 'CUST-000001', 'ABC Corporation', 'Mike Wilson', 'mike@abc.com', '+1-555-0201', '789 Business Ave, Commerce City, TX', 50000.00, true),
(1, 'CUST-000002', 'XYZ Industries', 'Lisa Brown', 'lisa@xyz.com', '+1-555-0202', '321 Industry Rd, Manufacturing Town, IL', 75000.00, true);

-- Sample sales orders
INSERT INTO sales_orders (company_id, order_number, customer_id, order_date, status, total_amount, is_demo) VALUES
(1, 'SO-2025-0001', 1, '2025-01-10', 'completed', 2999.99, true),
(1, 'SO-2025-0002', 2, '2025-01-15', 'processing', 1499.99, true);
```

### HR Module Demo Data
```sql
-- Sample employees
INSERT INTO employees (company_id, employee_id, first_name, last_name, email, department, position, hire_date, salary, is_demo) VALUES
(1, 'EMP-000001', 'Alice', 'Johnson', 'alice@demo.com', 'Engineering', 'Software Developer', '2024-01-15', 75000.00, true),
(1, 'EMP-000002', 'Bob', 'Smith', 'bob@demo.com', 'Sales', 'Sales Representative', '2024-02-01', 55000.00, true),
(1, 'EMP-000003', 'Carol', 'Davis', 'carol@demo.com', 'HR', 'HR Manager', '2023-11-01', 65000.00, true);

-- Sample attendance records
INSERT INTO attendance_records (company_id, employee_id, date, check_in_time, check_out_time, hours_worked, is_demo) VALUES
(1, 1, '2025-01-08', '09:00:00', '17:30:00', 8.5, true),
(1, 2, '2025-01-08', '08:45:00', '17:15:00', 8.5, true);
```

### Project Management Demo Data
```sql
-- Sample projects
INSERT INTO projects (company_id, project_name, description, start_date, end_date, budget, status, is_demo) VALUES
(1, 'ERP Implementation Phase 1', 'Initial ERP system implementation', '2025-01-01', '2025-03-31', 150000.00, 'in_progress', true),
(1, 'Website Redesign', 'Complete company website overhaul', '2025-02-01', '2025-04-30', 75000.00, 'planning', true);

-- Sample tasks
INSERT INTO tasks (company_id, project_id, task_name, description, assigned_to, due_date, priority, status, is_demo) VALUES
(1, 1, 'Requirements Gathering', 'Collect and document system requirements', 1, '2025-01-31', 'high', 'completed', true),
(1, 1, 'System Design', 'Design the ERP system architecture', 1, '2025-02-15', 'high', 'in_progress', true),
(1, 2, 'Wireframe Creation', 'Create website wireframes and mockups', 2, '2025-02-28', 'medium', 'pending', true);
```

---

## User Accounts Setup

### Demo User Roles
```php
class DemoUserSetup {
    public function createDemoUsers() {
        $demoUsers = [
            [
                'username' => 'demo_admin',
                'email' => 'admin@demo.tpt-free-erp.com',
                'password' => 'DemoAdmin2025!',
                'role' => 'administrator',
                'permissions' => ['*'], // All permissions
                'profile' => [
                    'first_name' => 'Demo',
                    'last_name' => 'Administrator',
                    'department' => 'IT',
                    'title' => 'System Administrator'
                ]
            ],
            [
                'username' => 'demo_manager',
                'email' => 'manager@demo.tpt-free-erp.com',
                'password' => 'DemoManager2025!',
                'role' => 'manager',
                'permissions' => [
                    'inventory.view', 'inventory.edit',
                    'sales.view', 'sales.edit',
                    'hr.view', 'hr.edit',
                    'finance.view', 'reporting.view'
                ],
                'profile' => [
                    'first_name' => 'Demo',
                    'last_name' => 'Manager',
                    'department' => 'Operations',
                    'title' => 'Operations Manager'
                ]
            ],
            [
                'username' => 'demo_sales',
                'email' => 'sales@demo.tpt-free-erp.com',
                'password' => 'DemoSales2025!',
                'role' => 'sales',
                'permissions' => [
                    'sales.view', 'sales.edit',
                    'customers.view', 'customers.edit',
                    'inventory.view'
                ],
                'profile' => [
                    'first_name' => 'Demo',
                    'last_name' => 'Sales',
                    'department' => 'Sales',
                    'title' => 'Sales Representative'
                ]
            ],
            [
                'username' => 'demo_hr',
                'email' => 'hr@demo.tpt-free-erp.com',
                'password' => 'DemoHR2025!',
                'role' => 'hr',
                'permissions' => [
                    'hr.view', 'hr.edit',
                    'employees.view', 'employees.edit'
                ],
                'profile' => [
                    'first_name' => 'Demo',
                    'last_name' => 'HR',
                    'department' => 'Human Resources',
                    'title' => 'HR Specialist'
                ]
            ],
            [
                'username' => 'demo_accounting',
                'email' => 'accounting@demo.tpt-free-erp.com',
                'password' => 'DemoAccounting2025!',
                'role' => 'accounting',
                'permissions' => [
                    'finance.view', 'finance.edit',
                    'reporting.view'
                ],
                'profile' => [
                    'first_name' => 'Demo',
                    'last_name' => 'Accounting',
                    'department' => 'Finance',
                    'title' => 'Accountant'
                ]
            ]
        ];

        foreach ($demoUsers as $userData) {
            $this->createDemoUser($userData);
        }
    }

    private function createDemoUser($userData) {
        // Hash password
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_ARGON2ID);

        // Remove plain password
        unset($userData['password']);

        // Create user record
        $userId = $this->db->insert('users', array_merge($userData, [
            'is_demo' => true,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ]));

        // Assign permissions
        $this->assignUserPermissions($userId, $userData['permissions']);

        // Create user profile
        $this->createUserProfile($userId, $userData['profile']);

        return $userId;
    }

    private function assignUserPermissions($userId, $permissions) {
        foreach ($permissions as $permission) {
            $this->db->insert('user_permissions', [
                'user_id' => $userId,
                'permission' => $permission,
                'is_demo' => true
            ]);
        }
    }

    private function createUserProfile($userId, $profile) {
        $this->db->insert('user_profiles', array_merge($profile, [
            'user_id' => $userId,
            'is_demo' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]));
    }
}
```

---

## Configuration Files

### Demo Environment Configuration
```php
// config/demo.php
return [
    'app' => [
        'name' => 'TPT Free ERP Demo',
        'url' => 'https://demo.tpt-free-erp.com',
        'environment' => 'demo',
        'debug' => false,
        'timezone' => 'Pacific/Auckland',
        'locale' => 'en_NZ'
    ],

    'database' => [
        'host' => 'localhost',
        'port' => 5432,
        'database' => 'tpt_erp_demo',
        'username' => 'tpt_demo_user',
        'password' => 'secure_password_2025',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci'
    ],

    'cache' => [
        'driver' => 'redis',
        'host' => 'localhost',
        'port' => 6379,
        'password' => null,
        'database' => 1
    ],

    'session' => [
        'driver' => 'redis',
        'lifetime' => 7200, // 2 hours
        'secure' => true,
        'httponly' => true,
        'samesite' => 'strict'
    ],

    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.demo.tpt-free-erp.com',
        'port' => 587,
        'username' => 'noreply@demo.tpt-free-erp.com',
        'password' => 'demo_smtp_password',
        'encryption' => 'tls',
        'from_address' => 'noreply@demo.tpt-free-erp.com',
        'from_name' => 'TPT Free ERP Demo'
    ],

    'demo' => [
        'reset_interval' => '24 hours', // Reset demo data daily
        'max_users' => 100,
        'data_retention' => '30 days',
        'analytics_enabled' => true,
        'feedback_enabled' => true,
        'watermark_enabled' => true
    ]
];
```

### Demo-Specific Settings
```php
// Demo settings and restrictions
class DemoSettings {
    public function applyDemoRestrictions() {
        // Limit certain operations in demo mode
        $this->limitDataExport();
        $this->limitBulkOperations();
        $this->addDemoWatermarks();
        $this->enableDemoNotifications();
    }

    private function limitDataExport() {
        // Limit export to 1000 records max
        add_filter('export_limit', function() {
            return 1000;
        });
    }

    private function limitBulkOperations() {
        // Limit bulk operations to 100 items max
        add_filter('bulk_operation_limit', function() {
            return 100;
        });
    }

    private function addDemoWatermarks() {
        // Add demo watermarks to reports and exports
        add_filter('report_output', function($content) {
            return $content . "\n\n--- DEMO VERSION - FOR EVALUATION ONLY ---";
        });
    }

    private function enableDemoNotifications() {
        // Show demo-specific notifications
        add_action('user_login', function($user) {
            if ($user->is_demo) {
                Notification::send($user->id, 'Welcome to TPT Free ERP Demo!', 'info');
            }
        });
    }
}
```

---

## Testing the Demo

### Automated Demo Tests
```php
class DemoTests {
    public function runDemoTests() {
        $this->testUserLogin();
        $this->testModuleAccess();
        $this->testDataIntegrity();
        $this->testPerformance();
        $this->testSecurity();
    }

    private function testUserLogin() {
        $users = ['admin@demo.tpt-free-erp.com', 'manager@demo.tpt-free-erp.com'];

        foreach ($users as $email) {
            $result = $this->attemptLogin($email, 'DemoPass2025!');
            assert($result['success'] === true, "Login failed for {$email}");
        }

        echo "✓ User login tests passed\n";
    }

    private function testModuleAccess() {
        $modules = ['inventory', 'sales', 'hr', 'finance'];

        foreach ($modules as $module) {
            $result = $this->testModuleEndpoint($module);
            assert($result['accessible'] === true, "Module {$module} not accessible");
        }

        echo "✓ Module access tests passed\n";
    }

    private function testDataIntegrity() {
        // Test referential integrity
        $this->testForeignKeys();

        // Test data consistency
        $this->testDataConsistency();

        // Test demo data completeness
        $this->testDemoDataCompleteness();

        echo "✓ Data integrity tests passed\n";
    }

    private function testPerformance() {
        // Test page load times
        $this->testPageLoadTimes();

        // Test API response times
        $this->testAPIResponseTimes();

        // Test database query performance
        $this->testDatabasePerformance();

        echo "✓ Performance tests passed\n";
    }

    private function testSecurity() {
        // Test authentication
        $this->testAuthentication();

        // Test authorization
        $this->testAuthorization();

        // Test input validation
        $this->testInputValidation();

        echo "✓ Security tests passed\n";
    }
}
```

### Manual Testing Checklist
```markdown
## Manual Demo Testing Checklist

### User Interface Testing
- [ ] Login page loads correctly
- [ ] Dashboard displays properly
- [ ] Navigation menu works
- [ ] Responsive design functions on mobile
- [ ] Forms submit correctly
- [ ] Data tables sort and filter
- [ ] Charts and graphs display
- [ ] Print functionality works

### Module Testing
- [ ] Inventory: Add/edit/delete products
- [ ] Sales: Create orders, manage customers
- [ ] HR: Employee management, attendance
- [ ] Finance: View reports, enter transactions
- [ ] Projects: Create tasks, assign resources
- [ ] Quality: Run inspections, create CAPAs
