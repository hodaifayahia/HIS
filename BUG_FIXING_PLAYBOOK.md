# Bug Fixing Playbook for Hospital Information System (HIS)

## Overview
This playbook provides a comprehensive guide for debugging and fixing issues in our Hospital Information System built with Laravel 10 (PHP 8+), Vue 3, and MySQL.

---

## 1. Bug Triage & Tracking

### Bug Severity Classification
- **Critical**: System down, data loss, security breach, patient safety impact
- **High**: Major functionality broken, affects multiple users
- **Medium**: Minor functionality issues, workarounds available
- **Low**: Cosmetic issues, enhancement requests

### Bug Report Template

```markdown
## Bug Report

**Environment:**
- Laravel Version: 11.x
- PHP Version: 8.x
- Vue Version: 3.x
- MySQL Version: 8.x
- Browser: [Chrome/Firefox/Safari] Version X.X
- OS: [Windows/macOS/Linux]

**Bug Description:**
Brief description of the issue

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Screenshots/Logs:**
Attach relevant screenshots or log entries

**Additional Context:**
Any other relevant information

**Priority:** [Critical/High/Medium/Low]
**Reproducibility:** [Always/Sometimes/Rarely]
**Affected Users:** [All/Specific Role/Single User]
```

### GitHub Issues Workflow

#### Labels Structure
```bash
# Severity
severity/critical
severity/high
severity/medium
severity/low

# Type
type/bug
type/enhancement
type/documentation

# Status
status/triage
status/in-progress
status/testing
status/blocked

# Component
component/backend
component/frontend
component/database
component/api
```

#### Issue Templates
Create `.github/ISSUE_TEMPLATE/bug_report.md`:

```markdown
---
name: Bug report
about: Create a report to help us improve
title: '[BUG] '
labels: 'type/bug, status/triage'
assignees: ''
---

**Environment Information**
- Laravel Version: 
- PHP Version: 
- Vue Version: 
- Browser: 
- OS: 

**Bug Description**
A clear and concise description of what the bug is.

**Steps to Reproduce**
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected Behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Additional Context**
Add any other context about the problem here.
```

---

## 2. Backend Debugging (Laravel)

### Tools & Setup

#### Xdebug Configuration
Add to `php.ini`:
```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

#### VS Code Debug Configuration
Create `.vscode/launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}",
                "/home/administrator/www/HIS": "${workspaceFolder}"
            }
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 9003,
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes"
            ]
        }
    ]
}
```

#### PhpStorm Configuration
1. File → Settings → Languages & Frameworks → PHP → Debug
2. Set Xdebug port to 9003
3. Enable "Can accept external connections"
4. Create server mapping for your project

### Development Tools Installation

#### Laravel Debugbar (Development Only)
```bash
composer require barryvdh/laravel-debugbar --dev
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

Add to `.env`:
```env
DEBUGBAR_ENABLED=true
TELESCOPE_ENABLED=true
```

#### Laravel Telescope (Development Only)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Add to `AppServiceProvider.php`:
```php
public function register()
{
    if ($this->app->environment('local')) {
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }
}
```

### Logging & Exception Handling

#### Custom Exception Handler
Update `app/Exceptions/Handler.php`:
```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            Log::error('Application Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => app()->environment('local') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }

        return parent::render($request, $e);
    }
}
```

#### Custom Exception Classes
Create `app/Exceptions/HISException.php`:
```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class HISException extends Exception
{
    protected $context = [];

    public function __construct($message = "", $code = 0, Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function report()
    {
        Log::error('HIS Exception: ' . $this->getMessage(), [
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'user_id' => auth()->id(),
        ]);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
                'code' => $this->getCode()
            ], 400);
        }

        return redirect()->back()->withErrors(['error' => $this->getMessage()]);
    }
}
```

