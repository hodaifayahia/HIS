# AI Agent Instructions for HIS (Hospital Information System)

> Comprehensive guidelines for coding agents to be immediately productive in this Laravel 11 + Vue 3 medical application.

## 🏥 Project Overview

**HIS** is a comprehensive Hospital Information System managing patient care workflows:
- **Patient management** - medical records, history, allergies, chronic diseases
- **Appointment scheduling** - doctor availability, modality slots, patient booking
- **Clinical workflows** - consultations (fiches navette), prescriptions, procedures
- **Medical transactions** - prestations (services), packages, dynamic pricing
- **B2B operations** - conventions (insurance/org contracts), avenants, invoicing
- **Inventory & logistics** - medications, supplies, stock management, purchase orders
- **Financial** - payments, fund transfers, cash management, approval workflows

**Tech Stack**: PHP 8.2+ | Laravel 11 | Vue 3 | Tailwind CSS 4 | PrimeVue | Vite | MySQL

---

## 🏗️ Architecture: Service-Oriented Design

**ALL business logic lives in `/app/Services/`** - Controllers are thin request/response handlers.

Key service structure by domain:
- `ReceptionService` - consultation records (fiches), items, pricing, auto-package conversion
- `ConventionPricingService` - dynamic pricing per insurance/org contract
- `AvenantService` - convention amendments (add services, modify pricing)
- `PrestationValidationService` - validate medical service eligibility
- `BonReceptionService`, `StockMovementApprovalService` - inventory workflows

**Pattern**: Service methods receive validated data → perform DB operations in transactions → log results → return fresh model with eagerly-loaded relationships

```php
public function createFicheNavette(array $data): ficheNavette
{
    DB::beginTransaction();
    try {
        $fiche = ficheNavette::create([
            'patient_id' => $data['patient_id'],
            'creator_id' => Auth::id(),
        ]);
        DB::commit();
        // Return fresh model with relationships to prevent N+1
        return $fiche->fresh(['items.prestation', 'items.package', 'patient']);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('FicheNavette creation failed:', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

---

## 🎯 Critical Feature: Automatic Package Conversion (Refactored with Actions & Observers)

This is a core differentiator - when 2+ prestation items are added to a fiche navette:

### How It Works (Clean Architecture)

```
1. Item Created
   ↓
2. FicheNavetteItemObserver → Dispatch Async Job
   ↓
3. CheckAndConvertFichePackageJob (Queued)
   ├─ PreparePackageConversionData (Action)
   │  └─ DetectMatchingPackage (Action)
   ├─ If should_convert = true
   └─ ExecutePackageConversion (Action)
      └─ PrestationsConvertedToPackage (Event)
         └─ Listeners (audit log, notifications, etc.)
```

### Key Components

**Actions** (Single Responsibility):
- `DetectMatchingPackage::execute(array $prestationIds)` - Find best matching package
- `PreparePackageConversionData::execute(...)` - Analyze fiche, decide if conversion needed
- `ExecutePackageConversion::execute(...)` - Remove old items, create package item, fire event

**Observers & Jobs**:
- `FicheNavetteItemObserver` - Listens to item creation, dispatches async job
- `CheckAndConvertFichePackageJob` - Queued job that calls Actions and fires Events
- `PackageAutoConversionChecked`, `PrestationsConvertedToPackage` - Domain Events

**Facade**:
- `PackageConversionFacade` - Clean public API wrapping all Actions

### Key Benefits
✅ **Separation of Concerns** - Each Action has one job  
✅ **Testable** - Mock Actions individually  
✅ **Async** - Doesn't block HTTP response  
✅ **Observable** - Events allow listeners for audit/notifications  
✅ **Maintainable** - Clear flow, easy to modify logic  

### Important
- Only **standard prestations** are checked (convention & dependency items are preserved)
- Detects both exact and subset matches, prefers exact
- Handles cascading: replacing smaller package with larger package

See: `AUTOMATIC_PACKAGE_CONVERSION.md` for full documentation

---

## 📂 Project Structure (Updated with Actions & Observers)

```
app/
├── Services/              # Business logic wrappers (high-level API)
│   ├── Reception/         # Consultations, fiches
│   │   ├── ReceptionService.php           # Main service
│   │   └── PackageConversionFacade.php    # Clean API for Actions
│   ├── B2B/               # Conventions, avenants
│   └── [Domain]/          
├── Actions/               # ⭐ SINGLE RESPONSIBILITY (NEW)
│   ├── Reception/
│   │   ├── DetectMatchingPackage.php      # Find package match
│   │   ├── PreparePackageConversionData.php  # Analyze conversion
│   │   └── ExecutePackageConversion.php   # Execute conversion
│   └── [Domain]/
├── Observers/             # ⭐ MODEL LIFECYCLE HOOKS (NEW)
│   ├── Reception/
│   │   └── FicheNavetteItemObserver.php   # Listen to item events
│   └── [Domain]/
├── Jobs/                  # ⭐ QUEUED JOBS (NEW)
│   ├── Reception/
│   │   └── CheckAndConvertFichePackageJob.php  # Async conversion
│   └── [Domain]/
├── Events/                # ⭐ DOMAIN EVENTS (NEW)
│   ├── Reception/
│   │   ├── PackageAutoConversionChecked.php
│   │   └── PrestationsConvertedToPackage.php
│   └── [Domain]/
├── Models/                # Eloquent (Patient, Doctor, Prestation, etc)
├── Http/
│   ├── Controllers/       # Thin - delegate to Services
│   ├── Requests/          # Form validation
│   └── Resources/         # API response transformers
└── Enums/                 # Status enums

