# Patient Model - Fields Comparison & Migration Guide

## Your Requirements vs Current Database

### ✅ FIELDS YOU HAVE (Already in Database)

| Field | Current Column | Type | Status |
|-------|---|---|---|
| **First Name** | `Firstname` | varchar(255) | ✅ Exists |
| **Last Name** | `Lastname` | varchar(255) | ✅ Exists |
| **Parent/Father Name** | `Parent` | varchar(255) | ✅ Exists |
| **Date of Birth** | `dateOfBirth` | date | ✅ Exists |
| **Phone Number** | `phone` | varchar(255) | ✅ Exists |
| **Address** | `address` | varchar(255) | ✅ Exists |
| **Postal Code** | `postal_code` | varchar(255) | ✅ Exists |
| **City** | `city` | varchar(255) | ✅ Exists |
| **Email** | `email` | varchar(255) | ✅ Exists |
| **Marital Status** | `marital_status` | varchar(255) | ✅ Exists |
| **Mother Name** | `mother_lastname`, `mother_firstname` | varchar(255) | ✅ Exists |
| **Gender** | `gender` | tinyint(1) | ✅ Exists |
| **National ID** | `Idnum` | text | ✅ Exists |
| **NSS (Social Security)** | `nss` | varchar(255) | ✅ Exists |
| **Birth Place** | `birth_place` | varchar(255) | ✅ Exists |
| **Blood Group** | `blood_group` | varchar(255) | ✅ Exists |

---

### ❌ FIELDS YOU NEED TO ADD (Missing from Database)

| Field | Requirement | Type Recommendation | Priority | Note |
|-------|-------------|---|---|---|
| **Fax Number** | Contact information | varchar(20) | 🟡 MEDIUM | - |
| **Identity Document Type** | Document classification | enum/varchar(50) | 🔴 HIGH | National Card, Passport, etc |
| **Identity Issued On** | Document issue date | date | 🟡 MEDIUM | - |
| **Identity Issued By** | Issuing authority | varchar(255) | 🟡 MEDIUM | - |
| **Passport Number** | Travel document | varchar(50) unique | 🔴 HIGH | - |
| **Professional Badge Number** | Professional ID | varchar(50) | 🟡 MEDIUM | - |
| **Foreigner Card Number** | Immigration document | varchar(50) unique | 🔴 HIGH | - |
| **Is Presumed Birth Place** | Flag for presumed | tinyint(1) | 🟡 MEDIUM | Additional flag for birth_place |
| **Additional IDs** | Multiple document storage | JSON or separate table | 🟡 MEDIUM | Store multiple document numbers |

**Note**: Father name is already stored in the `Parent` field ✅

---

## Current Patient Table Structure

```sql
CREATE TABLE `patients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Firstname` varchar(255) NOT NULL,
  `Lastname` varchar(255) NOT NULL,
  `Parent` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `dateOfBirth` date DEFAULT NULL,
  `Idnum` text DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT '2',
  `gender` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `age` int DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `firstname_ar` varchar(255) DEFAULT NULL,
  `lastname_ar` varchar(255) DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `nss` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `mother_lastname` varchar(255) DEFAULT NULL,
  `mother_firstname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `blood_group` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `other_clinical_info` text DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

---

## Migration Strategy

### Phase 1: Create Migration for New Fields (HIGH PRIORITY)

```bash
php artisan make:migration add_identity_and_contact_fields_to_patients_table --table=patients
```

#### Fields to Add:

```php
// app/Models/Patient.php - Update fillable array
protected $fillable = [
    // ... existing fields ...
    'fax_number',                // NEW
    'identity_document_type',    // NEW (National Card, Passport, etc)
    'identity_issued_on',        // NEW
    'identity_issued_by',        // NEW
    'passport_number',           // NEW (UNIQUE)
    'professional_badge_number', // NEW
    'foreigner_card_number',     // NEW (UNIQUE)
    'is_birth_place_presumed',   // NEW (flag)
    'additional_ids',            // NEW (JSON for multiple documents)
];
```

### Phase 2: Migration File Content

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_identity_and_contact_fields_to_patients_table.php