#### Structured Logging
Create `app/Services/LoggingService.php`:
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LoggingService
{
    public static function logUserAction($action, $data = [])
    {
        Log::info('User Action', [
            'action' => $action,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public static function logDatabaseOperation($operation, $model, $data = [])
    {
        Log::info('Database Operation', [
            'operation' => $operation,
            'model' => $model,
            'user_id' => auth()->id(),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public static function logAPICall($endpoint, $method, $response_code, $data = [])
    {
        Log::info('API Call', [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $response_code,
            'user_id' => auth()->id(),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

### Debugging Techniques

#### Using dump() and dd()
```php
// In controllers or services
public function someMethod($data)
{
    dump($data); // Shows data without stopping execution
    dd($data);   // Shows data and stops execution
    
    // For complex objects
    dump($user->toArray());
    dump($user->getAttributes());
}
```

#### Artisan Tinker for Data Inspection
```bash
php artisan tinker

# Test models and relationships
>>> $user = App\Models\User::find(1)
>>> $user->appointments
>>> $user->appointments()->where('status', 'active')->get()

# Test services
>>> $service = new App\Services\Reception\ReceptionService()
>>> $service->someMethod($parameters)
```

### Test-Driven Debugging

#### PHPUnit Test for Bug Reproduction
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FicheNavette;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageCreationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_single_fiche_navette_item_for_package()
    {
        // Arrange
        $user = User::factory()->create();
        $package = Package::factory()->create();
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/fiche-navette-items', [
                'package_id' => $package->id,
                'fiche_navette_id' => 1
            ]);
        
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseCount('fiche_navette_items', 1);
        $this->assertDatabaseHas('fiche_navette_items', [
            'package_id' => $package->id,
            'prestation_id' => null
        ]);
    }
}
```

#### Query Debugging
Add to `AppServiceProvider.php` boot method:
```php
public function boot()
{
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            Log::info('Database Query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms'
            ]);
        });
    }
}
```

---

## 4. Frontend Debugging (Vue.js)

### Tools & Setup

#### Vue DevTools
1. Install the Vue DevTools browser extension (Chrome/Firefox).
2. Ensure your Vue application is running in development mode. In `resources/js/app.js`, add:
```javascript
if (import.meta.env.DEV) {
    app.config.devtools = true;
}
```
3. Open your browser's developer tools and navigate to the "Vue" tab to inspect components, state, events, and more.

#### Source Maps
1. Enable source maps in your `vite.config.js` for readable stack traces. Ensure `sourcemap` is set to `true` in the build options:
```javascript
export default defineConfig({
    // ...
    build: {
        sourcemap: true,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return id.toString().split('node_modules/')[1].split('/')[0];
                    }
                }
            }
        }
    },
    // ...
});
```

### Logging & Assertions

#### Standardize `console.error()` and `console.warn()`
Use `console.error()` for critical errors and `console.warn()` for non-critical issues. Wrap API calls with `try/catch` blocks to display user-friendly error messages.

```javascript
async function fetchData() {
  try {
    const response = await axios.get('/api/data');
    return response.data;
  } catch (error) {
    console.error('API Error:', error);
    // Display user-friendly error message
    alert('Failed to fetch data. Please try again later.');
    return null;
  }
}
```

#### Jest Unit Tests with API Mocks
Write Jest unit tests to replicate component logic failures and mock API calls using `axios-mock-adapter`.

```javascript
// Example: tests/unit/MyComponent.spec.js
import { mount } from '@vue/test-utils';
import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import MyComponent from '../../resources/js/components/MyComponent.vue';

describe('MyComponent', () => {
  let mock;

  beforeEach(() => {
    mock = new MockAdapter(axios);
  });

  afterEach(() => {
    mock.restore();
  });

  it('displays data after successful API call', async () => {
    mock.onGet('/api/data').reply(200, { message: 'Test Data' });
    const wrapper = mount(MyComponent);
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).toContain('Test Data');
  });

  it('displays error message on API failure', async () => {
    mock.onGet('/api/data').reply(500);
    const wrapper = mount(MyComponent);
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).toContain('Failed to fetch data.');
  });
});
```


---

## 6. Code Analysis & Prevention

### Static Analysis

#### PHPStan
1. Install PHPStan as a dev dependency:
```bash
composer require phpstan/phpstan --dev
```
2. Create a `phpstan.neon` configuration file in the project root:
```yaml
parameters:
    level: 8
    paths:
        - app
        - tests
    excludePaths:
        - app/Http/Kernel.php
        - app/Exceptions/Handler.php
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::(.*?)'
        - '#Access to an undefined property (.*?)'
    bootstrapFiles:
        - vendor/autoload.php
    checkMissingIterableValueType: false
    doctrine:
        objectManagerLoader: null
