# Pharmacy Product Reservation Seeding Guide

**Date:** November 1, 2025  
**Purpose:** Populate ProductReserve table with pharmacy product reservations

---

## 📋 Overview

This guide provides two seeders for populating the `product_reserves` table with pharmacy product reservations:

1. **PharmacyProductReservationSeeder** - Seeds limited realistic data (~25 reservations)
2. **GeneratePharmacyReservationsSeeder** - Generates large datasets (up to 1000+)

---

## ✅ Prerequisites

Before running the seeders, ensure these tables are populated:

- ✅ `pharmacy_products` - Run `PharmacyProductSeeder`
- ✅ `pharmacy_stockages` - Run `PharmacyStockageSeeder`
- ✅ `services` - Run `ServiceSeeder`
- ✅ `users` - Run `UserSeeder` or `DatabaseSeeder`

### Check Prerequisites:
```bash
# Check if pharmacy products exist
php artisan tinker
>>> App\Models\PharmacyProduct::count()
>>> App\Models\PharmacyStockage::count()
>>> App\Models\CONFIGURATION\Service::count()
```

---

## 🚀 Running the Seeders

### Option 1: Run via DatabaseSeeder (Recommended)

Run all seeders in correct order:

```bash
php artisan db:seed
```

This will automatically run:
- PharmacyProductSeeder
- PharmacyStockageSeeder
- PharmacyProductReservationSeeder ✅ (newly added)
- And all other seeders

### Option 2: Run Single Seeder (Limited Data)

For a quick test with ~25 reservations:

```bash
php artisan db:seed --class=PharmacyProductReservationSeeder
```

**Output:**
```
✅ Inserted 21 pharmacy product reservations
✅ Successfully seeded 21 pharmacy product reservations!
📊 Pharmacy Reservation Statistics:
   Total: 21
   ✓ Pending: 8 (38.1%)
   ✓ Fulfilled: 9 (42.9%)
   ✓ Cancelled: 3 (14.3%)
   ✓ Expired: 1 (4.8%)
```

### Option 3: Generate Large Dataset (500+ Records)

For comprehensive testing and demonstration:

```bash
php artisan db:seed --class=GeneratePharmacyReservationsSeeder
```

**Output:**
```
🚀 Generating 300 pharmacy product reservations...
  ✅ Inserted 100 reservations (100/300)
  ✅ Inserted 100 reservations (200/300)
  ✅ Inserted 100 reservations (300/300)
✅ Successfully generated 300 pharmacy product reservations!

📊 Pharmacy Reservation Statistics:
   Total: 300
   ✓ Pending:   138 (46.0%)
   ✓ Fulfilled: 237 (79.0%)
   ✓ Cancelled: 27 (9.0%)
   ✓ Expired:   18 (6.0%)

🏆 Top 5 Most Reserved Products:
   1. Paracetamol 500mg - 15 reservations
   2. Ibuprofen 400mg - 12 reservations
   3. Amoxicillin 500mg - 11 reservations
   ...

🏢 Top 5 Services by Reservation Count:
   1. General Surgery - 45 reservations
   2. Pediatrics - 38 reservations
   ...
```

---

## 📊 Data Structure

### What Gets Created

Each reservation includes:

| Field | Example | Description |
|-------|---------|-------------|
| `reservation_code` | RES-1234ABCD | Unique reservation identifier |
| `pharmacy_product_id` | 42 | Link to pharmacy product |
| `pharmacy_stockage_id` | 5 | Source stockage |
| `reserved_by` | 3 | User ID who created reservation |
| `quantity` | 50 | Number of units reserved |
| `status` | pending, fulfilled, cancelled, expired | Reservation status |
| `reserved_at` | 2025-11-01 10:00:00 | When reserved |
| `expires_at` | 2025-11-30 23:59:59 | When reservation expires |
| `fulfilled_at` | 2025-11-15 14:30:00 | When delivered (if fulfilled) |
| `source` | pharmacy | Always 'pharmacy' |
| `destination_service_id` | 2 | Service receiving the medication |
| `reservation_notes` | Urgent - high priority | Optional notes |
| `meta` | JSON: priority, batch, etc. | Additional metadata |

---

## 🎯 Seeder Comparison

| Feature | PharmacyProductReservationSeeder | GeneratePharmacyReservationsSeeder |
|---------|-----------------------------------|-----------------------------------|
| **Records** | ~25 | 300+ (configurable) |
| **Status Distribution** | Realistic mix | Realistic mix |
| **Time to Run** | < 1 second | 3-5 seconds |
| **Use Case** | Quick test | Production testing |
| **Customizable** | No | Via command line |

---

## 🔧 Customization

### Generate Custom Number of Reservations

```bash
# Generate exactly 500 reservations
php artisan db:seed --class=GeneratePharmacyReservationsSeeder --count=500

# Generate 1000 reservations
php artisan db:seed --class=GeneratePharmacyReservationsSeeder --count=1000
```

### Modify Seeder Code

Edit the seeders to customize:

1. **Status Distribution** (line ~60)
   ```php
   $statusRandom = rand(1, 100);
   if ($statusRandom <= 50) {          // 50% pending
       $status = 'pending';
   } elseif ($statusRandom <= 85) {    // 35% fulfilled
       $status = 'fulfilled';
   }
   ```

2. **Quantity Range** (line ~75)
   ```php
   'quantity' => rand(1, 100),  // Change to rand(1, 500)
   ```

3. **Date Range** (lines ~70-71)
   ```php
   $reserved_at = $now->copy()->subDays(rand(1, 90));    // Last 90 days
   $expires_at = $reserved_at->copy()->addDays(rand(7, 45)); // 7-45 days later
   ```

