# Campus Hub Portal - Testing Framework Documentation

## Overview

The Campus Hub Portal includes a comprehensive testing framework designed to ensure code quality, functionality, and performance across all system components.

## Test Framework Components

### 1. PHP Unit Testing Framework (`TestFramework.php`)
- **Purpose**: Core testing framework for PHP backend components
- **Features**:
  - Assertion methods (assertEquals, assertTrue, assertFalse, etc.)
  - Database testing helpers
  - HTTP/API testing utilities
  - Performance measurement tools
  - Test result reporting

### 2. API Tests (`ApiTests.php`)
- **Purpose**: Comprehensive testing of all REST API endpoints
- **Test Categories**:
  - News API endpoints
  - Events API endpoints  
  - Users API endpoints
  - Search API endpoints
  - Analytics API endpoints
  - Authentication & Security tests
  - Performance tests

### 3. Database Tests (`DatabaseTests.php`)
- **Purpose**: Database operations and data integrity testing
- **Test Categories**:
  - Database connection tests
  - User operations (CRUD, authentication)
  - News operations (creation, retrieval, search)
  - Event operations (date queries, filtering)
  - Search analytics operations
  - Data integrity and foreign key constraints
  - Query performance benchmarks

### 4. Frontend Tests (`frontend_tests.html`)
- **Purpose**: Browser-based testing of user interface components
- **Test Categories**:
  - UI Component Tests (navigation, search, forms)
  - API Integration Tests
  - Performance Tests (page load, resource optimization)
  - Accessibility Tests (alt attributes, form labels, heading structure)
  - User Interaction Tests (click events, form handling)

### 5. Test Runner (`run_tests.php`)
- **Purpose**: Command-line test execution and reporting
- **Features**:
  - Run individual test suites or all tests
  - Environment information display
  - Comprehensive reporting (console, JSON, HTML)
  - Exit codes for CI/CD integration

## Running Tests

### Prerequisites
1. **PHP 7.4+** with PDO extension
2. **MySQL database** with test database configured
3. **Web server** (Apache/Nginx) running locally
4. **Browser** for frontend tests

### Command Line Testing

```bash
# Run all tests
php tests/run_tests.php

# Run specific test suites
php tests/run_tests.php --database
php tests/run_tests.php --api
php tests/run_tests.php --performance
php tests/run_tests.php --security

# Run multiple suites
php tests/run_tests.php --database --api

# Display help
php tests/run_tests.php --help
```

### Individual Test Execution

```bash
# Database tests only
php tests/DatabaseTests.php

# API tests only  
php tests/ApiTests.php
```

### Frontend Testing
1. Open `tests/frontend_tests.html` in a web browser
2. Click "Run All Tests" or run specific test suites
3. View results in the interactive dashboard

## Test Configuration

Edit `tests/test_config.php` to configure:

### Database Settings
```php
'database' => [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'test_database' => 'campus_hub_test',
    'charset' => 'utf8mb4'
]
```

### API Testing Settings
```php
'api' => [
    'base_url' => 'http://localhost/BreyerApps/campus-hub',
    'timeout' => 30,
    'verify_ssl' => false
]
```

### Performance Thresholds
```php
'performance' => [
    'max_response_time' => 2.0,    // seconds
    'max_query_time' => 0.1,       // seconds
    'max_page_load_time' => 5.0,   // seconds
    'max_dom_elements' => 2000
]
```

## Test Data Management

### Automatic Test Data Creation
The framework automatically creates test users and content:

**Test Users:**
- `test_admin` - Administrator account
- `test_student` - Student account  
- `test_faculty` - Faculty account

**Test Content:**
- Sample news articles
- Sample events
- Analytics tracking data

### Cleanup
- Test data is automatically cleaned up after test execution
- Database transactions ensure data isolation
- No permanent changes to production data

## Continuous Integration

### GitHub Actions Configuration
Create `.github/workflows/tests.yml`:

```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: campus_hub_test
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: pdo, pdo_mysql
        
    - name: Run Database Tests
      run: php tests/run_tests.php --database
      
    - name: Run API Tests  
      run: php tests/run_tests.php --api
      
    - name: Run Security Tests
      run: php tests/run_tests.php --security
```

### Local Development Workflow

1. **Before committing code:**
   ```bash
   php tests/run_tests.php
   ```

2. **After making database changes:**
   ```bash
   php tests/run_tests.php --database
   ```

3. **After API modifications:**
   ```bash
   php tests/run_tests.php --api
   ```

4. **Performance regression testing:**
   ```bash
   php tests/run_tests.php --performance
   ```

## Test Reports

### Console Output
Real-time test execution progress with:
- ✓ Passed tests (green)
- ✗ Failed tests (red)  
- Performance metrics
- Summary statistics

### JSON Reports
Saved to `tests/results/test_results_TIMESTAMP.json`:
```json
{
    "summary": {
        "total_tests": 45,
        "passed": 43,
        "failed": 2,
        "success_rate": 95.6,
        "duration": 12.34
    },
    "suites": {...}
}
```

### HTML Reports
Interactive HTML reports with:
- Visual test results
- Performance charts
- Error details
- Test execution timeline

## Performance Benchmarks

### Expected Performance Metrics
- **Database Queries**: < 100ms average
- **API Responses**: < 2 seconds
- **Page Load Time**: < 5 seconds
- **Memory Usage**: < 50% of limit

### Performance Test Categories
1. **Database Performance**
   - Connection time
   - Query execution time
   - Large dataset handling

2. **API Performance**  
   - Response times
   - Concurrent request handling
   - Rate limiting effectiveness

3. **Frontend Performance**
   - Page load metrics
   - Resource optimization
   - DOM element count

## Security Testing

### Security Test Categories
1. **SQL Injection Protection**
   - Malicious query prevention
   - Parameter sanitization
   - Error message filtering

2. **XSS Protection**
   - Script injection prevention
   - Output escaping validation
   - Content Security Policy

3. **Authentication Security**
   - Unauthorized access prevention
   - Session management
   - Password security

4. **File Upload Security**
   - Dangerous file type rejection
   - File size limitations
   - Path traversal prevention

## Troubleshooting

### Common Issues

**Database Connection Errors:**
```bash
# Check database configuration
mysql -u root -p -e "SHOW DATABASES;"

# Verify test database exists
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS campus_hub_test;"
```

**API Test Failures:**
```bash
# Verify web server is running
curl http://localhost/BreyerApps/campus-hub/php/api/news.php

# Check file permissions
chmod -R 755 php/
```

**Performance Test Failures:**
- Increase memory limit in php.ini
- Optimize database indexes
- Check server resource usage

### Debug Mode
Enable debug logging in test configuration:
```php
'debug' => true
```

## Best Practices

### Test Development
1. **Write tests first** (TDD approach)
2. **Keep tests isolated** and independent
3. **Use descriptive test names**
4. **Test edge cases** and error conditions
5. **Mock external dependencies**

### Test Maintenance
1. **Update tests** when functionality changes
2. **Remove obsolete tests**
3. **Keep test data minimal** and relevant
4. **Regular performance baseline updates**

### CI/CD Integration
1. **Run tests on every commit**
2. **Block merges** if tests fail
3. **Monitor test execution time**
4. **Archive test reports**

## Contributing

When adding new features:

1. **Write corresponding tests**
2. **Ensure all existing tests pass**
3. **Add performance benchmarks** for new functionality
4. **Update test documentation**

## Support

For testing framework issues:
1. Check test configuration
2. Verify environment setup
3. Review test logs
4. Consult documentation
5. Contact development team

---

**Test Framework Version**: 1.0.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+