```
3. Add a script to `package.json` to run PHPStan:
```json
"scripts": {
    "phpstan": "php artisan phpstan"
}
```
4. Run PHPStan:
```bash
npm run phpstan
```

#### ESLint
1. Install ESLint and necessary plugins as dev dependencies:
```bash
npm install eslint eslint-plugin-vue @vue/eslint-config-prettier eslint-config-prettier --save-dev
```
2. Create a `.eslintrc.js` configuration file in the project root:
```javascript
module.exports = {
  root: true,
  env: {
    node: true,
    browser: true,
  },
  extends: [
    'plugin:vue/vue3-recommended',
    'eslint:recommended',
    'plugin:prettier/recommended',
  ],
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
  },
  rules: {
    // Add custom rules here if needed
  },
};
```
3. Add a script to `package.json` to run ESLint:
```json
"scripts": {
    "lint": "eslint . --ext .js,.vue"
}
```
4. Run ESLint:
```bash
npm run lint
```

### Peer Review & Pair Debugging
- **Code Review Checklists**: Focus on edge cases, security vulnerabilities, and proper error handling during code reviews.
- **Pair Debugging Sessions**: Encourage collaborative debugging for complex or recurring issues to leverage collective knowledge.

---

## 7. Automation & CI/CD

### CI Pipeline
Configure GitHub Actions or GitLab CI to run linting, static analysis, and the full test suite on every pull request.

#### Example GitHub Actions Workflow (`.github/workflows/ci.yml`)
```yaml
name: CI

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, pdo_mysql
        ini-values: post_max_size=256M, upload_max_filesize=256M
        coverage: none

    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"

    - name: Install Composer Dependencies
      run: composer install --no-ansi --no-interaction --no-progress --prefer-dist

    - name: Generate Application Key
      run: php artisan key:generate

    - name: Run Migrations
      run: php artisan migrate --force

    - name: Run PHPStan
      run: php artisan phpstan

    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'

    - name: Install NPM Dependencies
      run: npm install

    - name: Run ESLint
      run: npm run lint

    - name: Run Tests
      run: php artisan test

    - name: Run Frontend Tests (if applicable)
      run: npm test
```

### Rollback Strategies
- **Blue/Green Deployments**: Maintain two identical production environments (Blue and Green). Deploy new versions to the inactive environment, test, and then switch traffic. This allows for instant rollback by switching back to the previous environment.
- **Feature Flags**: Use feature flags to enable/disable new features without redeploying code. This allows for quick disabling of problematic features.

---

## 8. Monitoring & Validation

### Post-Fix Validation
- **Automated Smoke Tests**: Implement automated smoke tests using Laravel Dusk or Cypress to verify critical functionalities (e.g., login, patient lookup, appointment flow) after each bug fix deployment.

#### Laravel Dusk Example
```php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /** @test */
    public function user_can_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->press('Login')
                    ->assertPathIs('/home');
        });
    }
}
```

#### Cypress Example
```javascript
// cypress/e2e/login.cy.js
describe('Login Flow', () => {
  it('successfully logs in a user', () => {
    cy.visit('/login');
    cy.get('input[name="email"]').type('test@example.com');
    cy.get('input[name="password"]').type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/home');
  });
});
```

### Metrics & Alerts
- **Sentry/New Relic Integration**: Set up alerts for new exceptions, error rate spikes, and failed health checks in Sentry or New Relic to proactively identify and address issues.

---

## 7. Continuous Improvement

### Retrospectives
- Regularly conduct retrospectives after major bug fixes or incidents to identify root causes, learn from mistakes, and improve processes.

### Documentation Updates
- Keep this playbook and other relevant documentation up-to-date with new tools, processes, and lessons learned.

---

## Conclusion
This Bug Fixing Playbook is a living document. It will evolve as our system and team mature. By adhering to these guidelines, we aim to streamline our bug-fixing process, improve code quality, and ultimately deliver a more stable and reliable Hospital Information System.

---

## 5. Database Issue Resolution (MySQL)

### Query Debugging

#### Slow Query Logging
Add to MySQL configuration:
```ini
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

#### Laravel Query Optimization

##### N+1 Query Detection and Fix
```php
// Problem: N+1 Query
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->name; // This creates N additional queries
}

// Solution: Eager Loading
$users = User::with('profile')->get();
foreach ($users as $user) {
    echo $user->profile->name; // No additional queries
}

// Complex relationships
$appointments = Appointment::with([
    'patient',
    'doctor',
    'ficheNavette.items.prestation',
    'ficheNavette.items.package'
])->get();
```

##### Query Monitoring with Telescope
```php
// Enable query monitoring in local environment
// Visit /telescope/queries to see all executed queries
```

### Migration & Data Fixes

#### Safe Migration Rollback
```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Rollback specific number of migrations
php artisan migrate:rollback --step=3

# Rollback to specific batch
php artisan migrate:rollback --batch=5
```

