# Bug Fixing Playbook for Hospital Information System (HIS)

## Overview
This playbook provides a comprehensive guide for streamlining the bug-fixing process in our Laravel 10 (PHP 8+), Vue 3, and MySQL-based Hospital Information System.

## 1. Bug Triage & Tracking

### Bug Report Template
```markdown
## Bug Report

**Environment:**
- Environment: [Production/Staging/Development]
- Laravel Version: 10.x
- PHP Version: 8.x
- Vue Version: 3.x
- MySQL Version: 8.x
- Browser: [Chrome/Firefox/Safari] Version X.X

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

**Priority:** [Critical/High/Medium/Low]
**Reproducibility:** [Always/Sometimes/Rarely]
**Affected Users:** [All/Specific Role/Single User]
```

### Severity Labels & Workflow

#### Priority Levels:
- **Critical**: System down, data loss, security breach
- **High**: Major functionality broken, affects many users
- **Medium**: Minor functionality issues, workarounds available
- **Low**: Cosmetic issues, enhancement requests

#### GitHub Issues Workflow:
```bash
# Create issue with labels
gh issue create --title "Payment processing fails for bank transfers" \
  --body-file bug-report.md \
  --label "bug,high-priority,caisse-module"

# Assign to developer
gh issue edit 123 --add-assignee developer-username

# Link to project board
gh issue edit 123 --add-project "HIS Bug Tracking"
```

## 2. Backend Debugging (Laravel)

### Tools & Setup

#### Xdebug Configuration
```ini
# php.ini or .env
XDEBUG_MODE=debug
XDEBUG_START_WITH_REQUEST=yes
XDEBUG_CLIENT_HOST=127.0.0.1
XDEBUG_CLIENT_PORT=9003
```

#### VS Code launch.json
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

#### Laravel Debugbar & Telescope Setup
```bash
# Install debugging tools (dev only)
composer require barryvdh/laravel-debugbar --dev
composer require laravel/telescope --dev

# Publish and migrate
php artisan vendor:publish --provider="Laravel\Telescope\TelescopeServiceProvider"
php artisan migrate
```

### Logging & Exception Handling

#### Standardized Logging
```php
// In controllers and services
use Illuminate\Support\Facades\Log;

// Error logging
Log::error('Payment processing failed', [
    'user_id' => auth()->id(),
    'transaction_data' => $request->all(),
    'error' => $exception->getMessage()
]);

// Info logging for debugging
Log::info('Bank transfer initiated', [
    'bank_id' => $bankId,
    'amount' => $amount,
    'user_id' => auth()->id()
]);
```

#### Global Exception Handler
```php
// app/Exceptions/Handler.php
public function register()
{
    $this->reportable(function (Throwable $e) {
        // Send to Sentry/Bugsnag in production
        if (app()->environment('production')) {
            app('sentry')->captureException($e);
        }
        
        // Log with context
        Log::error('Unhandled exception', [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id() ?? null,
            'url' => request()->fullUrl(),
            'ip' => request()->ip()
        ]);
    });
}
```

#### Custom Exception Classes
```php
// app/Exceptions/PaymentException.php
<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public function report()
    {
        Log::error('Payment Exception', [
            'message' => $this->getMessage(),
            'user_id' => auth()->id(),
            'context' => $this->getContext()
        ]);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment processing failed',
            'error_code' => 'PAYMENT_ERROR'
        ], 422);
    }
}
```

### Reproduce & Fix Workflow

#### TDD Approach - Write Failing Test First
```php
// tests/Feature/FinancialTransactionTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Bank\Bank;

class FinancialTransactionTest extends TestCase
{
    /** @test */
    public function it_processes_bank_transfer_with_bank_id()
    {
        $user = User::factory()->create();
        $bank = Bank::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/financial-transactions', [
                'amount' => 100.00,
                'payment_method' => 'bank_transfer',
                'is_bank_transaction' => true,
                'bank_id' => $bank->id,
                'fiche_navette_id' => 1,
                'fiche_navette_item_id' => 1
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bank_id' => $bank->id,
                    'is_bank_transaction' => true
                ]
            ]);
    }
}
```

#### Debug Commands
```bash
# Use artisan tinker for quick debugging
php artisan tinker
>>> $transaction = App\Models\Caisse\FinancialTransaction::find(1);
>>> $transaction->bank_id;
>>> dd($transaction->toArray());

# Enable query logging
DB::listen(function ($query) {
    Log::info('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time
    ]);
});
```

## 3. Database Issue Resolution (MySQL)

### Query Debugging

#### Capture Slow Queries with Telescope
```php
// In AppServiceProvider boot method
if (app()->environment('local')) {
    DB::listen(function ($query) {
        if ($query->time > 100) { // Log queries taking more than 100ms
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms'
            ]);
        }
    });
}
```

