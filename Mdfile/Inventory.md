# Inventory Model Documentation

## Overview
The Inventory model represents stock items in the Hospital Information System (HIS). It manages product quantities, locations, batch tracking, and expiry dates across different storage locations.

## Database Table
**Table Name:** `inventories`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `product_id` | integer | Foreign key to products table |
| `stockage_id` | integer | Foreign key to stockages table |
| `quantity` | decimal(2) | Current quantity in stock |
| `total_units` | decimal(2) | Total units available |
| `unit` | string | Unit of measurement |
| `batch_number` | string | Batch/lot number |
| `serial_number` | string | Serial number (optional) |
| `purchase_price` | decimal(2) | Purchase price per unit |
| `barcode` | string | Product barcode |
| `expiry_date` | date | Product expiry date |
| `location` | string | Specific storage location |

## Model Relationships

### Product
- **Type:** Many-to-One (belongsTo)
- **Description:** Each inventory item belongs to one product
- **Related Model:** `App\Models\Product`
- **Foreign Key:** `product_id`

### Stockage
- **Type:** Many-to-One (belongsTo)
- **Description:** Each inventory item is stored in one stockage location
- **Related Model:** `App\Models\Stockage`
- **Foreign Key:** `stockage_id`

## Type Casting
- `quantity` → decimal:2
- `total_units` → decimal:2
- `purchase_price` → decimal:2
- `expiry_date` → date

## Units of Measurement
- `pieces` - Individual items
- `boxes` - Boxed items
- `bottles` - Liquid containers
- `vials` - Small containers
- `tubes` - Tube containers
- `packets` - Packaged items
- `strips` - Strip packaging

## Factory Usage Example

```php
// Create a single inventory item
$inventory = Inventory::factory()->create();

// Create low stock items
$lowStockItems = Inventory::factory()->lowStock()->count(20)->create();

// Create expired items
$expiredItems = Inventory::factory()->expired()->count(10)->create();

// Create inventory for specific product
$inventory = Inventory::factory()->create([
    'product_id' => 1,
    'quantity' => 100,
    'unit' => 'boxes'
]);
```

## Seeder Usage Example

```php
// In InventorySeeder.php
public function run(): void
{
    // Create 150 normal stock items
    Inventory::factory()->count(150)->create();
    
    // Create 30 low stock items
    Inventory::factory()->lowStock()->count(30)->create();
    
    // Create 20 expired items
    Inventory::factory()->expired()->count(20)->create();
}
```

## Usage in Tests

```php
public function test_inventory_belongs_to_product()
{
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create(['product_id' => $product->id]);
    
    $this->assertEquals($product->id, $inventory->product->id);
}

public function test_low_stock_detection()
{
    $inventory = Inventory::factory()->lowStock()->create();
    
    $this->assertLessThan(10, $inventory->quantity);
}

public function test_expiry_date_tracking()
{
    $inventory = Inventory::factory()->expired()->create();
    
    $this->assertLessThan(now(), $inventory->expiry_date);
}
```

## API Endpoints
- `GET /api/inventory` - List all inventory items
- `POST /api/inventory` - Create new inventory item
- `GET /api/inventory/{id}` - Get specific inventory item
- `PUT /api/inventory/{id}` - Update inventory item
- `DELETE /api/inventory/{id}` - Delete inventory item
- `GET /api/inventory/low-stock` - Get low stock items
- `GET /api/inventory/expired` - Get expired items
- `GET /api/inventory/expiring-soon` - Get items expiring soon

## Validation Rules
```php
'product_id' => 'required|exists:products,id',
'stockage_id' => 'required|exists:stockages,id',
'quantity' => 'required|numeric|min:0',
'total_units' => 'required|numeric|min:0',
'unit' => 'required|string|max:50',
'batch_number' => 'required|string|max:100',
'serial_number' => 'nullable|string|max:100',
'purchase_price' => 'required|numeric|min:0',
'barcode' => 'nullable|string|max:100',
'expiry_date' => 'required|date|after:today',
'location' => 'required|string|max:100'
```

## Business Logic
- **Stock Management:** Track quantities across multiple locations
- **Batch Tracking:** Monitor product batches for quality control
- **Expiry Management:** Alert for expired or expiring products
- **Location Tracking:** Know exact storage locations
- **Cost Tracking:** Monitor purchase prices for financial reporting
- **Barcode Integration:** Support for barcode scanning systems

## Inventory States
- **Normal Stock:** Adequate quantities available
- **Low Stock:** Quantities below minimum threshold
- **Out of Stock:** Zero quantity available
- **Expired:** Past expiry date
- **Expiring Soon:** Approaching expiry date

## Integration Points
- **Purchase Orders:** Update inventory upon receipt
- **Dispensing:** Reduce quantities when items are used
- **Stock Movements:** Track transfers between locations
- **Reporting:** Generate stock reports and analytics
- **Alerts:** Notify for low stock and expiring items