#### Data Correction Scripts
Create `database/seeders/DataFixSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataFixSeeder extends Seeder
{
    public function run()
    {
        // Fix duplicate fiche navette items
        DB::statement("
            DELETE fn1 FROM fiche_navette_items fn1
            INNER JOIN fiche_navette_items fn2 
            WHERE fn1.id > fn2.id 
            AND fn1.fiche_navette_id = fn2.fiche_navette_id 
            AND fn1.package_id = fn2.package_id 
            AND fn1.package_id IS NOT NULL
        ");

        // Update package pricing
        DB::statement("
            UPDATE fiche_navette_items fni
            JOIN packages p ON fni.package_id = p.id
            SET fni.final_price = p.price
            WHERE fni.package_id IS NOT NULL
        ");
    }
}
```

#### Database Backup Before Fixes
```bash
# Create backup
mysqldump -u username -p his_database > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore if needed
mysql -u username -p his_database < backup_20240101_120000.sql
```

### Performance Optimization

#### Index Analysis
```sql
-- Check missing indexes
EXPLAIN SELECT * FROM appointments WHERE patient_id = 1 AND status = 'active';

-- Add indexes
ALTER TABLE appointments ADD INDEX idx_patient_status (patient_id, status);

-- Check index usage
SHOW INDEX FROM appointments;
```

#### Query Optimization Examples
```php
// Instead of
$activeAppointments = Appointment::where('status', 'active')
    ->where('date', '>=', now())
    ->get();

// Use
$activeAppointments = Appointment::where('status', 'active')
    ->where('date', '>=', now())
    ->select(['id', 'patient_id', 'doctor_id', 'date', 'time'])
    ->get();
```

---

## 4. Frontend Debugging (Vue.js)

### Tools & Setup

#### Vue DevTools Installation
```bash
# For Chrome
# Install from Chrome Web Store: Vue.js devtools

# For Firefox
# Install from Firefox Add-ons: Vue.js devtools
```

#### Vite Configuration for Source Maps
Update `vite.config.js`:
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        sourcemap: true, // Enable source maps
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
```

### Error Handling & Logging

#### Global Error Handler
Create `resources/js/utils/errorHandler.js`:
```javascript
export const errorHandler = {
    install(app) {
        app.config.errorHandler = (error, instance, info) => {
            console.error('Vue Error:', error);
            console.error('Component:', instance);
            console.error('Info:', info);
            
            // Send to logging service
            if (window.logError) {
                window.logError({
                    error: error.message,
                    stack: error.stack,
                    component: instance?.$options.name || 'Unknown',
                    info: info,
                    url: window.location.href,
                    timestamp: new Date().toISOString()
                });
            }
        };
    }
};
```

#### API Error Handling
Create `resources/js/utils/api.js`:
```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    timeout: 10000,
});

api.interceptors.response.use(
    response => response,
    error => {
        console.error('API Error:', error);
        
        if (error.response) {
            // Server responded with error status
            const message = error.response.data.message || 'Server Error';
            showNotification(message, 'error');
        } else if (error.request) {
            // Request made but no response
            showNotification('Network Error', 'error');
        } else {
            // Something else happened
            showNotification('Unexpected Error', 'error');
        }
        
        return Promise.reject(error);
    }
);

function showNotification(message, type) {
    // Implement your notification system
    console.log(`${type.toUpperCase()}: ${message}`);
}

export default api;
```

### Component Testing

#### Jest Configuration
Create `jest.config.js`:
```javascript
module.exports = {
    testEnvironment: 'jsdom',
    moduleFileExtensions: ['js', 'json', 'vue'],
    transform: {
        '^.+\\.vue$': '@vue/vue3-jest',
        '^.+\\.js$': 'babel-jest',
    },
    moduleNameMapping: {
        '^@/(.*)$': '<rootDir>/resources/js/$1',
    },
    testMatch: [
        '<rootDir>/tests/js/**/*.test.js',
    ],
    collectCoverageFrom: [
        'resources/js/**/*.{js,vue}',
        '!resources/js/app.js',
    ],
};
```

#### Component Test Example
Create `tests/js/components/PrestationCard.test.js`:
```javascript
import { mount } from '@vue/test-utils';
import PrestationCard from '@/Components/Caisse/PrestationCard.vue';

