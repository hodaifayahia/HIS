# Bug Fixing Playbook for Hospital Information System (HIS)

## Overview
This comprehensive playbook provides standardized procedures for identifying, debugging, and resolving bugs in our Hospital Information System built with Laravel 10 (PHP 8+), Vue 3, and MySQL.

## Table of Contents
1. [Bug Triage & Tracking](#1-bug-triage--tracking)
2. [Backend Debugging (Laravel)](#2-backend-debugging-laravel)
3. [Database Issue Resolution (MySQL)](#3-database-issue-resolution-mysql)
4. [Frontend Debugging (Vue.js)](#4-frontend-debugging-vuejs)
5. [Code Analysis & Prevention](#5-code-analysis--prevention)
6. [Automation & CI/CD](#6-automation--cicd)
7. [Monitoring & Validation](#7-monitoring--validation)

---

## 1. Bug Triage & Tracking

### Bug Severity Classification
- **Critical**: System down, data loss, security breach
- **High**: Major functionality broken, affects multiple users
- **Medium**: Minor functionality issues, workarounds available
- **Low**: Cosmetic issues, enhancement requests

### Bug Report Template

```markdown
## Bug Report

**Environment:**
- Laravel Version: 10.x
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
Attach relevant screenshots or error logs

**Additional Context:**
Any other relevant information

**Priority:** [Critical/High/Medium/Low]
**Reproducibility:** [Always/Sometimes/Rarely]
```

### GitHub Issues Workflow

```bash
# Create issue with labels
gh issue create --title "Bug: Patient search returns incorrect results" \
  --body-file bug-report.md \
  --label "bug,high-priority,backend"

# Assign to developer
gh issue edit 123 --add-assignee developer-username

# Link to pull request
gh pr create --title "Fix: Patient search query optimization" \
  --body "Fixes #123"
```

---

## 2. Backend Debugging (Laravel)

### Tools & Setup

#### Xdebug Configuration

**VS Code launch.json:**
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
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

**php.ini configuration:**
```ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

#### Laravel Debugbar Setup

```bash
# Install Laravel Debugbar (dev only)
composer require barryvdh/laravel-debugbar --dev

# Publish config
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

**config/debugbar.php:**
```php
'enabled' => env('DEBUGBAR_ENABLED', false),
```

**.env (local/dev only):**
```
DEBUGBAR_ENABLED=true
```

#### Laravel Telescope Setup

```bash
# Install Telescope
composer require laravel/telescope --dev

# Install Telescope
php artisan telescope:install

# Migrate
php artisan migrate

# Publish assets
php artisan telescope:publish
```

### Logging & Exception Handling

#### Standardized Logging

```php
// In controllers
use Illuminate\Support\Facades\Log;

public function store(Request $request)
{
    try {
        // Business logic
        $patient = Patient::create($request->validated());
        
        Log::info('Patient created successfully', [
            'patient_id' => $patient->id,
            'user_id' => auth()->id(),
            'ip' => $request->ip()
        ]);
        
        return response()->json($patient, 201);
    } catch (\Exception $e) {
        Log::error('Failed to create patient', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);
        
        return response()->json(['error' => 'Failed to create patient'], 500);
    }
}
```

#### Custom Exception Classes

```php
// app/Exceptions/PatientNotFoundException.php
<?php

namespace App\Exceptions;

use Exception;

class PatientNotFoundException extends Exception
{
    public function __construct($patientId)
    {
        parent::__construct("Patient with ID {$patientId} not found");
    }

    public function report()
    {
        Log::warning('Patient not found', [
            'patient_id' => $this->getMessage(),
            'user_id' => auth()->id()
        ]);
    }

    public function render($request)
    {
        return response()->json([
            'error' => 'Patient not found'
        ], 404);
    }
}
```

#### Global Exception Handler

```php
// app/Exceptions/Handler.php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        // Log all exceptions
        Log::error('Unhandled exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id()
        ]);

        return parent::render($request, $e);
    }
}
```

### Reproduce & Fix Workflow

#### Test-Driven Debugging

```php
// tests/Feature/PatientSearchTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatientSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_should_find_patient_by_partial_name()
    {
        // Arrange
        $patient = Patient::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        // Act
        $response = $this->getJson('/api/patients/search?q=Joh');

        // Assert
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['id' => $patient->id]);
    }
}
```

#### Debugging Commands

```bash
# Run failing test
php artisan test --filter="it_should_find_patient_by_partial_name"

# Use tinker for quick debugging
php artisan tinker
>>> Patient::where('first_name', 'like', '%Joh%')->get()

# Debug with dump and die
dd($query->toSql(), $query->getBindings());

# Use dump for non-blocking debug
dump($variable);
```

---

## 3. Database Issue Resolution (MySQL)

### Query Debugging

#### Capture Slow Queries

```php
// In AppServiceProvider boot method
use Illuminate\Support\Facades\DB;

public function boot()
{
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            if ($query->time > 1000) { // Log queries > 1 second
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time
                ]);
            }
        });
    }
}
```

#### N+1 Query Resolution

**Before (N+1 Problem):**
```php
// This will execute 1 + N queries
$patients = Patient::all();
foreach ($patients as $patient) {
    echo $patient->appointments->count(); // N queries
}
```

**After (Eager Loading):**
```php
// This will execute only 2 queries
$patients = Patient::with('appointments')->get();
foreach ($patients as $patient) {
    echo $patient->appointments->count(); // No additional queries
}
```

### Migration & Data Fixes

#### Safe Migration Rollback

```bash
# Check migration status
php artisan migrate:status

# Rollback last batch
php artisan migrate:rollback

# Rollback specific number of batches
php artisan migrate:rollback --step=3

# Reset and re-run migrations (CAUTION: Data loss)
php artisan migrate:fresh --seed
```

#### Data Correction Scripts

```php
// database/seeders/FixPatientDataSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;

class FixPatientDataSeeder extends Seeder
{
    public function run()
    {
        // Fix null phone numbers
        Patient::whereNull('phone')
            ->update(['phone' => 'N/A']);

        // Normalize phone format
        Patient::chunk(100, function ($patients) {
            foreach ($patients as $patient) {
                $patient->update([
                    'phone' => preg_replace('/[^0-9]/', '', $patient->phone)
                ]);
            }
        });
    }
}
```

```bash
# Run data fix
php artisan db:seed --class=FixPatientDataSeeder
```

#### Bulk Data Operations

```sql
-- SQL script for bulk corrections
UPDATE patients 
SET phone = REGEXP_REPLACE(phone, '[^0-9]', '') 
WHERE phone IS NOT NULL;

-- Add index for performance
CREATE INDEX idx_patients_phone ON patients(phone);
```

---

## 4. Frontend Debugging (Vue.js)

### Tools & Setup

#### Vue DevTools Installation

```bash
# Install Vue DevTools browser extension
# Chrome: https://chrome.google.com/webstore/detail/vuejs-devtools/
# Firefox: https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/
```

#### Vite Configuration for Source Maps

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    build: {
        sourcemap: true, // Enable source maps
    },
});
```

### Logging & Error Handling

#### Standardized Frontend Logging

```javascript
// resources/js/utils/logger.js
class Logger {
    static error(message, context = {}) {
        console.error(`[ERROR] ${message}`, context);
        
        // Send to backend logging service
        if (window.app?.config?.env === 'production') {
            this.sendToBackend('error', message, context);
        }
    }

    static warn(message, context = {}) {
        console.warn(`[WARN] ${message}`, context);
    }

    static info(message, context = {}) {
        console.info(`[INFO] ${message}`, context);
    }

    static sendToBackend(level, message, context) {
        fetch('/api/frontend-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                level,
                message,
                context,
                url: window.location.href,
                userAgent: navigator.userAgent
            })
        }).catch(err => console.error('Failed to send log to backend:', err));
    }
}

export default Logger;
```

#### API Error Handling

```javascript
// resources/js/services/api.js
import axios from 'axios';
import Logger from '@/utils/logger';

const api = axios.create({
    baseURL: '/api',
    timeout: 10000,
});

// Request interceptor
api.interceptors.request.use(
    config => {
        Logger.info(`API Request: ${config.method?.toUpperCase()} ${config.url}`, {
            params: config.params,
            data: config.data
        });
        return config;
    },
    error => {
        Logger.error('API Request Error', error);
        return Promise.reject(error);
    }
);

// Response interceptor
api.interceptors.response.use(
    response => {
        Logger.info(`API Response: ${response.status} ${response.config.url}`);
        return response;
    },
    error => {
        const message = error.response?.data?.message || error.message;
        Logger.error('API Response Error', {
            status: error.response?.status,
            message,
            url: error.config?.url
        });

        // Show user-friendly error message
        if (error.response?.status >= 500) {
            window.app?.$toast?.error('Server error. Please try again later.');
        } else if (error.response?.status === 422) {
            window.app?.$toast?.error('Please check your input and try again.');
        }

        return Promise.reject(error);
    }
);

export default api;
```

### Component Testing

#### Jest Unit Tests

```javascript
// tests/js/components/PatientSearch.test.js
import { mount } from '@vue/test-utils';
import PatientSearch from '@/components/PatientSearch.vue';
import axios from 'axios';

// Mock axios
jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

describe('PatientSearch', () => {
    beforeEach(() => {
        mockedAxios.get.mockClear();
    });

    it('should search patients when query is entered', async () => {
        const mockPatients = [
            { id: 1, name: 'John Doe' },
            { id: 2, name: 'Jane Doe' }
        ];

        mockedAxios.get.mockResolvedValue({
            data: { data: mockPatients }
        });

        const wrapper = mount(PatientSearch);
        
        // Enter search query
        await wrapper.find('input[type="text"]').setValue('Doe');
        await wrapper.find('form').trigger('submit');

        // Wait for async operations
        await wrapper.vm.$nextTick();

        expect(mockedAxios.get).toHaveBeenCalledWith('/api/patients/search', {
            params: { q: 'Doe' }
        });

        expect(wrapper.vm.patients).toEqual(mockPatients);
    });

    it('should handle API errors gracefully', async () => {
        mockedAxios.get.mockRejectedValue(new Error('Network Error'));

        const wrapper = mount(PatientSearch);
        
        await wrapper.find('input[type="text"]').setValue('Doe');
        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.error).toBeTruthy();
        expect(wrapper.find('.error-message').exists()).toBe(true);
    });
});
```

---

## 5. Code Analysis & Prevention

### Static Analysis

#### PHPStan Configuration

```bash
# Install PHPStan
composer require --dev phpstan/phpstan
composer require --dev phpstan/phpstan-laravel
```

**phpstan.neon:**
```neon
includes:
    - ./vendor/phpstan/phpstan-laravel/extension.neon

parameters:
    paths:
        - app
        - tests
    level: 8
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'
    checkMissingIterableValueType: false
```

```bash
# Run PHPStan analysis
./vendor/bin/phpstan analyse
```

#### ESLint Configuration

```bash
# Install ESLint
npm install --save-dev eslint @vue/eslint-config-typescript
```

**.eslintrc.js:**
```javascript
module.exports = {
    env: {
        browser: true,
        es2021: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        '@vue/typescript/recommended',
        'plugin:vue/vue3-recommended',
    ],
    parserOptions: {
        ecmaVersion: 2021,
        sourceType: 'module',
    },
    rules: {
        'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'vue/no-unused-vars': 'error',
        '@typescript-eslint/no-unused-vars': 'error',
    },
};
```

### Code Review Checklist

#### Backend (Laravel) Checklist
- [ ] Input validation using Form Requests
- [ ] Proper error handling with try-catch blocks
- [ ] Database queries optimized (no N+1 problems)
- [ ] Authorization checks implemented
- [ ] Logging added for important operations
- [ ] Unit/Feature tests written
- [ ] Security vulnerabilities checked (SQL injection, XSS)

#### Frontend (Vue.js) Checklist
- [ ] Component props properly typed
- [ ] Error handling for API calls
- [ ] Loading states implemented
- [ ] User feedback for actions
- [ ] Accessibility attributes added
- [ ] Component tests written
- [ ] Performance optimizations (v-memo, computed properties)

---

## 6. Automation & CI/CD

### GitHub Actions Configuration

**.github/workflows/tests.yml:**
```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  laravel-tests:
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

    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache

    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse

    - name: Execute tests (Unit and Feature tests)
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: his_test
        DB_USERNAME: root
        DB_PASSWORD: password
      run: php artisan test

  frontend-tests:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'

    - name: Install dependencies
      run: npm ci

    - name: Run ESLint
      run: npm run lint

    - name: Run tests
      run: npm run test

    - name: Build assets
      run: npm run build
```

### Rollback Strategies

#### Feature Flags Implementation

```php
// config/features.php
return [
    'patient_search_v2' => env('FEATURE_PATIENT_SEARCH_V2', false),
    'new_appointment_flow' => env('FEATURE_NEW_APPOINTMENT_FLOW', false),
];
```

```php
// In controller
public function search(Request $request)
{
    if (config('features.patient_search_v2')) {
        return $this->searchV2($request);
    }
    
    return $this->searchV1($request);
}
```

#### Blue/Green Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

BLUE_DIR="/var/www/his-blue"
GREEN_DIR="/var/www/his-green"
CURRENT_LINK="/var/www/his-current"

# Determine current and next environments
if [ -L "$CURRENT_LINK" ]; then
    CURRENT=$(readlink "$CURRENT_LINK")
    if [ "$CURRENT" = "$BLUE_DIR" ]; then
        NEXT_DIR="$GREEN_DIR"
        NEXT_COLOR="green"
    else
        NEXT_DIR="$BLUE_DIR"
        NEXT_COLOR="blue"
    fi
else
    NEXT_DIR="$BLUE_DIR"
    NEXT_COLOR="blue"
fi

echo "Deploying to $NEXT_COLOR environment: $NEXT_DIR"

# Deploy to next environment
cd "$NEXT_DIR"
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Health check
if curl -f http://localhost:8080/health-check; then
    echo "Health check passed. Switching traffic..."
    ln -sfn "$NEXT_DIR" "$CURRENT_LINK"
    sudo systemctl reload nginx
    echo "Deployment successful!"
else
    echo "Health check failed. Deployment aborted."
    exit 1
fi
```

---

## 7. Monitoring & Validation

### Post-Fix Validation

#### Laravel Dusk Smoke Tests

```php
// tests/Browser/SmokeTest.php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SmokeTest extends DuskTestCase
{
    /** @test */
    public function user_can_login_and_access_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'admin@hospital.com')
                    ->type('password', 'password')
                    ->press('Login')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Welcome to HIS Dashboard');
        });
    }

    /** @test */
    public function patient_search_functionality_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/patients')
                    ->type('search', 'John')
                    ->press('Search')
                    ->waitFor('.patient-results')
                    ->assertSee('Search Results');
        });
    }

    /** @test */
    public function appointment_booking_flow_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/appointments/create')
                    ->select('patient_id', '1')
                    ->select('doctor_id', '1')
                    ->type('appointment_date', '2024-12-31')
                    ->press('Book Appointment')
                    ->assertSee('Appointment booked successfully');
        });
    }
}
```

```bash
# Run smoke tests
php artisan dusk --filter=SmokeTest
```

### Metrics & Alerts

#### Sentry Configuration

```bash
# Install Sentry
composer require sentry/sentry-laravel
```

**config/sentry.php:**
```php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('APP_ENV'),
    'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
];
```

#### Health Check Endpoint

```php
// routes/web.php
Route::get('/health-check', function () {
    $checks = [
        'database' => false,
        'cache' => false,
        'storage' => false,
    ];

    try {
        // Database check
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (Exception $e) {
        Log::error('Health check: Database connection failed', ['error' => $e->getMessage()]);
    }

    try {
        // Cache check
        Cache::put('health_check', 'ok', 10);
        $checks['cache'] = Cache::get('health_check') === 'ok';
    } catch (Exception $e) {
        Log::error('Health check: Cache failed', ['error' => $e->getMessage()]);
    }

    try {
        // Storage check
        Storage::put('health_check.txt', 'ok');
        $checks['storage'] = Storage::exists('health_check.txt');
        Storage::delete('health_check.txt');
    } catch (Exception $e) {
        Log::error('Health check: Storage failed', ['error' => $e->getMessage()]);
    }

    $allHealthy = array_reduce($checks, fn($carry, $check) => $carry && $check, true);

    return response()->json([
        'status' => $allHealthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toISOString()
    ], $allHealthy ? 200 : 503);
});
```

#### Monitoring Script

```bash
#!/bin/bash
# monitor.sh

HEALTH_URL="https://his.hospital.com/health-check"
SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"

check_health() {
    response=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL")
    
    if [ "$response" != "200" ]; then
        message="🚨 HIS Health Check Failed - HTTP $response"
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"$message\"}" \
            "$SLACK_WEBHOOK"
    fi
}

# Run health check
check_health

# Add to crontab: */5 * * * * /path/to/monitor.sh
```

---

## Quick Reference Commands

### Laravel Debugging
```bash
# Clear all caches
php artisan optimize:clear

# Run specific test
php artisan test --filter="test_method_name"

# Database operations
php artisan migrate:fresh --seed
php artisan tinker

# Queue debugging
php artisan queue:work --verbose
php artisan queue:failed
```

### Frontend Debugging
```bash
# Build with source maps
npm run dev

# Run tests with coverage
npm run test -- --coverage

# Lint and fix
npm run lint -- --fix
```

### Database Debugging
```bash
# Show slow query log
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"
mysql -u root -p -e "SET GLOBAL long_query_time = 1;"

# Analyze table
mysql -u root -p his -e "ANALYZE TABLE patients;"
```

---

## Emergency Procedures

### System Down
1. Check health endpoint: `curl https://his.hospital.com/health-check`
2. Check server resources: `htop`, `df -h`
3. Check application logs: `tail -f storage/logs/laravel.log`
4. Check web server logs: `tail -f /var/log/nginx/error.log`
5. Rollback if recent deployment: `git checkout previous-stable-tag`

### Data Corruption
1. Stop application: `php artisan down`
2. Backup current database: `mysqldump his > backup_$(date +%Y%m%d_%H%M%S).sql`
3. Restore from latest backup: `mysql his < backup_file.sql`
4. Run data integrity checks: `php artisan app:verify-data-integrity`
5. Bring application back up: `php artisan up`

---

This playbook should be regularly updated as new tools, processes, and lessons learned are incorporated into the development workflow.