#### N+1 Query Fix Example
```php
// Before (N+1 problem)
$transactions = FinancialTransaction::all();
foreach ($transactions as $transaction) {
    echo $transaction->patient->name; // N+1 query
}

// After (Eager loading)
$transactions = FinancialTransaction::with(['patient', 'bank'])->get();
foreach ($transactions as $transaction) {
    echo $transaction->patient->name; // Single query
}
```

### Migration & Data Fixes

#### Safe Migration Rollback
```bash
# Check migration status
php artisan migrate:status

# Rollback specific migration
php artisan migrate:rollback --step=1

# Rollback to specific batch
php artisan migrate:rollback --batch=3

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

#### Data Correction Scripts
```php
// database/seeders/FixBankTransactionDataSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Caisse\FinancialTransaction;

class FixBankTransactionDataSeeder extends Seeder
{
    public function run()
    {
        // Fix missing bank_id for bank transfers
        FinancialTransaction::where('payment_method', 'bank_transfer')
            ->whereNull('bank_id')
            ->update(['bank_id' => 1]); // Default bank

        // Update is_bank_transaction flag
        FinancialTransaction::where('payment_method', 'bank_transfer')
            ->update(['is_bank_transaction' => true]);
    }
}
```

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
        sourcemap: true, // Enable source maps for debugging
    },
});
```

### Logging & Error Handling

#### Standardized Frontend Logging
```javascript
// utils/logger.js
export const logger = {
    error: (message, context = {}) => {
        console.error(`[HIS Error] ${message}`, context);
        // Send to monitoring service in production
        if (import.meta.env.PROD) {
            // Sentry.captureException(new Error(message), { extra: context });
        }
    },
    
    warn: (message, context = {}) => {
        console.warn(`[HIS Warning] ${message}`, context);
    },
    
    info: (message, context = {}) => {
        console.info(`[HIS Info] ${message}`, context);
    }
};
```

#### API Error Handling
```javascript
// services/api.js
import axios from 'axios';
import { logger } from '@/utils/logger';

const api = axios.create({
    baseURL: '/api',
    timeout: 10000
});

api.interceptors.response.use(
    response => response,
    error => {
        logger.error('API Request Failed', {
            url: error.config?.url,
            method: error.config?.method,
            status: error.response?.status,
            data: error.response?.data,
            message: error.message
        });
        
        // Show user-friendly error message
        const message = error.response?.data?.message || 'An error occurred';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 4000
        });
        
        return Promise.reject(error);
    }
);
```

#### Component Testing with Jest
```javascript
// tests/Vue/Components/GlobalPayment.test.js
import { mount } from '@vue/test-utils';
import GlobalPayment from '@/Components/GlobalPayment.vue';
import axios from 'axios';

jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

describe('GlobalPayment', () => {
    it('includes bank transaction data when bank transfer is selected', async () => {
        const wrapper = mount(GlobalPayment, {
            props: {
                modelValue: 100,
                method: 'bank_transfer',
                bankId: 1,
                isBankTransaction: true
            }
        });

        await wrapper.find('[data-testid="pay-button"]').trigger('click');

        expect(wrapper.emitted('pay-global')).toBeTruthy();
        const emittedData = wrapper.emitted('pay-global')[0][0];
        expect(emittedData.is_bank_transaction).toBe(true);
        expect(emittedData.bank_id).toBe(1);
    });
});
```

## 5. Code Analysis & Prevention

### Static Analysis Setup

#### PHPStan Configuration
```neon
# phpstan.neon
parameters:
    level: 8
    paths:
        - app
        - tests
    excludePaths:
        - app/Console/Commands/stubs
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'
    checkMissingIterableValueType: false
```

#### ESLint Configuration
```javascript
// .eslintrc.js
module.exports = {
    env: {
        browser: true,
        es2021: true,
        node: true
    },
    extends: [
        'eslint:recommended',
        '@vue/eslint-config-typescript',
        'plugin:vue/vue3-recommended'
    ],
    rules: {
        'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
        'vue/no-unused-vars': 'error',
        'vue/require-default-prop': 'error'
    }
};
```

### Code Review Checklist

#### Backend Review Points:
- [ ] Proper validation rules applied
- [ ] Authorization checks in place
- [ ] Database transactions used for multi-step operations
- [ ] Proper error handling and logging
- [ ] Input sanitization for security
- [ ] Performance considerations (N+1 queries, etc.)
- [ ] Tests cover edge cases