describe('PrestationCard', () => {
    it('displays package name and price correctly', () => {
        const item = {
            id: 1,
            package_id: 1,
            package: {
                name: 'Test Package',
                price: 100
            },
            final_price: 100
        };

        const wrapper = mount(PrestationCard, {
            props: {
                item,
                finalPrice: 100
            }
        });

        expect(wrapper.text()).toContain('Test Package');
        expect(wrapper.text()).toContain('100');
    });

    it('displays prestation name when no package', () => {
        const item = {
            id: 1,
            prestation_id: 1,
            prestation: {
                name: 'Test Prestation'
            },
            final_price: 50
        };

        const wrapper = mount(PrestationCard, {
            props: {
                item,
                finalPrice: 50
            }
        });

        expect(wrapper.text()).toContain('Test Prestation');
    });
});
```

### Debugging Techniques

#### Console Debugging
```javascript
// In Vue components
export default {
    methods: {
        debugMethod() {
            console.log('Current data:', this.$data);
            console.log('Props:', this.$props);
            console.log('Computed:', this.computedProperty);
            
            // Inspect reactive data
            console.log('Reactive state:', JSON.stringify(this.reactiveData, null, 2));
        }
    },
    
    watch: {
        someProperty: {
            handler(newVal, oldVal) {
                console.log('Property changed:', { newVal, oldVal });
            },
            deep: true
        }
    }
}
```

#### Vue DevTools Usage
1. Open browser DevTools
2. Go to Vue tab
3. Inspect component tree
4. View component data, props, and events
5. Use time-travel debugging for Vuex/Pinia

---

## 5. Code Analysis & Prevention

### Static Analysis Tools

#### PHPStan Configuration
Create `phpstan.neon`:
```neon
parameters:
    level: 8
    paths:
        - app
        - tests
    excludePaths:
        - app/Console/Kernel.php
        - app/Http/Kernel.php
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'
    checkMissingIterableValueType: false
```

Install and run:
```bash
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse
```

#### ESLint Configuration
Create `.eslintrc.js`:
```javascript
module.exports = {
    env: {
        browser: true,
        es2021: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        '@vue/eslint-config-prettier',
        'plugin:vue/vue3-recommended',
    ],
    parserOptions: {
        ecmaVersion: 12,
        sourceType: 'module',
    },
    plugins: ['vue'],
    rules: {
        'vue/multi-word-component-names': 'off',
        'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    },
};
```

### Code Review Checklist

#### Backend Review Points
- [ ] Proper exception handling with try-catch blocks
- [ ] Input validation and sanitization
- [ ] SQL injection prevention (using Eloquent or prepared statements)
- [ ] Authentication and authorization checks
- [ ] Proper logging for debugging
- [ ] Database transactions for data consistency
- [ ] Memory usage optimization
- [ ] N+1 query prevention

#### Frontend Review Points
- [ ] Component prop validation
- [ ] Error boundary implementation
- [ ] XSS prevention (proper data escaping)
- [ ] API error handling
- [ ] Loading states and user feedback
- [ ] Accessibility compliance
- [ ] Performance optimization (lazy loading, etc.)
- [ ] Memory leak prevention

### Pair Debugging Guidelines

#### Session Structure
1. **Problem Definition** (5 minutes)
   - Clearly define the bug
   - Reproduce the issue
   - Identify affected components

2. **Investigation** (20-30 minutes)
   - Use debugging tools
   - Check logs and error messages
   - Trace code execution

3. **Solution Implementation** (15-20 minutes)
   - Write failing test first
   - Implement fix
   - Verify solution works

4. **Review and Documentation** (5 minutes)
   - Code review
   - Update documentation
   - Add preventive measures

---

## 6. Automation & CI/CD

### GitHub Actions Configuration

Create `.github/workflows/ci.yml`:
```yaml
name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: his_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, dom, fileinfo, mysql
        coverage: xdebug

    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'

    - name: Install PHP dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader

    - name: Install Node dependencies
      run: npm ci

    - name: Copy environment file
      run: cp .env.example .env

    - name: Generate application key
      run: php artisan key:generate

    - name: Run migrations
      run: php artisan migrate --force
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: his_test
        DB_USERNAME: root
        DB_PASSWORD: password

    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse

    - name: Run PHP tests
      run: php artisan test --coverage
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: his_test
        DB_USERNAME: root
        DB_PASSWORD: password

    - name: Build frontend
      run: npm run build

    - name: Run ESLint
      run: npm run lint

    - name: Run JavaScript tests
      run: npm test
```

### Pre-commit Hooks

Create `.pre-commit-config.yaml`:
```yaml
repos:
  - repo: local
    hooks:
      - id: phpstan
        name: PHPStan
        entry: ./vendor/bin/phpstan analyse
        language: system
        files: \.php$
        
      - id: php-cs-fixer
        name: PHP CS Fixer
        entry: ./vendor/bin/php-cs-fixer fix
        language: system
        files: \.php$
        
      - id: eslint
        name: ESLint
        entry: npm run lint:fix
        language: system
        files: \.(js|vue)$
```

### Deployment Scripts

Create `scripts/deploy.sh`:
```bash
#!/bin/bash

set -e

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx

echo "Deployment completed successfully!"
```

### Rollback Strategy

Create `scripts/rollback.sh`:
```bash
#!/bin/bash

PREVIOUS_RELEASE=$1

if [ -z "$PREVIOUS_RELEASE" ]; then
    echo "Usage: ./rollback.sh <previous-release-tag>"
    exit 1
fi

echo "Rolling back to $PREVIOUS_RELEASE..."

# Checkout previous release
git checkout $PREVIOUS_RELEASE

# Install dependencies for that version
composer install --no-dev --optimize-autoloader

# Rollback migrations if needed
# php artisan migrate:rollback --batch=X

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx

echo "Rollback to $PREVIOUS_RELEASE completed!"
```

---

## 7. Monitoring & Validation

### Health Check Endpoints

Create `app/Http/Controllers/HealthController.php`:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkCache()
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $value = Cache::get('health_check');
            return ['status' => $value === 'ok' ? 'ok' : 'error', 'message' => 'Cache check'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache check failed: ' . $e->getMessage()];
        }
    }

    private function checkStorage()
    {
        try {
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'ok');
            $content = file_get_contents($testFile);
            unlink($testFile);
            return ['status' => $content === 'ok' ? 'ok' : 'error', 'message' => 'Storage check'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage check failed: ' . $e->getMessage()];
        }
    }
}
```

### Automated Testing Scripts

Create `tests/Feature/SmokeTest.php`:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function patient_lookup_works()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)
            ->get("/api/patients/search?q={$patient->name}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $patient->name]);
    }

    /** @test */
    public function appointment_creation_works()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/appointments', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'date' => now()->addDay()->format('Y-m-d'),
                'time' => '10:00'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);
    }
}
```

### Error Monitoring Setup

#### Sentry Integration
```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

