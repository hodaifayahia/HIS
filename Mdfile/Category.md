# Category Model Documentation

## Overview
The Category model represents product categories in the Hospital Information System (HIS). It provides classification and organization for medical products, equipment, and supplies.

## Database Table
**Table Name:** `categories`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Category name |
| `description` | text | Category description |

## Model Relationships

### Products
- **Type:** One-to-Many (hasMany)
- **Description:** A category can have multiple products
- **Related Model:** `App\Models\Product`
- **Foreign Key:** `category_id` (in products table)

## Common Categories
- Medical Equipment
- Pharmaceuticals
- Surgical Instruments
- Diagnostic Tools
- Laboratory Supplies
- Emergency Equipment
- Rehabilitation Equipment
- Dental Supplies
- Orthopedic Devices
- Cardiology Equipment
- Radiology Supplies
- Anesthesia Equipment
- Wound Care
- Infection Control
- Patient Monitoring

## Factory Usage Example

```php
// Create a single category
$category = Category::factory()->create();

// Create multiple categories
$categories = Category::factory()->count(10)->create();

// Create category with specific attributes
$category = Category::factory()->create([
    'name' => 'Surgical Instruments',
    'description' => 'Tools and instruments used in surgical procedures'
]);
```

## Seeder Usage Example

```php
// In CategorySeeder.php
public function run(): void
{
    // Create 15 categories (limited by unique names in factory)
    Category::factory()->count(15)->create();
}
```

## Usage in Tests

```php
public function test_category_can_have_products()
{
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    
    $this->assertTrue($category->products->contains($product));
}

public function test_category_creation()
{
    $category = Category::factory()->create([
        'name' => 'Test Category'
    ]);
    
    $this->assertEquals('Test Category', $category->name);
    $this->assertDatabaseHas('categories', [
        'name' => 'Test Category'
    ]);
}
```

## API Endpoints
- `GET /api/categories` - List all categories
- `POST /api/categories` - Create new category
- `GET /api/categories/{id}` - Get specific category
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category
- `GET /api/categories/{id}/products` - Get category's products

## Validation Rules
```php
'name' => 'required|string|max:255|unique:categories,name',
'description' => 'nullable|string'
```

## Business Logic
- Categories provide hierarchical organization for products
- Unique category names prevent duplication
- Categories help in inventory management and reporting
- Product classification aids in procurement and stock management
- Categories can be used for filtering and searching products

## Usage in Inventory Management
- **Product Classification:** Groups similar items together
- **Reporting:** Generate reports by category
- **Procurement:** Organize purchasing by category
- **Stock Management:** Monitor inventory levels by category
- **Search and Filter:** Enable category-based product searches