#### Frontend Review Points:
- [ ] Props validation defined
- [ ] Error states handled gracefully
- [ ] Loading states implemented
- [ ] Accessibility attributes present
- [ ] Responsive design considerations
- [ ] Performance optimizations (v-memo, computed properties)
- [ ] Component tests written

## 6. Automation & CI/CD

### GitHub Actions Workflow
```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: his_test
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, dom, fileinfo, mysql
        
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        
    - name: Install PHP dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader
      
    - name: Install Node dependencies
      run: npm ci
      
    - name: Run PHPStan
      run: vendor/bin/phpstan analyse
      
    - name: Run ESLint
      run: npm run lint
      
    - name: Build assets
      run: npm run build
      
    - name: Run PHP tests
      run: php artisan test
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: his_test
        DB_USERNAME: root
        DB_PASSWORD: password
        
    - name: Run Vue tests
      run: npm run test
```

### Rollback Strategies

#### Feature Flags Implementation
```php
// config/features.php
return [
    'bank_transfer_v2' => env('FEATURE_BANK_TRANSFER_V2', false),
    'new_payment_ui' => env('FEATURE_NEW_PAYMENT_UI', false),
];

// In controller
if (config('features.bank_transfer_v2')) {
    return $this->processBankTransferV2($request);
}
return $this->processBankTransfer($request);
```

#### Blue/Green Deployment Script
```bash
#!/bin/bash
# deploy.sh

# Build new version
docker build -t his:new .

# Test new version
docker run -d --name his-test his:new
sleep 30
curl -f http://localhost:8001/health || exit 1

# Switch traffic
docker stop his-current
docker run -d --name his-new -p 8000:8000 his:new
docker rm his-current
docker rename his-new his-current

echo "Deployment successful"
```

## 7. Monitoring & Validation

### Post-Fix Validation

#### Automated Smoke Tests with Laravel Dusk
```php
// tests/Browser/PaymentFlowTest.php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaymentFlowTest extends DuskTestCase
{
    /** @test */
    public function user_can_complete_bank_transfer_payment()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::find(1))
                    ->visit('/caisse/patient/1')
                    ->select('@payment-method', 'bank_transfer')
                    ->select('@bank-selection', '1')
                    ->type('@amount-input', '100')
                    ->click('@pay-button')
                    ->waitForText('Payment successful')
                    ->assertSee('Payment successful');
        });
    }
}
```

#### Health Check Endpoint
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::store()->getStore() ? 'connected' : 'disconnected',
        'timestamp' => now()->toISOString()
    ]);
});
```

### Monitoring Setup

#### Sentry Configuration
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('APP_ENV'),
    'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
];
```

#### Custom Alerts
```php
// app/Observers/FinancialTransactionObserver.php
<?php

namespace App\Observers;

use App\Models\Caisse\FinancialTransaction;
use Illuminate\Support\Facades\Log;

class FinancialTransactionObserver
{
    public function created(FinancialTransaction $transaction)
    {
        // Alert on high-value transactions
        if ($transaction->amount > 10000) {
            Log::warning('High-value transaction created', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'user_id' => auth()->id()
            ]);
        }
    }

    public function failed($event, $exception)
    {
        Log::error('Transaction processing failed', [
            'exception' => $exception->getMessage(),
            'event' => $event
        ]);
    }
}
```

## Quick Reference Commands

### Development
```bash
# Start debugging session
php artisan serve --host=0.0.0.0 --port=8000

# Clear all caches
php artisan optimize:clear

# Run specific test
php artisan test --filter=BankTransferTest

# Generate test coverage
php artisan test --coverage-html coverage/

# Database operations
php artisan migrate:fresh --seed
php artisan db:seed --class=FixBankTransactionDataSeeder
```

### Production Debugging
```bash
# View logs
tail -f storage/logs/laravel.log

# Check queue status
php artisan queue:work --verbose

# Monitor performance
php artisan telescope:prune --hours=48
```

### Frontend
```bash
# Development server with hot reload
npm run dev

# Build for production
npm run build

# Run tests
npm run test

# Lint and fix
npm run lint -- --fix
```

## Emergency Procedures

### Critical Bug Response
1. **Immediate**: Rollback to previous stable version
2. **Assessment**: Identify scope and impact
3. **Communication**: Notify stakeholders
4. **Fix**: Apply hotfix with minimal changes
5. **Validation**: Test thoroughly before deployment
6. **Post-mortem**: Document lessons learned

### Data Recovery
```sql
-- Backup before any data fixes
mysqldump -u root -p his_database > backup_$(date +%Y%m%d_%H%M%S).sql

-- Restore from backup if needed
mysql -u root -p his_database < backup_20240115_143022.sql
```

This playbook should be regularly updated as the system evolves and new debugging techniques are discovered.