database/
├── migrations/            
└── seeders/               

resources/
├── js/
│   ├── Components/        # Vue components
│   ├── services/          # API clients
│   └── app.js             
└── css/                   

tests/
├── Feature/               
└── Unit/                  
```

**Key Pattern Changes**:
- ✅ Complex business logic → **Actions** (testable, reusable)
- ✅ Model events → **Observers** (clean separation)
- ✅ Long-running tasks → **Jobs** (async, retryable)
- ✅ Side effects → **Events** (listeners decoupled)

---

## 💾 Key Models & Data Relationships

**Medical Core**:
- `Patient` - person record with medical history, contacts
- `Doctor` - physician with specializations, schedules, availability
- `Prestation` - medical service (e.g., "ECG", "Blood Work") with pricing
- `PrestationPackage` - bundle of prestations (e.g., "Cardiology" = ECG + labs)
- `ficheNavette` - consultation/visit with items (prestations/packages/custom)
- `Appointment` - scheduled doctor-patient meeting

**B2B/Financial**:
- `Convention` - insurance/org pricing agreement with special rates
- `Avenant` - amendment to convention (add coverage, modify pricing)
- `Facture` - invoice to convention
- `BonCommend` - purchase order to supplier

**Inventory**:
- `Medication`, `Product` - pharmacy/supply items
- `Stock` - current inventory levels
- `BonReception` - goods receipt from supplier

**Relationships example**:
```php
// Fiche has many items (prestations, packages, dependencies)
FicheNavette::with('items.prestation', 'items.package');

// Package has many prestations (many-to-many)
PrestationPackage::with('prestations');

// Convention has pricing rules
Convention::with('prestations'); // Special pricing for this org
```

---

## 🔌 API & Frontend Patterns

### Controller → Resource Pattern
Controllers return **Eloquent API Resources** for clean JSON responses:

```php
// app/Http/Controllers/Reception/FicheNavetteController.php
use App\Http\Resources\Reception\FicheNavetteResource;

public function store(StoreFicheNavetteRequest $request): JsonResponse
{
    $fiche = $this->receptionService->createFicheNavette($request->validated());
    return response()->json(['data' => new FicheNavetteResource($fiche)]);
}

// app/Http/Resources/Reception/FicheNavetteResource.php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'items' => FicheNavetteItemResource::collection($this->items),
        'total' => $this->total_amount,
        'patient' => new PatientResource($this->patient),
    ];
}
```

### Vue 3 + Service Layer Pattern
Components NEVER call axios directly - use service layer:

```js
// resources/js/services/Reception/ficheNavetteService.js
export default {
    async getFicheNavette(id) {
        const response = await axios.get(`/api/reception/fiche-navette/${id}`);
        return response.data.data; // Resource data
    },

    async addItemsToFicheNavette(ficheId, items) {
        const response = await axios.post(
            `/api/reception/fiche-navette/${ficheId}/items`,
            { items }
        );
        return response.data; // Includes conversion_data if auto-converted
    }
}

// Vue component
import ficheNavetteService from '@/services/Reception/ficheNavetteService'

