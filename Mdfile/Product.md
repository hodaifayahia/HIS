# Product Model Documentation

## Overview
The Product model represents medical products, medications, and supplies in the Hospital Information System (HIS). It manages product information, categorization, and approval workflows.

## Database Table
**Table Name:** `products`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Product name |
| `description` | text | Detailed product description |
| `category` | string | Product category |
| `is_clinical` | boolean | Whether product is for clinical use |
| `is_required_approval` | boolean | Whether product requires approval |
| `is_request_approval` | boolean | Whether approval is requested |
| `code_interne` | integer | Internal product code |
| `code_pch` | string | PCH (Pharmacy) code |
| `code` | string | General product code |
| `designation` | string | Product designation |
| `type_medicament` | string | Type of medication |
| `forme` | string | Product form (oral, injectable, etc.) |
| `boite_de` | integer | Package quantity |
| `nom_commercial` | string | Commercial name |
| `status` | string | Product status (active, inactive, discontinued) |

## Model Relationships

### Inventories
- **Type:** One-to-Many (hasMany)
- **Description:** A product can have multiple inventory entries
- **Related Model:** `App\Models\Inventory`

### Pharmacy Inventories
- **Type:** One-to-Many (hasMany)
- **Description:** A product can have multiple pharmacy inventory entries
- **Related Model:** `App\Models\PharmacyInventory`

### Stockages
- **Type:** Many-to-Many (belongsToMany)
- **Description:** Products can be stored in multiple stockage locations
- **Related Model:** `App\Models\Stockage`
- **Pivot Table:** `inventories`

## Model Features

### Type Casting
- `is_clinical` → boolean
- `is_required_approval` → boolean
- `is_request_approval` → boolean
- `code_interne` → integer
- `boite_de` → integer

### Product Categories
Common categories include:
- Antibiotique
- Antalgique
- Anti-inflammatoire
- Cardiovasculaire
- Neurologique

### Medication Types
- Comprimé
- Gélule
- Sirop
- Injectable
- Pommade
- Gouttes

## Factory Usage Example

```php
// Create a single product
$product = Product::factory()->create();

// Create active products
$activeProducts = Product::factory()->active()->count(50)->create();

// Create clinical products
$clinicalProducts = Product::factory()->clinical()->count(20)->create();

// Create products requiring approval
$approvalProducts = Product::factory()->requiresApproval()->count(15)->create();

// Create product with specific attributes
$product = Product::factory()->create([
    'name' => 'Paracetamol 500mg',
    'category' => 'Antalgique',
    'is_clinical' => true
]);
```

## Seeder Usage Example

```php
// In ProductSeeder.php
public function run(): void
{
    // Create 60 active products
    Product::factory()->active()->count(60)->create();
    
    // Create 20 clinical products
    Product::factory()->clinical()->count(20)->create();
    
    // Create 15 products requiring approval
    Product::factory()->requiresApproval()->count(15)->create();
    
    // Create 5 products with random states
    Product::factory()->count(5)->create();
}
```

## Usage in Tests

```php
public function test_product_can_be_clinical()
{
    $product = Product::factory()->clinical()->create();
    
    $this->assertTrue($product->is_clinical);
}

public function test_product_requires_approval()
{
    $product = Product::factory()->requiresApproval()->create();
    
    $this->assertTrue($product->is_required_approval);
    $this->assertTrue($product->is_request_approval);
}

public function test_product_has_inventories()
{
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create(['product_id' => $product->id]);
    
    $this->assertTrue($product->inventories->contains($inventory));
}
```

## API Endpoints
- `GET /api/products` - List all products
- `POST /api/products` - Create new product
- `GET /api/products/{id}` - Get specific product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product
- `GET /api/products/{id}/inventory` - Get product inventory
- `GET /api/products/clinical` - Get clinical products only

## Validation Rules
```php
'name' => 'required|string|max:255',
'description' => 'nullable|string',
'category' => 'required|string|max:100',
'code_interne' => 'required|integer|unique:products,code_interne',
'code' => 'required|string|max:50|unique:products,code',
'designation' => 'required|string|max:255',
'type_medicament' => 'required|string|max:100',
'forme' => 'required|string|max:100',
'boite_de' => 'required|integer|min:1',
'nom_commercial' => 'required|string|max:255',
'status' => 'required|in:active,inactive,discontinued'
```

## Business Logic
- Clinical products require special handling and approval
- Products with approval requirements go through workflow processes
- Internal codes must be unique across the system
- Package quantities determine dispensing units
- Status controls product availability in the system