# TPT Free ERP - Migration Guide

## Overview

This guide provides comprehensive instructions for migrating from the legacy TPT Free ERP codebase to the refactored version. The refactored version introduces significant improvements in architecture, performance, and maintainability while maintaining backward compatibility where possible.

## Table of Contents

1. [Migration Overview](#migration-overview)
2. [Breaking Changes](#breaking-changes)
3. [Component Migration](#component-migration)
4. [Utility Migration](#utility-migration)
5. [Testing Migration](#testing-migration)
6. [Deployment Migration](#deployment-migration)
7. [Rollback Procedures](#rollback-procedures)
8. [Troubleshooting](#troubleshooting)

## Migration Overview

### What Changed

The refactored TPT Free ERP introduces:

- **12 New Utilities**: Enterprise-grade utilities for common functionality
- **12 Refactored Components**: Modern component architecture with improved UX
- **4 Quality Assurance Systems**: Error handling, performance, accessibility, testing
- **4 DevOps Tools**: ESLint, Prettier, Husky, CI/CD pipeline
- **Enterprise Standards**: WCAG 2.1 AA compliance, 80% test coverage, automated deployment

### Migration Timeline

**Recommended Approach:**
1. **Phase 1**: Deploy utilities and development tools (Low risk)
2. **Phase 2**: Migrate non-critical components (Medium risk)
3. **Phase 3**: Migrate critical components (High risk)
4. **Phase 4**: Full production deployment (High risk)

**Timeline Estimate:**
- Development Environment: 1-2 weeks
- Staging Environment: 2-4 weeks
- Production Deployment: 1-2 weeks

### Prerequisites

Before starting migration:

```bash
# Required Node.js version
node --version  # Should be 18.x or higher

# Required npm version
npm --version   # Should be 8.x or higher

# Required PHP version
php --version   # Should be 8.1 or higher

# Required Composer version
composer --version  # Should be 2.x

# Check available disk space
df -h  # Should have at least 2GB free
```

## Breaking Changes

### High Impact Changes

#### 1. Component Architecture Changes

**Before:**
```javascript
class OldComponent {
    constructor(container) {
        this.container = container;
        this.init();
    }

    init() {
        // Manual DOM manipulation
        this.container.innerHTML = '<div>Content</div>';
    }
}
```

**After:**
```javascript
class NewComponent extends BaseComponent {
    constructor(options) {
        super(options);
    }

    async render() {
        // Modern component rendering
        return this.createElement('div', { className: 'content' }, 'Content');
    }
}
```

**Migration Required:** All components must extend `BaseComponent`

#### 2. API Request Changes

**Before:**
```javascript
// Manual fetch calls
fetch('/api/data', {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error(error));
```

**After:**
```javascript
// Centralized API client
const data = await this.apiRequest('/api/data');
console.log(data);
```

**Migration Required:** Replace all manual fetch calls with `this.apiRequest()`

#### 3. Notification System Changes

**Before:**
```javascript
// Direct App method calls
App.showNotification('Success message', 'success');
```

**After:**
```javascript
// Component method calls
this.showNotification('Success message', 'success');
```

**Migration Required:** Replace `App.showNotification()` with `this.showNotification()`

### Medium Impact Changes

#### 4. Table Rendering Changes

**Before:**
```javascript
// Manual table creation
const table = document.createElement('table');
// Manual row creation and population
```

**After:**
```javascript
// TableRenderer usage
const tableRenderer = new TableRenderer(container, data, config);
tableRenderer.render();
```

**Migration Required:** Replace manual table creation with `TableRenderer`

#### 5. Modal Management Changes

**Before:**
```javascript
// Direct modal manipulation
const modal = document.getElementById('myModal');
modal.style.display = 'block';
```

**After:**
```javascript
// ModalManager usage
const modal = await this.showModal('My Modal', content, options);
```

**Migration Required:** Replace direct modal manipulation with `ModalManager`

### Low Impact Changes

#### 6. Form Validation Changes

**Before:**
```javascript
// Manual validation
if (!input.value) {
    showError('Field required');
}
```

**After:**
```javascript
// FormValidator usage
const validator = new FormValidator();
const result = validator.validate('required', input.value);
if (!result.isValid) {
    this.showError(result.message);
}
```

**Migration Required:** Optional, but recommended for consistency

## Component Migration

### Step-by-Step Migration Process

#### Step 1: Backup Current Component

```bash
# Create backup of current component
cp components/OldComponent.js components/OldComponent.backup.js
```

#### Step 2: Update Component Structure

```javascript
// Before
class OldComponent {
    constructor(container) {
        this.container = container;
        this.init();
    }

    init() {
        // Old initialization code
    }
}

// After
class NewComponent extends BaseComponent {
    constructor(options) {
        super(options);
    }

    async init() {
        // New initialization code
        await super.init();
    }

    async render() {
        // Modern rendering logic
        return this.createElement('div', { className: 'component' }, 'Content');
    }
}
```

#### Step 3: Update Method Bindings

```javascript
// Before
this.handleClick = this.handleClick.bind(this);

// After (automatic with BaseComponent)
get bindMethods() {
    return ['handleClick', 'handleSubmit'];
}
```

#### Step 4: Update API Calls

```javascript
// Before
fetch('/api/data')
    .then(response => response.json())
    .then(data => this.updateUI(data))
    .catch(error => console.error(error));

// After
try {
    const data = await this.apiRequest('/api/data');
    this.updateUI(data);
} catch (error) {
    this.handleError(error);
}
```

#### Step 5: Update Notifications

```javascript
// Before
App.showNotification('Success!', 'success');

// After
this.showNotification('Success!', 'success');
```

#### Step 6: Update Table Rendering

```javascript
// Before
this.renderTable(data);

// After
const tableRenderer = new TableRenderer(
    this.container.querySelector('.table-container'),
    data,
    {
        sortable: true,
        filterable: true,
        paginated: true
    }
);
tableRenderer.render();
```

### Component-Specific Migration Notes

#### HR Component Migration

```javascript
// Additional configuration for HR component
const hrConfig = {
    tableColumns: [
        { key: 'name', label: 'Employee Name', sortable: true },
        { key: 'department', label: 'Department', filterable: true },
        { key: 'salary', label: 'Salary', formatter: 'currency' }
    ],
    bulkActions: ['export', 'delete', 'update']
};
```

#### Inventory Component Migration

```javascript
// Inventory-specific table configuration
const inventoryConfig = {
    columns: [
        { key: 'sku', label: 'SKU', sortable: true },
        { key: 'name', label: 'Product Name', filterable: true },
        { key: 'quantity', label: 'Stock Level', type: 'number' },
        { key: 'location', label: 'Warehouse Location' }
    ],
    exportFormats: ['csv', 'excel', 'pdf']
};
```

## Utility Migration

### New Utility Integration

#### 1. BaseComponent Integration

```javascript
// Add to main app.js
import BaseComponent from './utils/baseComponent.js';

// Make globally available
window.BaseComponent = BaseComponent;
```

#### 2. ApiClient Integration

```javascript
// Add to main app.js
import ApiClient from './utils/apiClient.js';

// Initialize with configuration
const apiClient = new ApiClient({
    baseURL: '/api',
    timeout: 10000,
    retries: 3
});

// Make globally available
window.ApiClient = ApiClient;
window.apiClient = apiClient;
```

#### 3. TableRenderer Integration

```javascript
// Add to main app.js
import TableRenderer from './utils/tableRenderer.js';

// Make globally available
window.TableRenderer = TableRenderer;
```

#### 4. ModalManager Integration

```javascript
// Add to main app.js
import ModalManager from './utils/modalManager.js';

// Initialize modal manager
const modalManager = new ModalManager();

// Make globally available
window.ModalManager = ModalManager;
window.modalManager = modalManager;
```

### Additional Utilities Setup

#### FormValidator Setup

```javascript
import FormValidator from './utils/formValidator.js';

const validator = new FormValidator({
    customRules: {
        strongPassword: (value) => {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(value);
        }
    }
});

window.FormValidator = FormValidator;
window.formValidator = validator;
```

#### DataFormatter Setup

```javascript
import DataFormatter from './utils/dataFormatter.js';

const formatter = new DataFormatter({
    locale: 'en-US',
    currency: 'USD',
    timezone: 'America/New_York'
});

window.DataFormatter = DataFormatter;
window.dataFormatter = formatter;
```

#### StorageManager Setup

```javascript
import StorageManager from './utils/storageManager.js';

const storage = new StorageManager({
    prefix: 'tpt_erp_',
    encrypt: true,
    compress: true
});

window.StorageManager = StorageManager;
window.storageManager = storage;
```

#### EventManager Setup

```javascript
import EventManager from './utils/eventManager.js';

const eventManager = new EventManager({
    enableLogging: true,
    wildcardEvents: true
});

window.EventManager = EventManager;
window.eventManager = eventManager;
```

## Testing Migration

### Unit Testing Setup

#### 1. Install Testing Dependencies

```bash
npm install --save-dev jest @testing-library/dom @testing-library/jest-dom
```

#### 2. Configure Jest

```javascript
// jest.config.js
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/test-setup.js'],
  testMatch: [
    '<rootDir>/tests/**/*.test.js',
    '<rootDir>/public/assets/js/**/*.test.js'
  ],
  collectCoverageFrom: [
    'public/assets/js/**/*.js',
    '!public/assets/js/**/*.test.js'
  ],
  coverageDirectory: 'coverage',
  coverageThreshold: {
    global: {
      branches: 70,
      functions: 80,
      lines: 80,
      statements: 80
    }
  }
};
```

#### 3. Create Test Setup File

```javascript
// test-setup.js
import '@testing-library/jest-dom';

// Mock utilities
global.BaseComponent = class MockBaseComponent {
  constructor(options) {
    this.options = options;
    this.container = document.createElement('div');
  }

  async render() {
    return this.container;
  }

  showNotification(message, type) {
    console.log(`Notification: ${type} - ${message}`);
  }
};

// Mock API client
global.apiClient = {
  request: jest.fn().mockResolvedValue({ data: 'mocked' })
};
```

### Integration Testing Setup

#### 1. Component Integration Tests

```javascript
// tests/components/HR.test.js
import { render, screen, fireEvent, waitFor } from '@testing-library/dom';
import HR from '../../public/assets/js/components/HR.js';

describe('HR Component Integration', () => {
  let container;
  let hr;

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
  });

  test('renders employee table', async () => {
    hr = new HR({ container });
    await hr.render();

    expect(screen.getByText('Employee Management')).toBeInTheDocument();
    expect(screen.getByRole('table')).toBeInTheDocument();
  });

  test('loads employee data', async () => {
    hr = new HR({ container });
    await hr.render();

    // Mock API response
    global.apiClient.request.mockResolvedValueOnce({
      employees: [
        { id: 1, name: 'John Doe', department: 'Engineering' }
      ]
    });

    await hr.loadEmployees();

    await waitFor(() => {
      expect(screen.getByText('John Doe')).toBeInTheDocument();
    });
  });
});
```

### E2E Testing Setup

#### 1. Install Cypress or Playwright

```bash
# For Cypress
npm install --save-dev cypress

# For Playwright
npm install --save-dev @playwright/test
```

#### 2. Create E2E Test

```javascript
// cypress/integration/hr.spec.js
describe('HR Management E2E', () => {
  beforeEach(() => {
    cy.visit('/hr');
  });

  it('displays employee list', () => {
    cy.contains('Employee Management').should('be.visible');
    cy.get('[data-testid="employee-table"]').should('be.visible');
  });

  it('can add new employee', () => {
    cy.get('[data-testid="add-employee-btn"]').click();
    cy.get('[data-testid="employee-name"]').type('Jane Smith');
    cy.get('[data-testid="employee-department"]').select('Engineering');
    cy.get('[data-testid="save-employee"]').click();

    cy.contains('Jane Smith').should('be.visible');
  });

  it('can search employees', () => {
    cy.get('[data-testid="search-input"]').type('John');
    cy.get('[data-testid="employee-table"]').should('contain', 'John Doe');
  });
});
```

## Deployment Migration

### Development Environment Setup

#### 1. Install Development Dependencies

```bash
# Install ESLint and plugins
npm install --save-dev eslint @typescript-eslint/parser @typescript-eslint/eslint-plugin \
  eslint-plugin-react eslint-plugin-react-hooks eslint-plugin-jsx-a11y \
  eslint-plugin-import eslint-plugin-security eslint-plugin-jsdoc \
  eslint-plugin-prefer-arrow eslint-plugin-no-unsanitized

# Install Prettier
npm install --save-dev prettier

# Install Husky for git hooks
npm install --save-dev husky

# Install testing framework
npm install --save-dev jest @testing-library/dom @testing-library/jest-dom
```

#### 2. Setup Git Hooks

```bash
# Initialize Husky
npx husky install

# Create pre-commit hook
npx husky add .husky/pre-commit "npm run pre-commit"

# Create commit-msg hook
npx husky add .husky/commit-msg "npm run commit-msg"
```

#### 3. Update package.json Scripts

```json
{
  "scripts": {
    "dev": "npm run build && npm run serve",
    "build": "webpack --mode production",
    "serve": "php -S localhost:8000",
    "lint": "eslint public/assets/js/**/*.js",
    "lint:fix": "eslint public/assets/js/**/*.js --fix",
    "format": "prettier --write public/assets/js/**/*.js",
    "format:check": "prettier --check public/assets/js/**/*.js",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "docs": "jsdoc -c docs/jsdoc-config.json",
    "pre-commit": "npm run lint && npm run format:check && npm run test",
    "commit-msg": "echo 'Commit message validated'"
  }
}
```

### Production Environment Setup

#### 1. Server Requirements

```bash
# Check PHP extensions
php -m | grep -E "(pdo|pdo_mysql|mbstring|xml|curl)"

# Check Node.js availability
node --version
npm --version

# Check available memory
free -h

# Check disk space
df -h /var/www
```

#### 2. Deployment Configuration

```bash
# Create deployment directory
sudo mkdir -p /var/www/tpt-erp

# Set proper permissions
sudo chown -R www-data:www-data /var/www/tpt-erp
sudo chmod -R 755 /var/www/tpt-erp

# Configure Nginx (example)
sudo tee /etc/nginx/sites-available/tpt-erp << EOF
server {
    listen 80;
    server_name tpt-erp.example.com;
    root /var/www/tpt-erp/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/tpt-erp /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

#### 3. SSL Configuration

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d tpt-erp.example.com

# Configure automatic renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Rollback Procedures

### Emergency Rollback

#### 1. Immediate Rollback Script

```bash
#!/bin/bash
# rollback.sh

echo "Starting emergency rollback..."

# Stop application
sudo systemctl stop nginx
sudo systemctl stop php8.1-fpm

# Backup current state
BACKUP_DIR="/var/www/backups/$(date +%Y%m%d_%H%M%S)"
sudo mkdir -p $BACKUP_DIR
sudo cp -r /var/www/tpt-erp $BACKUP_DIR/

# Restore from last known good state
if [ -d "/var/www/tpt-erp-backup" ]; then
    sudo rm -rf /var/www/tpt-erp
    sudo cp -r /var/www/tpt-erp-backup /var/www/tpt-erp
    echo "Restored from backup"
else
    echo "No backup found!"
    exit 1
fi

# Restart services
sudo systemctl start php8.1-fpm
sudo systemctl start nginx

echo "Rollback completed"
```

#### 2. Database Rollback

```bash
# Rollback database migrations
php phinx rollback -e production -t 1

# Or rollback to specific migration
php phinx rollback -e production -d 20230908000001
```

#### 3. Git-based Rollback

```bash
# Rollback to previous commit
git log --oneline -10
git reset --hard HEAD~1
git push --force-with-lease

# Or rollback to specific tag
git reset --hard v1.0.0
git push --force-with-lease
```

### Gradual Rollback

#### 1. Feature Flag Rollback

```javascript
// Feature flags for gradual rollback
const FEATURES = {
  NEW_COMPONENTS: false,  // Set to false to use old components
  NEW_API_CLIENT: false,  // Set to false to use old API calls
  NEW_TABLES: false       // Set to false to use old table rendering
};

// Conditional loading
if (FEATURES.NEW_COMPONENTS) {
  import('./components/NewHR.js');
} else {
  import('./components/OldHR.js');
}
```

#### 2. Component-Level Rollback

```javascript
// Component registry for easy switching
const COMPONENT_REGISTRY = {
  hr: FEATURES.NEW_COMPONENTS ? NewHR : OldHR,
  inventory: FEATURES.NEW_COMPONENTS ? NewInventory : OldInventory,
  sales: FEATURES.NEW_COMPONENTS ? NewSales : OldSales
};

// Usage
const HRComponent = COMPONENT_REGISTRY.hr;
const hr = new HRComponent(options);
```

## Troubleshooting

### Common Migration Issues

#### 1. Component Not Rendering

**Symptoms:**
- Blank page after migration
- JavaScript errors in console

**Solutions:**
```javascript
// Check if BaseComponent is properly imported
console.log('BaseComponent available:', typeof BaseComponent);

// Verify component structure
class MyComponent extends BaseComponent {
  async render() {
    return this.createElement('div', {}, 'Hello World');
  }
}

// Check for missing dependencies
if (typeof ApiClient === 'undefined') {
  console.error('ApiClient not loaded');
}
```

#### 2. API Calls Failing

**Symptoms:**
- Network errors
- 404 responses
- Authentication issues

**Solutions:**
```javascript
// Verify API client configuration
const apiClient = new ApiClient({
  baseURL: '/api/v1',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Test API connectivity
apiClient.request('/health')
  .then(response => console.log('API connected'))
  .catch(error => console.error('API error:', error));
```

#### 3. Table Not Displaying Data

**Symptoms:**
- Empty table
- Loading spinner stuck
- JavaScript errors

**Solutions:**
```javascript
// Verify TableRenderer configuration
const config = {
  columns: [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', filterable: true }
  ],
  data: [], // Ensure data is array
  sortable: true,
  filterable: true
};

const tableRenderer = new TableRenderer(container, config.data, config);
tableRenderer.render();
```

#### 4. Modal Not Working

**Symptoms:**
- Modal not opening
- Modal not closing
- Focus issues

**Solutions:**
```javascript
// Verify ModalManager setup
const modalManager = new ModalManager();

// Test modal creation
const modal = await modalManager.showModal(
  'Test Modal',
  '<p>This is a test modal</p>',
  {
    size: 'medium',
    closable: true,
    onClose: () => console.log('Modal closed')
  }
);
```

### Performance Issues

#### 1. Slow Page Loads

**Check:**
```javascript
// Verify lazy loading is working
console.log('Lazy elements:', document.querySelectorAll('[data-lazy]').length);

// Check bundle size
if ('performance' in window && 'getEntriesByType' in performance) {
  const resources = performance.getEntriesByType('resource');
  resources.forEach(resource => {
    if (resource.name.includes('.js') && resource.transferSize > 500000) {
      console.warn('Large bundle detected:', resource.name, resource.transferSize);
    }
  });
}
```

#### 2. Memory Leaks

**Check:**
```javascript
// Monitor for detached DOM nodes
const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.type === 'childList') {
      mutation.removedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          console.log('DOM node removed:', node.tagName);
        }
      });
    }
  });
});

observer.observe(document.body, {
  childList: true,
  subtree: true
});
```

### Testing Issues

#### 1. Tests Failing

**Common fixes:**
```javascript
// Mock missing utilities
global.BaseComponent = class {
  constructor(options) {
    this.options = options;
    this.container = document.createElement('div');
  }
  async render() { return this.container; }
  showNotification() {}
};

// Mock API calls
global.apiClient = {
  request: jest.fn().mockResolvedValue({ data: [] })
};
```

#### 2. Coverage Issues

**Improve coverage:**
```javascript
// Add missing test cases
test('handles error states', () => {
  const component = new MyComponent();
  component.handleError(new Error('Test error'));
  expect(component.error).toBeDefined();
});

test('handles loading states', async () => {
  const component = new MyComponent();
  component.setLoading(true);
  expect(component.loading).toBe(true);
});
```

## Support and Resources

### Getting Help

1. **Documentation**: Check the generated API docs at `/docs/api/`
2. **Issues**: Report bugs on GitHub Issues
3. **Discussions**: Use GitHub Discussions for questions
4. **Slack**: Join our development Slack channel

### Useful Commands

```bash
# Run all tests
npm test

# Run linting
npm run lint

# Format code
npm run format

# Generate docs
npm run docs

# Check bundle size
npm run build:analyze

# Run security audit
npm audit

# Check accessibility
npm run accessibility
```

### Key Files to Monitor

- `public/assets/js/utils/` - Utility implementations
- `public/assets/js/components/` - Component implementations
- `docs/` - Documentation files
- `.eslintrc.js` - Linting configuration
- `.prettierrc.js` - Formatting configuration
- `.github/workflows/` - CI/CD configuration

---

## Summary

This migration guide provides comprehensive instructions for successfully migrating to the refactored TPT Free ERP. The migration process is designed to be:

- **Incremental**: Migrate components one at a time
- **Safe**: Comprehensive testing and rollback procedures
- **Well-documented**: Detailed instructions and troubleshooting
- **Supported**: Multiple channels for getting help

**Key Success Factors:**
1. Follow the recommended migration timeline
2. Test thoroughly at each step
3. Have rollback procedures ready
4. Monitor performance and errors
5. Keep team communication open

The refactored TPT Free ERP provides significant improvements in maintainability, performance, and user experience while maintaining backward compatibility where possible.

---

*Last Updated: September 8, 2025*
*Version: 2.0.0*
