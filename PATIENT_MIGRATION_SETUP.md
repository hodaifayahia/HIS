# Patient Model Enhancement - Migration Setup Guide

## ✅ Files Created & Updated

### 1. **Migration File Created**
📁 **Location**: `database/migrations/2025_11_15_133225_add_identity_and_contact_fields_to_patients_table.php`

**Status**: ✅ Ready to apply

**What it does**:
- Adds 9 new columns to the `patients` table
- Creates 3 performance indexes
- Includes proper up() and down() methods for rollback support

### 2. **Patient Model Updated**
📁 **Location**: `app/Models/Patient.php`

**Changes made**:
- ✅ Updated `$fillable` array with all new fields
- ✅ Updated `$casts` array with proper type casting

### 3. **New Fields Added**

| # | Field Name | Column Type | Details |
|---|---|---|---|
| 1 | Fax Number | `string(20)` | nullable |
| 2 | Identity Document Type | `enum` | 5 options (national_card, passport, foreigner_card, drivers_license, other) |
| 3 | Identity Issued On | `date` | nullable |
| 4 | Identity Issued By | `string(255)` | nullable |
| 5 | Passport Number | `string(50)` | nullable, **UNIQUE** |
| 6 | Professional Badge Number | `string(50)` | nullable |
| 7 | Foreigner Card Number | `string(50)` | nullable, **UNIQUE** |
| 8 | Is Birth Place Presumed | `boolean` | default: false |
| 9 | Additional IDs | `json` | nullable, stores array |

---

## 🚀 How to Apply the Migration

### Option 1: Direct Migration (Recommended)

```bash
cd /home/administrator/www/HIS

# Apply the migration
php artisan migrate

# Expected output:
# Migrating: 2025_11_15_133225_add_identity_and_contact_fields_to_patients_table
# Migrated:  2025_11_15_133225_add_identity_and_contact_fields_to_patients_table (XXms)
```

### Option 2: With Force Flag (If needed)

```bash
php artisan migrate --force
```

### Option 3: Via Docker/Sail

```bash
./vendor/bin/sail artisan migrate
```

---

## 🔄 Rollback (If needed)

To revert this migration:

```bash
php artisan migrate:rollback

# Or rollback the last batch:
php artisan migrate:rollback --step=1
```

---

## ✅ Verification After Migration

### Check if migration was applied:

```bash
# View all migrations
php artisan migrate:status

# Should show: 2025_11_15_133225_add_identity_and_contact_fields_to_patients_table ✓ Batch 1 (or higher)
```

### Verify database columns using MySQL:

```sql
-- Connect to your database and run:
DESCRIBE patients;

-- You should see the new columns at the end
-- OR use a more detailed query:
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'patients' 
AND TABLE_SCHEMA = 'his_database'
ORDER BY ORDINAL_POSITION DESC 
LIMIT 10;
```

---

## 🛠️ Next Steps After Migration

### 1. Update Validation Rules
Create or update form request validation:

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

### 2. Update Patient Factory (for testing)

```php
// database/factories/PatientFactory.php
public function definition(): array
{
    return [
        // ... existing fields ...
        'fax_number' => $this->faker->optional()->phoneNumber(),
        'identity_document_type' => $this->faker->randomElement(['national_card', 'passport', 'foreigner_card']),
        'identity_issued_on' => $this->faker->optional()->dateTime(),
        'identity_issued_by' => $this->faker->optional()->company(),
        'passport_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{7}'),
        'professional_badge_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{6}'),
        'foreigner_card_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{8}'),
        'is_birth_place_presumed' => false,
        'additional_ids' => null,
    ];
}
```

### 3. Update Vue Components
Update patient creation/edit forms to include new fields

### 4. Create API Resources
Update patient API resources to expose new fields

### 5. Update Tests
Add tests for new patient fields

---

## 📊 Database Schema After Migration

```sql
patients table will have:
- Original 33 columns
- NEW 9 columns
- Total: 42 columns
- 3 new indexes for performance
```

---

## ⚠️ Important Notes

1. **Nullable Fields**: All new fields are nullable by default, so no data loss for existing patients
2. **Unique Constraints**: `passport_number` and `foreigner_card_number` are unique
3. **Backward Compatible**: Migration won't affect existing patient data
4. **Reversible**: Migration includes proper down() method for rollback
5. **Indexes**: 3 indexes added for better query performance on ID lookups

---

## 🐛 Troubleshooting

### If migration fails:

```bash
# Check database connection
php artisan tinker
> DB::connection()->getPdo()

# Check migration status
php artisan migrate:status

# See detailed error
php artisan migrate --verbose

# Check if table exists
SHOW TABLES LIKE 'patients';
```

### If you need to manually apply the SQL:

```sql
ALTER TABLE `patients` ADD COLUMN `fax_number` VARCHAR(20) NULL AFTER `phone`;
ALTER TABLE `patients` ADD COLUMN `identity_document_type` ENUM('national_card','passport','foreigner_card','drivers_license','other') NULL AFTER `Idnum`;
ALTER TABLE `patients` ADD COLUMN `identity_issued_on` DATE NULL AFTER `identity_document_type`;
ALTER TABLE `patients` ADD COLUMN `identity_issued_by` VARCHAR(255) NULL AFTER `identity_issued_on`;
ALTER TABLE `patients` ADD COLUMN `passport_number` VARCHAR(50) NULL UNIQUE AFTER `identity_issued_by`;
ALTER TABLE `patients` ADD COLUMN `professional_badge_number` VARCHAR(50) NULL AFTER `passport_number`;
ALTER TABLE `patients` ADD COLUMN `foreigner_card_number` VARCHAR(50) NULL UNIQUE AFTER `professional_badge_number`;
ALTER TABLE `patients` ADD COLUMN `is_birth_place_presumed` TINYINT(1) DEFAULT 0 AFTER `birth_place`;
ALTER TABLE `patients` ADD COLUMN `additional_ids` JSON NULL AFTER `is_birth_place_presumed`;

-- Add indexes
CREATE INDEX idx_passport_number ON patients(passport_number);
CREATE INDEX idx_foreigner_card_number ON patients(foreigner_card_number);
CREATE INDEX idx_professional_badge_number ON patients(professional_badge_number);
```

---

## Summary

| Item | Status | Location |
|------|--------|----------|
| Migration File | ✅ Created | `database/migrations/2025_11_15_133225_add_identity_and_contact_fields_to_patients_table.php` |
| Patient Model | ✅ Updated | `app/Models/Patient.php` |
| Ready to Apply | ✅ Yes | Run `php artisan migrate` |
| Validation Rules | ⏳ Next Step | Create in form requests |
| Vue Components | ⏳ Next Step | Update patient forms |
| Tests | ⏳ Next Step | Add test coverage |

---

**Date**: November 15, 2025  
**Branch**: TestProducation  
**Migration ID**: 2025_11_15_133225