---

## 🧪 Testing the Seeded Data

### Check Seeded Reservations

```bash
php artisan tinker

# Count pharmacy reservations
>>> App\Models\ProductReserve::where('source', 'pharmacy')->count()
300

# Get pending reservations
>>> App\Models\ProductReserve::where('source', 'pharmacy')
    ->where('status', 'pending')->count()
138

# Get fulfilled ones
>>> App\Models\ProductReserve::where('source', 'pharmacy')
    ->where('status', 'fulfilled')
    ->with('pharmacyProduct', 'pharmacyStockage', 'reserver')
    ->first()

# View specific reservation
>>> $res = App\Models\ProductReserve::find(1);
>>> $res->pharmacyProduct->name
>>> $res->reserver->name
>>> $res->destinationService->name
```

### API Testing

```bash
# Get all pharmacy reservations
curl "http://localhost/api/product-reserves?source=pharmacy"

# Get pending pharmacy reservations
curl "http://localhost/api/product-reserves?source=pharmacy&status=pending"

# Filter by product
curl "http://localhost/api/product-reserves?source=pharmacy&pharmacy_product_id=42"

# Filter by service
curl "http://localhost/api/product-reserves?source=pharmacy&destination_service_id=2"
```

---

## 📈 Query Examples

### Find All Pharmacy Product Reservations

```php
$reservations = ProductReserve::where('source', 'pharmacy')
    ->with(['pharmacyProduct', 'pharmacyStockage', 'destinationService', 'reserver'])
    ->paginate(15);
```

### Get Pending Pharmacy Reservations

```php
$pending = ProductReserve::where('source', 'pharmacy')
    ->where('status', 'pending')
    ->whereDate('expires_at', '>=', now())
    ->orderBy('reserved_at', 'asc')
    ->get();
```

### Get Reservations by Product

```php
$productReservations = ProductReserve::where('source', 'pharmacy')
    ->where('pharmacy_product_id', $productId)
    ->orderBy('reserved_at', 'desc')
    ->get();
```

### Get Expiring Reservations

```php
$expiring = ProductReserve::where('source', 'pharmacy')
    ->where('status', 'pending')
    ->whereBetween('expires_at', [now(), now()->addDays(7)])
    ->get();
```

### Get Fulfilled Reservations This Month

```php
$thisMonth = ProductReserve::where('source', 'pharmacy')
    ->where('status', 'fulfilled')
    ->whereMonth('fulfilled_at', now()->month)
    ->whereYear('fulfilled_at', now()->year)
    ->get();
```

---

## 🗑️ Clearing Seeded Data

### Delete All Pharmacy Reservations

```bash
php artisan tinker

# Delete all
>>> App\Models\ProductReserve::where('source', 'pharmacy')->delete()

# Delete specific range
>>> App\Models\ProductReserve::where('source', 'pharmacy')
    ->whereDate('created_at', '>=', '2025-11-01')
    ->delete()
```

### Reset via Migration

```bash
# Full reset (loses all data)
php artisan migrate:refresh

# Then re-seed
php artisan db:seed
```

---

## 📝 File Locations

- **Seeder 1:** `database/seeders/PharmacyProductReservationSeeder.php`
- **Seeder 2:** `database/seeders/GeneratePharmacyReservationsSeeder.php`
- **Main Seeder:** `database/seeders/DatabaseSeeder.php` (updated)

---

## ⚡ Performance Notes

| Operation | Time | Records |
|-----------|------|---------|
| PharmacyProductReservationSeeder | < 1 sec | ~25 |
| GeneratePharmacyReservationsSeeder | 3-5 sec | 300 |
| GeneratePharmacyReservationsSeeder | 30-60 sec | 1000 |
| GeneratePharmacyReservationsSeeder | 2-3 min | 5000 |

### Optimize Large Datasets

For 5000+ records, increase batch size:

```php
// In GeneratePharmacyReservationsSeeder.php
$batchSize = 500;  // Default: 100
```

---

## 🐛 Troubleshooting

### Error: "No active pharmacy products found"

**Solution:** Run PharmacyProductSeeder first
```bash
php artisan db:seed --class=PharmacyProductSeeder
```

### Error: "No pharmacy stockages found"

**Solution:** Run PharmacyStockageSeeder first
```bash
php artisan db:seed --class=PharmacyStockageSeeder
```

### Error: "Foreign key constraint failed"

**Solution:** Run all seeders in order via DatabaseSeeder
```bash
php artisan db:seed
```

### Duplicate Reservations After Re-running

**Solution:** Truncate table before re-seeding
```bash
php artisan tinker
>>> DB::table('product_reserves')->truncate()
```

---

## ✅ Verification Checklist

After running seeders, verify:

- [x] Reservations are created in database
- [x] All have `source = 'pharmacy'`
- [x] Status distribution is realistic (pending ~40-50%)
- [x] Foreign key relationships intact
- [x] Timestamps are realistic (not all today)
- [x] Can query via API endpoints
- [x] UI shows reservations correctly

---

## 🎓 Learning Resources

### Model Relationships

See `app/Models/ProductReserve.php` for:
- `pharmacyProduct()` - The product being reserved
- `pharmacyStockage()` - Source stockage
- `destinationService()` - Service receiving it
- `reserver()` - User who created reservation

### Database Schema

See migration for `product_reserves` table structure and constraints.

---

## 📞 Support

If seeders fail:

1. Check prerequisites are seeded
2. Verify database connection
3. Check foreign key constraints are enabled
4. Run: `php artisan migrate:fresh` and retry

---

**Happy seeding!** 🌱
