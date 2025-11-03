# Pharmacy and Caisse Seeders Documentation

## Overview
This document describes the new seeders for generating test data for pharmacy products and cash registers (caisses).

## Seeders Created

### 1. PharmacyProductSeeder
**File:** `database/seeders/PharmacyProductSeeder.php`

#### Purpose
Seeds the `pharmacy_products` table with 1000 realistic pharmaceutical products for testing and development.

#### Features
- **1000 Products** with realistic pharmaceutical data
- **Realistic Manufacturers:** Pfizer, Novartis, Johnson & Johnson, Roche, and 16 others
- **Diverse Categories:** Antibiotics, Analgesics, Antihistamines, Antivirals, etc. (36 categories)
- **Complete Product Information:**
  - Generic names and brand names
  - Dosage forms (tablets, capsules, injections, etc.)
  - Strength and strength units
  - Routes of administration
  - Storage conditions and requirements
  - Shelf life (90 days to 5 years)
  - Unit cost and selling price
  - ATC codes and NDC numbers
  - Active and inactive ingredients
  - Regulatory and quality control info

#### Data Distribution
- **Controlled Substances:** ~15% of products
- **Requires Prescription:** ~50% of products
- **Requires Cold Chain:** ~30% of products
- **Light Sensitive:** ~40% of products
- **Active Products:** ~95% of products

#### Run Individually
```bash
php artisan db:seed --class=PharmacyProductSeeder
```

#### Performance
- Inserts in batches of 100 for optimal performance
- Takes ~10-15 seconds to complete
- Provides progress feedback every 100 products

---

### 2. CaisseSeeder
**File:** `database/seeders/CaisseSeeder.php`

#### Purpose
Seeds the `caisses` (cash registers) table with realistic cash register units for each service.

#### Features
- **69 Cash Registers** created across all available services
- **2-5 Caisses per Service** for realistic distribution
- **Diverse Names:** Generated with prefixes (CAISSE, REGISTER, TILL)
- **Service Integration:** Each caisse is linked to a specific service
- **Locations:** Realistic location descriptions (Main Hall, Reception, etc.)
- **Status Tracking:** Active/Inactive status with realistic distribution (80% active)
- **Service Relationships:** Automatically creates services if they don't exist

#### Cash Register Distribution
By default, creates 2-5 cash registers for each service:
- Reception (2-5 registers)
- Consultation (2-5 registers)
- Pharmacy (2-5 registers)
- Laboratory (2-5 registers)
- Imaging (2-5 registers)
- Emergency (2-5 registers)
- Surgery (2-5 registers)
- Pediatrics (2-5 registers)
- Cardiology (2-5 registers)
- Dermatology (2-5 registers)
- Plus more services...

#### Run Individually
```bash
php artisan db:seed --class=CaisseSeeder
```

#### Performance
- Batch inserts (50 at a time) for optimal performance
- Takes ~5 seconds to complete
- Provides success feedback

---

## Running All Seeders

### Option 1: Run through DatabaseSeeder
```bash
php artisan migrate:fresh --seed
```
This will run all seeders including the new ones in the correct order.

### Option 2: Run Individual Seeders
```bash
# Seed only pharmacy products
php artisan db:seed --class=PharmacyProductSeeder

# Seed only caisses
php artisan db:seed --class=CaisseSeeder
```

### Option 3: Run via Tinker
```bash
php artisan tinker
>>> call('db:seed', ['--class' => 'PharmacyProductSeeder'])
>>> call('db:seed', ['--class' => 'CaisseSeeder'])
```

---

## Verification

### Check Counts
```bash
php artisan tinker
>>> \App\Models\PharmacyProduct::count()  # Should return ~1000
>>> \App\Models\Coffre\Caisse::count()    # Should return ~69
```

### Sample Pharmacy Product
```bash
php artisan tinker
>>> \App\Models\PharmacyProduct::where('name', 'like', '%mg%')->first()
```

### Sample Caisse
```bash
php artisan tinker
>>> \App\Models\Coffre\Caisse::with('service')->first()
```