export default {
    methods: {
        async loadFiche() {
            this.fiche = await ficheNavetteService.getFicheNavette(this.ficheId);
            this.items = this.fiche.items;
        }
    }
}
```

---

## 🧪 Testing Requirements

### Test Setup
- Uses **PHPUnit 11** (NOT Pest)
- Feature tests use `RefreshDatabase` trait (fresh DB per test)
- Test database: SQLite configured in `phpunit.xml`

### Writing Tests
```php
// tests/Feature/Reception/FicheNavetteTest.php
class FicheNavetteTest extends TestCase
{
    use RefreshDatabase; // Fresh DB for each test

    public function test_can_create_fiche_with_prestations(): void
    {
        $patient = Patient::factory()->create();
        $prestations = Prestation::factory()->count(2)->create();

        $response = $this->postJson('/api/reception/fiche-navette', [
            'patient_id' => $patient->id,
            'prestations' => $prestations->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('fiche_navettes', ['patient_id' => $patient->id]);
    }
}
```

### Running Tests
```bash
php artisan test                              # All tests
php artisan test tests/Feature/Reception/     # Directory
php artisan test --filter=testName            # Single test
```

**Key Rule**: Every code change MUST have a test. Run tests before committing.

---

## 🛠️ Development Workflows

### Start Development
```bash
composer run dev
# Concurrently starts:
#   php artisan serve         (HTTP server)
#   npm run dev               (Vite hot reload)
#   php artisan queue:work    (Job processor)
#   php artisan reverb:start  (WebSocket server)
```

### Code Quality
```bash
php artisan pint --dirty      # Format changed files (REQUIRED)
php artisan phpstan           # Static analysis
php artisan test              # Run full suite
```

### Database Work
```bash
php artisan make:migration create_table_name --create=table_name
php artisan migrate           # Apply pending
php artisan migrate:fresh     # Reset + reseed
php artisan tinker            # Interactive shell
```

---

## 📋 Implementation Examples

### Adding a New Prestation (Medical Service)

```php
// 1. Service (business logic)
// app/Services/CONFIGURATION/PrestationService.php
public function createPrestation(array $data): Prestation
{
    return Prestation::create([
        'name' => $data['name'],
        'internal_code' => $data['code'],
        'price_with_vat_and_consumables_variant' => $data['price'],
        'is_active' => true,
    ]);
}

// 2. Validation
// app/Http/Requests/StorePrestationRequest.php
public function rules(): array
{
    return [
        'name' => 'required|string|unique:prestations',
        'code' => 'required|string|unique:prestations,internal_code',
        'price' => 'required|numeric|gt:0',
    ];
}

// 3. Test
// tests/Feature/CONFIGURATION/PrestationTest.php
public function test_can_create_prestation(): void
{
    $response = $this->postJson('/api/prestations', [
        'name' => 'ECG',
        'code' => 'ECG-001',
        'price' => 500,
    ]);
    $response->assertStatus(201);
}
```

---

## 🚨 Common Gotchas (Updated)

1. **Package Conversion Flow** (Refactored)
   - Item created → `FicheNavetteItemObserver::created()` fires
   - Observer dispatches `CheckAndConvertFichePackageJob` (async)
   - Job calls `PreparePackageConversionData` → `DetectMatchingPackage`
   - If match found, calls `ExecutePackageConversion` → fires `PrestationsConvertedToPackage` event
   - Log: `grep "Executed package conversion" storage/logs/laravel.log`

2. **Testing Actions**
   ```php
   // Test DetectMatchingPackage action directly
   $detector = new DetectMatchingPackage();
   $package = $detector->execute([5, 87, 88]);
   $this->assertNotNull($package);
   ```

3. **Adding Event Listeners**
   ```php
   // app/Listeners/Reception/LogPackageConversion.php
   public function handle(PrestationsConvertedToPackage $event): void
   {
       Log::info('Package converted', [
           'fiche_id' => $event->fiche->id,
           'new_package' => $event->newPackage->name,
       ]);
   }
   
   // Register in EventServiceProvider
   protected $listen = [
       PrestationsConvertedToPackage::class => [
           LogPackageConversion::class,
       ],
   ];
   ```

4. **Observer Gotchas**
   - Observers run during model save/delete
   - Avoid infinite loops (don't modify the model in observer)
   - Use `wasChanged()` to check specific columns

5. **Job Processing**
   - Jobs require queue worker: `php artisan queue:work`
   - Dev env: `composer run dev` starts worker automatically
   - Check failed jobs: `php artisan queue:failed`

6. **N+1 Query Prevention**
   - Use `fresh(['relationship1', 'relationship2'])` after saves
   - Use `with()` in queries

7. **Vue Changes Not Showing**
   - Run `npm run dev` (Vite watch mode)
   - Or `npm run build` then refresh

8. **Pint Formatting**
   - Run `php artisan pint --dirty` before finalizing

---

## 📚 Key Terminology

| Term | Definition |
|------|-----------|
| **Fiche Navette** | Consultation record/visit |
| **Prestation** | Medical service (ECG, labs) |
| **Package** | Bundle of prestations |
| **Convention** | Insurance contract |
| **Bon Commend** | Purchase order |
| **Caisse** | Cash account |
| **Modality** | Department/clinic |

---

## ✅ Pre-Commit Checklist (Updated)

- [ ] Action has single responsibility
- [ ] Observer only listens to model events (no heavy logic)
- [ ] Job wraps async work with retry logic
- [ ] Events fired at end of successful operations
- [ ] Listeners registered in EventServiceProvider
- [ ] Controller returns Resource-transformed response
- [ ] Test covers happy + edge cases
- [ ] Code formatted: `php artisan pint --dirty`
- [ ] Tests passing: `php artisan test`
- [ ] No N+1 queries
- [ ] Logging added (use structured arrays)

## 🎯 When to Use What

| Scenario | Use |
|----------|-----|
| Complex business logic | **Action** class |
| Model lifecycle hook | **Observer** |
| Long-running task | **Job** (queued) |
| Side effects after success | **Event** + **Listeners** |
| API response transform | **Resource** class |
| Request validation | **FormRequest** class |
| Thin HTTP adapter | **Controller** |

---

## 📖 Related Documentation

- `AUTOMATIC_PACKAGE_CONVERSION.md` - package detection internals
- `SOLUTION_ARCHITECTURE.md` - system data flows
- `PRESTATION_DEPENDENCIES_GUIDE.md` - dependency resolution

---

## 🔗 Key Files (Refactored)

**Package Conversion** (New Architecture):
- `app/Actions/Reception/DetectMatchingPackage.php` - Find package match
- `app/Actions/Reception/PreparePackageConversionData.php` - Analyze conversion
- `app/Actions/Reception/ExecutePackageConversion.php` - Execute conversion
- `app/Observers/Reception/FicheNavetteItemObserver.php` - Listen to events
- `app/Jobs/Reception/CheckAndConvertFichePackageJob.php` - Async job
- `app/Events/Reception/PrestationsConvertedToPackage.php` - Domain event
- `app/Services/Reception/PackageConversionFacade.php` - Clean API

**Core Services**:
- `app/Services/Reception/ReceptionService.php` - consultation logic
- `resources/js/services/Reception/ficheNavetteService.js` - frontend API client
- `app/Http/Resources/Reception/FicheNavetteResource.php` - API transformer
- `tests/Feature/Reception/` - integration tests

---

# Laravel Boost Guidelines (Inherited)

The Laravel Boost guidelines are specifically curated by Laravel maintainers. These must be followed:

## Foundational Context
- php: 8.2+
- laravel/framework: v11
- phpunit/phpunit: v11
- vue: v3
- tailwindcss: v4

## Conventions
- Follow existing code patterns - check sibling files before creating new
- Use descriptive names (`isRegisteredForDiscounts`, not `discount()`)
- Reuse existing components before writing new ones

## Application Structure & Architecture
- Stick to existing directory structure
- Do not change dependencies without approval

## Frontend Bundling
- If frontend changes don't show: run `npm run build`, `npm run dev`, or `composer run dev`

## Laravel Patterns
- Use `php artisan make:` commands to scaffold
- Always use explicit return type declarations
- Use PHPDoc blocks over comments
- Constructor property promotion: `public function __construct(public UserService $service) {}`

### Database
- Use proper Eloquent relationships with return types
- Avoid `DB::`; prefer `Model::query()`
- Prevent N+1 with eager loading
- Migrations include all column attributes (never lose data during modification)

### Controllers & Validation
- Create Form Request classes for validation (not inline)
- Return JSON with Eloquent Resources

### Authentication & Authorization
- Use Laravel's built-in features (gates, policies, Sanctum)

### Configuration
- Use environment variables only in config files - never `env()` outside of config

### Testing
- Use factories for test models (check for custom states)
- Most tests should be Feature tests (use `RefreshDatabase`)
- Run single tests after changes: `php artisan test --filter=testName`

### Code Formatting
- Run `vendor/bin/pint --dirty` before finalizing changes
- Do not use `--test`, just run `pint` to fix

### Vite Error
- If "Unable to locate file in Vite manifest" error: run `npm run build` or `npm run dev`

---

**Branch**: TestProducation | **Updated**: October 2025
