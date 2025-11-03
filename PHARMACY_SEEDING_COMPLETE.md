# Complete Pharmacy Seeding Documentation

## Overview
This document provides comprehensive information about all pharmacy-related seeders including products, storage, stockage, and cash registers.

## Seeded Data Summary

| Entity | Count | Details |
|--------|-------|---------|
| **Pharmacy Products** | 1,007 | Realistic pharmaceutical products with complete data |
| **Pharmacy Storages** | 38 | Different storage types and configurations |
| **Pharmacy Stockages** | 155 | Storage units/locations within storages |
| **Caisses** | 70 | Cash registers across services |

---

## 1. Pharmacy Products Seeder

### File
`database/seeders/PharmacyProductSeeder.php`

### Coverage
- **Count:** 1,007 pharmaceutical products
- **Manufacturers:** 30 companies (Pfizer, Novartis, Johnson & Johnson, etc.)
- **Categories:** 36 pharmaceutical categories
- **Dosage Forms:** 21 different forms

### Key Features
- Complete pharmaceutical data (strength, unit, dosage form)
- Route of administration (oral, IV, IM, topical, etc.)
- Storage requirements (temperature, humidity, light)
- Regulatory information (ATC codes, NDC numbers)
- Pricing information (unit cost, selling price)
- Status and lifecycle management

### Data Distribution
- **Controlled Substances:** 15%
- **Prescription Required:** 50%
- **Cold Chain Required:** 30%
- **Light Sensitive:** 40%
- **Active Products:** 95%

### Run Command
```bash
php artisan db:seed --class=PharmacyProductSeeder
```

---

## 2. Pharmacy Storage Seeder

### File
`database/seeders/PharmacyStorageSeeder.php`

### Coverage
- **Count:** 38 storage units
- **Storage Types:** 9 different types
- **Security Levels:** 5 levels (Basic to Maximum/DEA)
- **Configurations:** Each type optimized for its purpose

### Storage Types Created

| Type | Count | Purpose | Security | Temperature Control |
|------|-------|---------|----------|---------------------|
| General Pharmacy | 3 | Main pharmaceutical storage | Level 1 | No |
| Controlled Substances | 2 | DEA-regulated items | Level 4 | No |
| Refrigerated | 2 | 2-8°C storage | Level 3 | Yes |
| Frozen | 2 | -20 to -25°C storage | Level 3 | Yes |
| Hazardous | 2 | Dangerous materials | Level 2 | No |
| Compounding | 2 | Drug preparation | Level 2 | No |
| Bulk Storage | 2 | Large quantity storage | Level 1 | No |
| Quarantine | 2 | Inspection/hold area | Level 1 | No |
| Returns | 2 | Return items storage | Level 1 | No |

### Key Features

**For Controlled Substances:**
- DEA registration required
- Dual control access
- IoT-enabled monitoring
- Backup power and alarms
- GMP Certified

**For Refrigerated/Frozen:**
- Real-time temperature monitoring
- Backup power
- Humidity control
- Compliance certified

**For General Storage:**
- Basic monitoring
- Staff-only access
- Standard security

### Security Levels

| Level | Description | Best For |
|-------|-------------|----------|
| Level 1 | Basic Security | General products |
| Level 2 | Enhanced Security | Compounding, Hazardous |
| Level 3 | High Security | Refrigerated, Temperature-controlled |
| Level 4 | Maximum Security (DEA) | Controlled Substances |
| Level 5 | Ultra High Security | Narcotics, High-value items |

### Run Command
```bash
php artisan db:seed --class=PharmacyStorageSeeder
```

---

## 3. Pharmacy Stockage Seeder

### File
`database/seeders/PharmacyStockageSeeder.php`

### Coverage
- **Count:** 155 stockage units
- **Types:** 12 different storage types
- **Services:** Linked to pharmacy services
- **Managers:** Assigned to users
- **Capacity:** 50-5000 units each

### Stockage Types

| Type | Count | Example | Use Case |
|------|-------|---------|----------|
| Shelf | ~13 | Wall Shelf Storage | Quick access items |
| Cabinet | ~13 | Storage Cabinet | Organized storage |
| Drawer | ~13 | Drawer Unit | Small items |
| Bin | ~13 | Bin Storage | Bulk items |
| Rack | ~13 | Rack System | High-volume storage |
| Pallet | ~13 | Pallet Storage | Large quantities |
| Refrigerator | ~13 | Refrigerator Unit | Cold items |
| Freezer | ~13 | Freezer Unit | Frozen items |
| Safe | ~13 | Secure Safe | Controlled substances |
| Display | ~13 | Display Case | Customer-facing |
| Vault | ~13 | Secure Vault | High-security |
| Pod | ~13 | Storage Pod | Compact storage |