Schema::table('patients', function (Blueprint $table) {
    // Contact Information
    $table->string('fax_number', 20)->nullable()->after('phone');
    
    // Identity Document Fields
    $table->enum('identity_document_type', [
        'national_card',
        'passport',
        'foreigner_card',
        'drivers_license',
        'other'
    ])->nullable()->after('Idnum');
    
    $table->date('identity_issued_on')->nullable()->after('identity_document_type');
    $table->string('identity_issued_by', 255)->nullable()->after('identity_issued_on');
    
    // Specific Document Numbers
    $table->string('passport_number', 50)->nullable()->unique()->after('identity_issued_by');
    $table->string('professional_badge_number', 50)->nullable()->after('passport_number');
    $table->string('foreigner_card_number', 50)->nullable()->unique()->after('professional_badge_number');
    
    // Birth Place Enhancement
    $table->boolean('is_birth_place_presumed')->default(false)->after('birth_place');
    
    // Additional IDs Storage (JSON)
    $table->json('additional_ids')->nullable()->after('is_birth_place_presumed');
    
    // Add indexes for performance
    $table->index('passport_number');
    $table->index('foreigner_card_number');
    $table->index('professional_badge_number');
});
```

---

## Updated Patient Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Patient extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        // Basic Info
        'Firstname',
        'Lastname',
        'firstname_ar',
        'lastname_ar',
        'Parent',                // Parent/Father name already exists
        'mother_firstname',
        'mother_lastname',
        
        // Contact
        'phone',
        'fax_number',           // NEW
        'email',
        'address',
        'postal_code',
        'city',
        
        // Identification
        'Idnum',
        'identity_document_type', // NEW
        'identity_issued_on',    // NEW
        'identity_issued_by',    // NEW
        'passport_number',       // NEW
        'professional_badge_number', // NEW
        'foreigner_card_number', // NEW
        'nss',
        'additional_ids',        // NEW
        
        // Personal
        'gender',
        'dateOfBirth',
        'birth_place',
        'is_birth_place_presumed', // NEW
        'age',
        'weight',
        'height',
        'blood_group',
        'marital_status',
        
        // Medical
        'other_clinical_info',
        
        // Administrative
        'created_by',
        'balance',
        'is_faithful',
    ];

    protected $casts = [
        'age' => 'integer',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_faithful' => 'boolean',
        'is_birth_place_presumed' => 'boolean', // NEW
        'additional_ids' => 'array', // NEW - JSON cast
        'dateOfBirth' => 'date',
        'identity_issued_on' => 'date', // NEW
    };

    protected $appends = ['fullname'];

    /**
     * Get the patient's full name.
     */
    public function getFullnameAttribute(): string
    {
        return "{$this->Firstname} {$this->Lastname}";
    }

    // ... rest of the methods ...
}
```

---

## Implementation Checklist

### Step 1: Create Migration
```bash
php artisan make:migration add_identity_and_contact_fields_to_patients_table --table=patients
```

### Step 2: Run Migration
```bash
php artisan migrate
```

### Step 3: Update Patient Model
- Add new fields to `$fillable`
- Add new fields to `$casts`
- Add accessors/methods for computed properties

### Step 4: Update Validation Rules
```php
// app/Http/Requests/StorePatientRequest.php
'fax_number' => 'nullable|string|max:20',
'identity_document_type' => 'nullable|in:national_card,passport,foreigner_card,drivers_license,other',
'identity_issued_on' => 'nullable|date',
'identity_issued_by' => 'nullable|string|max:255',
'passport_number' => 'nullable|string|max:50|unique:patients,passport_number',
'professional_badge_number' => 'nullable|string|max:50',
'foreigner_card_number' => 'nullable|string|max:50|unique:patients,foreigner_card_number',
'is_birth_place_presumed' => 'nullable|boolean',
'additional_ids' => 'nullable|array',
```

### Step 5: Update Patient Factory (for testing)
```php
// database/factories/PatientFactory.php
public function definition(): array
{
    return [
        'Firstname' => $this->faker->firstName(),
        'Lastname' => $this->faker->lastName(),
        'father_firstname' => $this->faker->firstName(),
        'father_lastname' => $this->faker->lastName(),
        'mother_firstname' => $this->faker->firstName(),
        'mother_lastname' => $this->faker->lastName(),
        'phone' => $this->faker->phoneNumber(),
        'fax_number' => $this->faker->optional()->phoneNumber(),
        'email' => $this->faker->email(),
        'dateOfBirth' => $this->faker->dateTimeBetween('-80 years', '-18 years'),
        'birth_place' => $this->faker->city(),
        'is_birth_place_presumed' => false,
        'gender' => $this->faker->randomElement([0, 1]),
        'marital_status' => $this->faker->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
        'blood_group' => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
        'passport_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{7}'),
        'foreigner_card_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{8}'),
    ];
}
```

### Step 6: Update Patient Components/Forms
- Update Vue components for patient creation/editing
- Add fields to patient profile view
- Add fields to patient list (if needed)

### Step 7: Create Seeders (Optional)
```bash
php artisan make:seeder PatientIdentityDataSeeder
```

---

## Summary Table: What to Add

| Field Name | Database Column | Type | Length | Nullable | Unique | Cast | Priority |
|---|---|---|---|---|---|---|---|
| Fax Number | `fax_number` | varchar | 20 | Yes | No | string | MEDIUM |
| Identity Document Type | `identity_document_type` | enum | - | Yes | No | string | HIGH |
| Identity Issued On | `identity_issued_on` | date | - | Yes | No | date | MEDIUM |
| Identity Issued By | `identity_issued_by` | varchar | 255 | Yes | No | string | MEDIUM |
| Passport Number | `passport_number` | varchar | 50 | Yes | **Yes** | string | HIGH |
| Professional Badge | `professional_badge_number` | varchar | 50 | Yes | No | string | MEDIUM |
| Foreigner Card Number | `foreigner_card_number` | varchar | 50 | Yes | **Yes** | string | HIGH |
| Birth Place Presumed Flag | `is_birth_place_presumed` | boolean | - | No | No | boolean | MEDIUM |
| Additional IDs (JSON) | `additional_ids` | json | - | Yes | No | array | MEDIUM |

---

## Notes

✅ **Already exists in database**: 16 fields (including Parent/Father name)  
❌ **Need to add**: 9 fields  
**Total will have**: 25+ fields

The migration can be done in a single database migration. Start with HIGH priority fields and gradually add MEDIUM priority fields as needed.