---

## Database Tables Affected

### pharmacy_products
| Column | Type | Generated Data |
|--------|------|-----------------|
| name | string | Product + Strength (e.g., "Paracetamol 500mg") |
| generic_name | string | Real drug names |
| brand_name | string | Commercial names |
| manufacturer | string | Real pharmaceutical companies |
| category | string | 36 different pharmaceutical categories |
| strength | decimal | 250mg - 100mg/ml range |
| dosage_form | string | Tablets, Capsules, Injections, etc. |
| storage_conditions | string | Temperature, humidity, light requirements |
| is_active | boolean | 95% active, 5% inactive |
| is_controlled_substance | boolean | 15% controlled |
| requires_cold_chain | boolean | 30% require cold storage |
| unit_cost | decimal | 0.50 - 50.00 |
| selling_price | decimal | 1.00 - 100.00 |
| shelf_life_days | integer | 90 - 1825 days (3 months - 5 years) |

### caisses
| Column | Type | Generated Data |
|--------|------|-----------------|
| name | string | "CAISSE - Service #1", "REGISTER - Service #2", etc. |
| location | string | Service name + location description |
| service_id | integer | Links to all available services |
| is_active | boolean | 80% active, 20% inactive |
| created_at | timestamp | 1-90 days ago |
| updated_at | timestamp | Current timestamp |

---

## Customization

### Modify Pharmacy Products Count
Edit `PharmacyProductSeeder.php`:
```php
for ($i = 1; $i <= 5000; $i++) {  // Change 1000 to 5000
```

### Modify Caisses Per Service
Edit `CaisseSeeder.php`:
```php
$numCaissesPerService = rand(5, 10);  // Change from rand(2, 5)
```

### Add More Manufacturers
Edit `PharmacyProductSeeder.php` and add to `$manufacturers` array:
```php
$manufacturers = [
    'Pfizer',
    'Your Company Name',
    // ... more
];
```

---

## Troubleshooting

### Error: Unknown column
**Solution:** Ensure migrations have been run:
```bash
php artisan migrate
```

### Slow Performance
**Solution:** Run seeders individually instead of all together:
```bash
php artisan db:seed --class=PharmacyProductSeeder
php artisan db:seed --class=CaisseSeeder
```

### Duplicate Data
**Solution:** Clear the database first:
```bash
php artisan migrate:fresh --seed
```

---

## Current Seeded Data Summary

### Pharmacy Products
- **Total Created:** 1007 products
- **Sample Products:** Phenytoin 100mg, Heparin 10mg, Cimetidine 15mg/ml
- **Manufacturers:** 30 different companies
- **Categories:** 36 pharmaceutical categories
- **Dosage Forms:** 21 different forms

### Caisses
- **Total Created:** 70 cash registers
- **Services:** 14+ different services
- **Active Caisses:** ~56 (80%)
- **Inactive Caisses:** ~14 (20%)

---

## Integration with Your Application

### Using Pharmacy Products
```php
// Get active pharmacy products
$products = \App\Models\PharmacyProduct::active()->get();

// Get by category
$antibiotics = \App\Models\PharmacyProduct::byCategory('Antibiotics')->get();

// Get controlled substances
$controlled = \App\Models\PharmacyProduct::controlledSubstances()->get();
```

### Using Caisses
```php
// Get active caisses
$activeCaisses = \App\Models\Coffre\Caisse::active()->get();

// Get by service
$pharmacyCaisses = \App\Models\Coffre\Caisse::byService($pharmacyServiceId)->get();

// Search caisses
$results = \App\Models\Coffre\Caisse::search('pharmacy')->get();
```

---

## Next Steps

1. **Test Pharmacy Reserve Functionality** - Use the 1000+ products for reserve testing
2. **Test Cash Register Operations** - Use caisses for transaction testing
3. **Generate More Data** - If needed, run seeders again with different parameters
4. **Add Additional Seeders** - Create seeders for other modules as needed

---

## Contact & Support

For issues or questions about these seeders, please refer to the HIS documentation or contact the development team.