Add to `.env`:
```env
SENTRY_LARAVEL_DSN=your-sentry-dsn-here
```

Update `config/logging.php`:
```php
'channels' => [
    'sentry' => [
        'driver' => 'sentry',
    ],
],
```

#### Custom Monitoring Service
Create `app/Services/MonitoringService.php`:
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    public static function reportError($error, $context = [])
    {
        Log::error($error, $context);
        
        // Send to external monitoring service
        if (config('services.monitoring.enabled')) {
            Http::post(config('services.monitoring.webhook'), [
                'error' => $error,
                'context' => $context,
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
            ]);
        }
    }

    public static function reportPerformance($metric, $value, $tags = [])
    {
        Log::info("Performance Metric: {$metric}", [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

### Performance Monitoring

Create `app/Http/Middleware/PerformanceMonitoring.php`:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MonitoringService;

class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;

        if ($executionTime > 1000) { // Log slow requests (>1 second)
            MonitoringService::reportPerformance('slow_request', $executionTime, [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'memory_usage' => $memoryUsage,
            ]);
        }

        return $response;
    }
}
```

---

## Quick Reference Commands

### Laravel Artisan Commands
```bash
# Clear all caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Run tests
php artisan test

# Generate key
php artisan key:generate

# Start development server
php artisan serve

# Run tinker
php artisan tinker

# Create controller
php artisan make:controller ControllerName

# Create model
php artisan make:model ModelName -m

# Create test
php artisan make:test TestName
```

### Database Commands
```bash
# Connect to MySQL
mysql -u username -p database_name

# Show tables
SHOW TABLES;

# Describe table structure
DESCRIBE table_name;

# Show running processes
SHOW PROCESSLIST;

# Kill long-running query
KILL query_id;
```

### Git Commands for Bug Fixes
```bash
# Create feature branch
git checkout -b fix/bug-description

# Stage changes
git add .

# Commit with descriptive message
git commit -m "Fix: Description of the bug fix"

# Push branch
git push origin fix/bug-description

# Create pull request (GitHub CLI)
gh pr create --title "Fix: Bug description" --body "Description of changes"
```

---

## Emergency Procedures

### System Down
1. Check server status and logs
2. Verify database connectivity
3. Check disk space and memory usage
4. Review recent deployments
5. Rollback if necessary
6. Notify stakeholders

### Data Corruption
1. Stop all write operations
2. Create immediate backup
3. Identify scope of corruption
4. Restore from clean backup
5. Replay transactions if possible
6. Verify data integrity

### Security Breach
1. Isolate affected systems
2. Change all passwords and API keys
3. Review access logs
4. Patch vulnerabilities
5. Notify relevant authorities
6. Document incident

---

## Case Studies & Lessons Learned

### Case Study 1: Carbon Date Parsing Bug (October 2025)

#### Problem Description
**Bug Type:** Date/Time Handling  
**Severity:** High  
**Component:** Backend (Laravel)  
**File:** `app/Http/Controllers/AppointmentController.php`

**Issue:** Malformed datetime strings were being created when concatenating date strings with Carbon time objects, resulting in strings like `"2025-10-22 2025-10-20 08:00:00"` instead of the expected `"2025-10-22 08:00:00"`.

#### Root Cause Analysis
The bug occurred in two methods:
1. `calculateTotalAvailableTime()` (lines ~930-980)
2. `getDoctorWorkingHours()` (lines ~1050-1100)

**Root Cause:** Direct concatenation of date strings with Carbon objects without proper formatting:
```php
// BUGGY CODE
$startTime = Carbon::parse($dateString.' '.$schedule->start_time);
$endTime = Carbon::parse($dateString.' '.$schedule->end_time);
```

When `$schedule->start_time` was a Carbon instance (due to model casting), it would be converted to string using Carbon's default `__toString()` method, which includes the full date, creating malformed strings.

#### Detection Method
- **Initial Detection:** Manual testing of appointment scheduling functionality
- **Debugging Tools Used:**
  - `php artisan tinker` for reproducing the issue
  - Direct code inspection
  - Laravel Debugbar (would have helped if enabled)

#### Solution Implemented
**Fix:** Explicitly format Carbon instances to time-only strings before concatenation:

```php
// FIXED CODE
$startTimeStr = $schedule->start_time instanceof Carbon 
    ? $schedule->start_time->format('H:i:s') 
    : $schedule->start_time;
$endTimeStr = $schedule->end_time instanceof Carbon 
    ? $schedule->end_time->format('H:i:s') 
    : $schedule->end_time;

$startTime = Carbon::parse($dateString.' '.$startTimeStr);
$endTime = Carbon::parse($dateString.' '.$endTimeStr);
```

#### Testing Strategy
1. **Unit Tests:** Created `DateParsingLogicTest.php` to test the core logic without database dependencies
2. **Feature Tests:** Created `AppointmentDateParsingTest.php` for integration testing
3. **Manual Testing:** Used `php artisan tinker` to verify fixes

#### Prevention Measures
1. **Static Analysis:** PHPStan would have caught this type mismatch
2. **Code Review:** Checklist item added for date/time concatenation patterns
3. **Testing:** Comprehensive test coverage for date/time handling edge cases

#### Lessons Learned
1. **Always format objects before string concatenation** - Never assume object-to-string conversion will produce expected format
2. **Test with different data types** - Models with casts can return different types than expected
3. **Use type checking** - Always check if variables are instances of specific classes before operations
4. **Comprehensive testing** - Create both unit and integration tests for critical functionality
5. **Documentation** - Document expected data types and formats in method docblocks

#### Code Review Checklist Addition
- [ ] Are date/time concatenations properly formatted?
- [ ] Are Carbon instances explicitly formatted before string operations?
- [ ] Are there tests covering different data type scenarios?
- [ ] Are method parameters and return types properly documented?

#### Tools That Would Have Helped
- **PHPStan Level 6+:** Would have detected type mismatches
- **Laravel Debugbar:** Would have shown the malformed SQL queries
- **Xdebug:** Would have allowed step-through debugging to see exact values

#### Related Files Modified
- `app/Http/Controllers/AppointmentController.php` (lines 930-980, 1050-1100)
- `tests/Unit/DateParsingLogicTest.php` (new)
- `tests/Feature/AppointmentDateParsingTest.php` (new)

#### Time to Resolution
- **Detection to Fix:** 2 hours
- **Testing and Validation:** 1 hour
- **Documentation:** 30 minutes
- **Total:** 3.5 hours

---

## Case Study: Specialization Filtering Bug Fix

### Problem Description
**Issue:** Cardiology specialization not showing all available prestations in FicheNavette item selection across Emergency, Reception, and Nursing modules.

**Symptoms:**
- Users could only see prestations that matched their selected specialization
- Cardiology prestations were missing when cardiology specialization was selected
- Issue affected multiple Vue.js components across different modules

### Root Cause Analysis

#### Backend Investigation
1. **Initial Hypothesis:** Backend API filtering prestations by user specialization
2. **Investigation Method:** 
   - Searched codebase for `getPrestationsForFicheByAuthenticatedUser` method
   - Found in `app/Http/Services/Reception/FicheNavetteService.php`
   - Discovered specialization filtering logic in lines 1079-1083

#### Frontend Investigation
1. **Component Analysis:** Found filtering logic in multiple Vue components:
   - `CustomPrestationSelection.vue` (Emergency, Reception, Nursing)
   - `PrestationSelection.vue` (Emergency, Reception, Nursing)
2. **Filtering Logic:** Components were filtering prestations by `specialization_id` in computed properties

### Solution Implementation

#### Backend Fix
```php
// Before (in FicheNavetteService.php)
if ($user->specializations && $user->specializations->isNotEmpty()) {
    $specializationIds = $user->specializations->pluck('id')->toArray();
    $query->whereIn('specialization_id', $specializationIds);
}

// After - Removed specialization filtering
// Users should see all prestations regardless of their specialization
```

#### Frontend Fixes
Updated 6 Vue.js components to remove specialization filtering:

```javascript
// Before (in filteredPrestations computed property)
if (this.selectedSpecializationsFilter && this.selectedSpecializationsFilter.length > 0) {
    filtered = filtered.filter(prestation => 
        this.selectedSpecializationsFilter.includes(prestation.specialization_id)
    );
}

// After - Removed specialization filtering
// Show all prestations regardless of specialization selection
```

### Testing & Validation

#### Verification Script
Created `test_specialization_fix.php` to verify:
- API endpoint accessibility
- Frontend component modifications
- Removal of filtering logic

#### Results
- ✅ All 6 frontend components successfully updated
- ✅ Backend filtering logic removed
- ✅ Specialization filtering comments added for future reference

### Files Modified
1. **Backend:**
   - `app/Http/Services/Reception/FicheNavetteService.php` (lines 1079-1083)

2. **Frontend Components:**
   - `resources/js/Components/Apps/Emergency/components/CustomPrestationSelection.vue`
   - `resources/js/Components/Apps/reception/components/CustomPrestationSelection.vue`
   - `resources/js/Components/Apps/Nursing/components/CustomPrestationSelection.vue`
   - `resources/js/Components/Apps/Emergency/components/PrestationSelection.vue`
   - `resources/js/Components/Apps/reception/components/PrestationSelection.vue`
   - `resources/js/Components/Apps/Nursing/components/PrestationSelection.vue`

### Lessons Learned

#### Technical Insights
1. **Multi-layer Filtering:** Bug existed in both backend API and frontend components
2. **Component Replication:** Same filtering logic was duplicated across multiple modules
3. **Business Logic Clarity:** Need clearer requirements about specialization restrictions

#### Process Improvements
1. **Systematic Search:** Used codebase search to identify all affected components
2. **Consistent Fixes:** Applied same solution pattern across all components
3. **Documentation:** Added comments explaining the removal of filtering logic

#### Prevention Strategies
1. **Centralized Logic:** Consider creating shared composables for common filtering logic
2. **Business Rules Documentation:** Document when specialization filtering should/shouldn't apply
3. **Cross-module Testing:** Test similar functionality across all modules when fixing bugs

### Code Review Checklist Additions
- [ ] Are business rules consistently applied across all modules?
- [ ] Is filtering logic centralized or properly documented if duplicated?
- [ ] Are specialization restrictions clearly defined in requirements?
- [ ] Have similar components in other modules been checked for the same issue?

### Tools That Helped
- **Semantic Search:** Quickly identified all components with similar filtering logic
- **Regex Search:** Found exact patterns of specialization filtering code
- **File Comparison:** Ensured consistent fixes across all affected files

### Time to Resolution
- **Problem Identification:** 30 minutes
- **Root Cause Analysis:** 45 minutes  
- **Backend Fix:** 15 minutes
- **Frontend Fixes:** 60 minutes
- **Testing & Validation:** 30 minutes
- **Documentation:** 20 minutes
- **Total:** 3 hours 20 minutes

### Related Issues to Monitor
- Ensure no other components have similar specialization filtering that shouldn't be there
- Monitor user feedback to confirm all prestations are now accessible
- Consider adding tests to prevent regression of this filtering logic

---

This playbook should be regularly updated as new tools, techniques, and best practices are discovered. Keep it as a living document that evolves with your team's needs and the system's complexity.