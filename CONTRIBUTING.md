# Contributing to TPT Free ERP

Thank you for your interest in contributing to TPT Free ERP! This document provides guidelines and information for contributors.

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Workflow](#development-workflow)
4. [Code Standards](#code-standards)
5. [Pull Request Process](#pull-request-process)
6. [Issue Reporting](#issue-reporting)
7. [Testing](#testing)
8. [Documentation](#documentation)
9. [Security](#security)
10. [License](#license)

## Code of Conduct

### Our Pledge

We as members, contributors, and leaders pledge to make participation in our community a harassment-free experience for everyone, regardless of age, body size, visible or invisible disability, ethnicity, sex characteristics, gender identity and expression, level of experience, education, socio-economic status, nationality, personal appearance, race, caste, color, religion, or sexual identity and orientation.

### Our Standards

Examples of behavior that contributes to a positive environment for our community include:

- Demonstrating empathy and kindness toward other people
- Being respectful of differing opinions, viewpoints, and experiences
- Giving and gracefully accepting constructive feedback
- Accepting responsibility and apologizing to those affected by our mistakes
- Focusing on what is best not only for us as individuals, but for the overall community

Examples of unacceptable behavior include:

- The use of sexualized language or imagery, and sexual attention or advances of any kind
- Trolling, insulting or derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information, such as a physical or email address, without their explicit permission
- Other conduct which could reasonably be considered inappropriate in a professional setting

### Enforcement Responsibilities

Community leaders are responsible for clarifying and enforcing our standards of acceptable behavior and will take appropriate and fair corrective action in response to any behavior that they deem inappropriate, threatening, offensive, or harmful.

Community leaders have the right and responsibility to remove, edit, or reject comments, commits, code, wiki edits, issues, and other contributions that are not aligned to this Code of Conduct, and will communicate reasons for moderation decisions when appropriate.

### Scope

This Code of Conduct applies within all community spaces, and also applies when an individual is officially representing the community in public spaces. Examples of representing our community include using an official email address, posting via an official social media account, or acting as an appointed representative at an online or offline event.

### Enforcement

Instances of abusive, harassing, or otherwise unacceptable behavior may be reported to the community leaders responsible for enforcement at [contact information]. All complaints will be reviewed and investigated promptly and fairly.

All community leaders are obligated to respect the privacy and security of the reporter of any incident.

### Enforcement Guidelines

Community leaders will follow these Community Impact Guidelines in determining the consequences for any action they deem in violation of this Code of Conduct:

#### 1. Correction

**Community Impact**: Use of inappropriate language or other behavior deemed unprofessional or unwelcome in the community.

**Consequence**: A private, written warning from community leaders, providing clarity around the nature of the violation and an explanation of why the behavior was inappropriate. A public apology may be requested.

#### 2. Warning

**Community Impact**: A violation through a single incident or series of actions.

**Consequence**: A warning with consequences for continued behavior. No interaction with the people involved, including unsolicited interaction with those enforcing the Code of Conduct, for a specified period of time. This includes avoiding interactions in community spaces as well as external channels like social media. Violating these terms may lead to a temporary or permanent ban.

#### 3. Temporary Ban

**Community Impact**: A serious violation of community standards, including sustained inappropriate behavior.

**Consequence**: A temporary ban from any sort of interaction or public communication with the community for a specified period of time. No public or private interaction with the people involved, including unsolicited interaction with those enforcing the Code of Conduct, is allowed during this period. Violating these terms may lead to a permanent ban.

#### 4. Permanent Ban

**Community Impact**: Demonstrating a pattern of violation of community standards, including sustained inappropriate behavior, harassment of an individual, or aggression toward or disparagement of classes of individuals.

**Consequence**: A permanent ban from any sort of public interaction within the community.

## Getting Started

### Prerequisites

Before you begin, ensure you have the following installed:

- PHP 8.1 or higher
- PostgreSQL 13 or higher
- Composer
- Node.js 16 or higher (for frontend assets)
- Docker and Docker Compose (recommended for development)

### Development Environment Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/PhillipC05/tpt-free-erp.git
   cd tpt-free-erp
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up the database**
   ```bash
   # Using Docker (recommended)
   docker-compose up -d

   # Or set up PostgreSQL manually
   # Create database and run migrations
   php vendor/bin/phinx migrate
   php vendor/bin/phinx seed:run
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

5. **Install frontend dependencies** (if applicable)
   ```bash
   npm install
   ```

6. **Start the development server**
   ```bash
   php -S localhost:8000 -t public
   ```

### Project Structure

```
tpt-free-erp/
├── api/                    # PHP API controllers
│   ├── controllers/       # API endpoint controllers
│   └── middleware/        # Authentication and authorization middleware
├── config/                # Configuration files
├── core/                  # Core system classes
├── db/                    # Database schemas, migrations, and seeds
├── modules/               # ERP modules
├── public/                # Public web assets
│   ├── assets/           # CSS, JS, images
│   └── index.php         # Main entry point
├── templates/             # Email and other templates
├── tests/                 # Test files
└── docs/                  # Documentation
```

## Development Workflow

### Branching Strategy

We use a Git Flow-inspired branching strategy:

- `main`: Production-ready code
- `develop`: Latest development changes
- `feature/*`: New features
- `bugfix/*`: Bug fixes
- `hotfix/*`: Critical fixes for production
- `release/*`: Release preparation

### Creating a Feature Branch

```bash
# Start from develop branch
git checkout develop
git pull origin develop

# Create and switch to feature branch
git checkout -b feature/your-feature-name

# Make your changes
# ...

# Commit your changes
git add .
git commit -m "feat: add your feature description"

# Push to remote
git push origin feature/your-feature-name
```

### Commit Message Guidelines

We follow conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Testing
- `chore`: Maintenance

Examples:
```
feat(auth): add two-factor authentication
fix(api): resolve user creation validation error
docs(readme): update installation instructions
```

## Code Standards

### PHP Standards

We follow PSR-12 coding standards with some additional rules:

#### File Structure
- Use `<?php` opening tag
- No closing `?>` tag for pure PHP files
- One class per file
- Class names in PascalCase
- Method names in camelCase
- Constants in UPPER_SNAKE_CASE

#### Code Style
```php
<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class UserController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = new Database();
    }

    public function index(): Response
    {
        $users = $this->db->query('SELECT * FROM users');

        return $this->jsonResponse([
            'success' => true,
            'data' => $users
        ]);
    }
}
```

#### JavaScript Standards

We follow Airbnb JavaScript Style Guide with ES6+ features:

```javascript
// Good
const handleSubmit = (event) => {
  event.preventDefault();

  const formData = new FormData(event.target);
  const data = Object.fromEntries(formData);

  api.post('/users', data)
    .then(response => {
      if (response.success) {
        showNotification('User created successfully', 'success');
        resetForm();
      }
    })
    .catch(error => {
      showNotification('Error creating user', 'error');
    });
};

// Bad - Avoid
function handleSubmit(event) {
  event.preventDefault();
  // ... implementation
}
```

### Database Standards

#### Table Naming
- Use lowercase with underscores: `user_profiles`
- Use plural names: `users`, `products`
- Prefix related tables: `finance_accounts`, `inventory_products`

#### Column Naming
- Use lowercase with underscores: `first_name`, `created_at`
- Use descriptive names: `invoice_number`, `payment_status`
- Foreign keys: `user_id`, `product_id`

#### Indexing Strategy
- Primary keys are automatically indexed
- Foreign keys should be indexed
- Frequently queried columns should be indexed
- Consider composite indexes for complex queries

## Pull Request Process

### Before Submitting

1. **Update your branch** with the latest changes from develop:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout your-feature-branch
   git rebase develop
   ```

2. **Run tests** to ensure everything works:
   ```bash
   # Run PHP tests
   ./vendor/bin/phpunit

   # Run JavaScript tests (if applicable)
   npm test

   # Run code quality checks
   ./vendor/bin/phpstan analyse
   ./vendor/bin/phpcs
   ```

3. **Update documentation** if needed

4. **Write clear commit messages**

### Creating a Pull Request

1. **Go to GitHub** and create a new pull request
2. **Select the correct base branch** (usually `develop`)
3. **Write a descriptive title** following conventional commit format
4. **Provide a detailed description** including:
   - What changes were made
   - Why the changes were needed
   - How to test the changes
   - Any breaking changes
   - Screenshots (if UI changes)

### Pull Request Template

```markdown
## Description
Brief description of the changes made

## Type of Change
- [ ] Bug fix (non-breaking change)
- [ ] New feature (non-breaking change)
- [ ] Breaking change
- [ ] Documentation update

## How Has This Been Tested?
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed
- [ ] All existing tests still pass

## Checklist
- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes

## Screenshots (if applicable)
Add screenshots to help explain your changes

## Additional Notes
Any additional information or context
```

### Review Process

1. **Automated checks** will run:
   - Code quality checks (PHPStan, PHPCS)
   - Unit tests
   - Integration tests
   - Security scans

2. **Peer review** by maintainers:
   - Code quality and standards
   - Functionality and logic
   - Security considerations
   - Performance impact
   - Documentation updates

3. **Approval and merge**:
   - At least one maintainer approval required
   - All automated checks must pass
   - Squash and merge with conventional commit message

## Issue Reporting

### Bug Reports

When reporting bugs, please use the bug report template and include:

```markdown
**Describe the Bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected Behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment**
- OS: [e.g., Windows 10]
- Browser: [e.g., Chrome 91]
- PHP Version: [e.g., 8.1]
- Database: [e.g., PostgreSQL 13]

**Additional Context**
Add any other context about the problem here.
```

### Feature Requests

For feature requests, please use the feature request template:

```markdown
**Is your feature request related to a problem? Please describe.**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions or features you've considered.

**Additional context**
Add any other context or screenshots about the feature request here.
```

### Issue Labels

We use the following labels to categorize issues:

- `bug`: Something isn't working
- `enhancement`: New feature or request
- `documentation`: Documentation improvements
- `security`: Security-related issues
- `performance`: Performance-related issues
- `accessibility`: Accessibility-related issues
- `good first issue`: Good for newcomers
- `help wanted`: Extra attention is needed

## Testing

### Unit Testing

We use PHPUnit for PHP unit testing:

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/UserTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Integration Testing

Integration tests verify that different parts of the system work together:

```bash
# Run integration tests
./vendor/bin/phpunit --testsuite integration
```

### End-to-End Testing

We use Cypress for end-to-end testing:

```bash
# Run E2E tests
npx cypress run

# Open Cypress GUI
npx cypress open
```

### Test Coverage

We aim for at least 80% code coverage. Coverage reports are generated automatically in CI/CD.

## Documentation

### Code Documentation

All code should be well-documented:

```php
/**
 * Create a new user account
 *
 * @param array $userData User data including email, password, etc.
 * @return array Created user data
 * @throws ValidationException When user data is invalid
 * @throws DatabaseException When database operation fails
 */
public function createUser(array $userData): array
{
    // Implementation
}
```

### API Documentation

API endpoints should be documented using OpenAPI/Swagger:

```php
/**
 * @OA\Post(
 *     path="/api/users",
 *     summary="Create a new user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/User")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User created successfully"
 *     )
 * )
 */
public function create()
{
    // Implementation
}
```

### User Documentation

User-facing features should have corresponding documentation in the `/docs` directory.

## Security

### Security Considerations

When contributing code, please consider:

1. **Input Validation**: Always validate and sanitize user inputs
2. **SQL Injection**: Use prepared statements or ORM methods
3. **XSS Prevention**: Escape output and use Content Security Policy
4. **CSRF Protection**: Implement CSRF tokens for state-changing operations
5. **Authentication**: Use secure authentication mechanisms
6. **Authorization**: Implement proper access controls
7. **Data Encryption**: Encrypt sensitive data at rest and in transit
8. **Session Security**: Use secure session management
9. **Error Handling**: Don't expose sensitive information in error messages
10. **Dependencies**: Keep dependencies updated and scan for vulnerabilities

### Reporting Security Issues

If you discover a security vulnerability, please:

1. **Do not** create a public issue
2. Email security concerns to [security email]
3. Provide detailed information about the vulnerability
4. Allow time for the issue to be fixed before public disclosure

## License

By contributing to TPT Free ERP, you agree that your contributions will be licensed under the MIT License.

### MIT License Summary

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

Thank you for contributing to TPT Free ERP! Your contributions help make this project better for everyone.