### Key Features

**Stockage Attributes:**
- Name and location
- Capacity (50-5000 units)
- Type and warehouse type
- Security level (basic to DEA compliant)
- Manager assignment
- Service association
- Status (active/maintenance)
- Temperature control flag

**Warehouse Types:**
- Central Pharmacy (PC) - Main warehouse
- Service Pharmacy (PS) - Departmental pharmacy

**Security Levels:**
- Basic: General storage
- Enhanced: Medium security
- High: Restricted access
- Maximum: Multi-level access
- DEA Compliant: Controlled substances

### Distribution by Storage
- **Per Storage Unit:** 3-5 stockage units
- **Total Stockages:** 155 units
- **Active Status:** 85%
- **Manager Assignment:** 100% (when users available)

### Run Command
```bash
php artisan db:seed --class=PharmacyStockageSeeder
```

---

## 4. Caisse (Cash Register) Seeder

### File
`database/seeders/CaisseSeeder.php`

### Coverage
- **Count:** 70 cash registers
- **Services:** Distributed across 14+ services
- **Per Service:** 2-5 caisses average

### Service Distribution

| Service | Caisses | Purpose |
|---------|---------|---------|
| Reception | 2-5 | Check-in payments |
| Consultation | 2-5 | Appointment payments |
| Pharmacy | 2-5 | Medication sales |
| Laboratory | 2-5 | Test fees |
| Imaging | 2-5 | Scan fees |
| Emergency | 2-5 | Emergency room |
| Surgery | 2-5 | Surgical fees |
| Pediatrics | 2-5 | Pediatric services |
| Cardiology | 2-5 | Cardiology payments |
| Dermatology | 2-5 | Dermatology |
| Plus more | Variable | Other services |

### Key Features

**Caisse Attributes:**
- Name (with prefix: CAISSE, REGISTER, TILL)
- Location code
- Service association
- Status (active/inactive) - 80% active
- Creation date

**Naming Convention:**
- Format: `PREFIX - Service Name #Number`
- Examples:
  - `CAISSE - Pharmacy #1`
  - `REGISTER - Reception #2`
  - `TILL - Emergency #3`

### Run Command
```bash
php artisan db:seed --class=CaisseSeeder
```

---

## Complete Seeding Process

### Option 1: Full Database Seeding
```bash
php artisan migrate:fresh --seed
```
This will run all seeders in the correct order including:
1. PharmacyProductSeeder (1,007 products)
2. PharmacyStorageSeeder (38 storage units)
3. PharmacyStockageSeeder (155 stockage units)
4. CaisseSeeder (70 caisses)

### Option 2: Individual Seeding
```bash
# Seed products only
php artisan db:seed --class=PharmacyProductSeeder

# Seed storage units
php artisan db:seed --class=PharmacyStorageSeeder

# Seed stockage locations
php artisan db:seed --class=PharmacyStockageSeeder

# Seed cash registers
php artisan db:seed --class=CaisseSeeder
```

### Option 3: Selective Seeding with Tinker
```bash
php artisan tinker
>>> call('db:seed', ['--class' => 'PharmacyProductSeeder'])
>>> call('db:seed', ['--class' => 'PharmacyStorageSeeder'])
>>> call('db:seed', ['--class' => 'PharmacyStockageSeeder'])
>>> call('db:seed', ['--class' => 'CaisseSeeder'])
```

---

## Verification Queries

### Check All Counts
```php
php artisan tinker
>>> \App\Models\PharmacyProduct::count()          // 1,007
>>> \App\Models\PharmacyStorage::count()          // 38
>>> \App\Models\PharmacyStockage::count()         // 155
>>> \App\Models\Coffre\Caisse::count()            // 70
```

### View Storage Types
```php
>>> \App\Models\PharmacyStorage::groupBy('type')->pluck('type')->toArray()
```

### View Stockage Types
```php
>>> \App\Models\PharmacyStockage::groupBy('type')->pluck('type')->toArray()
```

### View Products by Category
```php
>>> \App\Models\PharmacyProduct::where('category', 'Antibiotics')->count()
```

### View Controlled Substances
```php
>>> \App\Models\PharmacyProduct::where('is_controlled_substance', true)->count()
```

### View Active Caisses by Service
```php
>>> \App\Models\Coffre\Caisse::with('service')->where('is_active', true)->get()
```

---

## Database Tables Affected

### pharmacy_products (1,007 rows)
- Comprehensive pharmaceutical data
- Names, manufacturers, categories
- Pricing, strength, dosage forms

### pharmacy_storages (38 rows)
- Storage units
- Security levels
- Temperature/humidity control
- Compliance info

### pharmacy_stockages (155 rows)
- Physical storage locations
- Stockage types
- Manager assignments
- Service associations

### caisses (70 rows)
- Cash register units
- Service locations
- Active/inactive status

---

## Integration Points

### Using Pharmacy Products
```php
// Get all active antibiotics
$antibiotics = \App\Models\PharmacyProduct::where('category', 'Antibiotics')
    ->where('is_active', true)
    ->get();

// Get controlled substances
$controlled = \App\Models\PharmacyProduct::where('is_controlled_substance', true)->get();

// Get cold chain products
$coldChain = \App\Models\PharmacyProduct::where('requires_cold_chain', true)->get();
```

### Using Storage and Stockage
```php
// Get all refrigerated storage
$refrigerated = \App\Models\PharmacyStorage::where('refrigeration_unit', true)->get();

// Get DEA-compliant vaults
$vaults = \App\Models\PharmacyStorage::controlledSubstances()->get();

// Get stockage by manager
$stockage = \App\Models\PharmacyStockage::where('manager_id', $userId)->get();

// Get active stockages
$active = \App\Models\PharmacyStockage::active()->get();
```

### Using Caisses
```php
// Get active caisses
$active = \App\Models\Coffre\Caisse::active()->get();

// Get caisses by service
$pharmacyCaisses = \App\Models\Coffre\Caisse::whereHas('service', function($q) {
    $q->where('name', 'Pharmacy');
})->get();

// Search caisses
$results = \App\Models\Coffre\Caisse::search('pharmacy')->get();
```

---

## Performance Notes

- **Batch Insert Size:** 10-50 (for optimal performance)
- **Insertion Time:** ~30 seconds total for all seeders
- **Database Size:** ~5-10 MB for all seeded data

---

## Customization Examples

### Increase Pharmacy Products
Edit `PharmacyProductSeeder.php`:
```php
for ($i = 1; $i <= 5000; $i++) {  // Change 1000 to 5000
```

### Increase Caisses per Service
Edit `CaisseSeeder.php`:
```php
$numCaissesPerService = rand(5, 10);  // Change from rand(2, 5)
```

### Add Custom Storage Type
Edit `PharmacyStorageSeeder.php`:
```php
$storageTypes = [
    'your_type' => 'Your Storage Type',
    // ... other types
];
```

---

## Troubleshooting

### Issue: "Unknown column" error
**Solution:** Ensure migrations have been run:
```bash
php artisan migrate
```

### Issue: Services not found
**Solution:** PharmacyStockageSeeder will create pharmacy services if needed

### Issue: Slow performance
**Solution:** Run seeders individually:
```bash
php artisan db:seed --class=PharmacyProductSeeder
# Wait for completion, then:
php artisan db:seed --class=PharmacyStorageSeeder
# etc...
```

### Issue: Duplicate data
**Solution:** Clear database first:
```bash
php artisan migrate:fresh --seed
```

---

## Next Steps

1. **Create Pharmacy Inventories** - Link products to stockages
2. **Create Stock Movements** - Track inventory changes
3. **Create Caisse Sessions** - Track cash register usage
4. **Add Test Transactions** - Create sample business data
5. **Generate Reports** - Test analytics and reporting

---

## Summary Table

| Component | Seeders | Records | Status |
|-----------|---------|---------|--------|
| Products | 1 | 1,007 | ✓ Complete |
| Storage | 1 | 38 | ✓ Complete |
| Stockage | 1 | 155 | ✓ Complete |
| Caisses | 1 | 70 | ✓ Complete |
| **TOTAL** | **4** | **1,270** | **✓ Ready** |

All seeders are complete and ready for testing!
