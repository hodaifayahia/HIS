# BonCommend Model Documentation

## Overview
The BonCommend model represents purchase orders in the Hospital Information System (HIS). It manages the procurement process from order creation to delivery confirmation.

## Database Table
**Table Name:** `bon_commends`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `bonCommendCode` | string | Unique purchase order code |
| `fournisseur_id` | integer | Foreign key to suppliers table |
| `service_demand_purchasing_id` | integer | Related service demand ID |
| `order_date` | date | Date when order was placed |
| `expected_delivery_date` | date | Expected delivery date |
| `department` | string | Requesting department |
| `priority` | string | Order priority (low, medium, high, urgent) |
| `notes` | text | Additional order notes |
| `created_by` | integer | User who created the order |
| `status` | string | Order status |
| `approval_status` | string | Approval workflow status |
| `has_approver_modifications` | boolean | Whether approver made modifications |
| `price` | decimal | Total order price |
| `pdf_content` | text | Generated PDF content |
| `pdf_generated_at` | timestamp | PDF generation timestamp |
| `is_confirmed` | boolean | Whether order is confirmed |
| `confirmed_at` | timestamp | Confirmation timestamp |
| `confirmed_by` | integer | User who confirmed |
| `boncommend_confirmed_at` | timestamp | Order confirmation timestamp |
| `attachments` | json | Order attachments |

## Model Relationships

### Fournisseur (Supplier)
- **Type:** Many-to-One (belongsTo)
- **Description:** Each order belongs to one supplier
- **Related Model:** `App\Models\Fournisseur`
- **Foreign Key:** `fournisseur_id`

### Service Demand Purchasing
- **Type:** Many-to-One (belongsTo)
- **Description:** Each order relates to a service demand
- **Related Model:** `App\Models\ServiceDemendPurchcing`
- **Foreign Key:** `service_demand_purchasing_id`

### Creator
- **Type:** Many-to-One (belongsTo)
- **Description:** User who created the order
- **Related Model:** `App\Models\User`
- **Foreign Key:** `created_by`

### Order Items
- **Type:** One-to-Many (hasMany)
- **Description:** An order can have multiple items
- **Related Model:** `App\Models\BonCommendItem`

## Order Statuses
- `pending` - Order created, awaiting processing
- `approved` - Order approved for procurement
- `rejected` - Order rejected
- `completed` - Order fulfilled and delivered
- `cancelled` - Order cancelled

## Priority Levels
- `low` - Standard priority
- `medium` - Moderate priority
- `high` - High priority
- `urgent` - Emergency procurement

## Approval Statuses
- `pending` - Awaiting approval
- `approved` - Approved for processing
- `rejected` - Approval denied
- `under_review` - Currently being reviewed

## Factory Usage Example

```php
// Create a single bon commend
$bonCommend = BonCommend::factory()->create();

// Create approved orders
$approvedOrders = BonCommend::factory()->approved()->count(20)->create();

// Create pending orders
$pendingOrders = BonCommend::factory()->pending()->count(15)->create();

// Create order with specific attributes
$order = BonCommend::factory()->create([
    'priority' => 'urgent',
    'department' => 'Emergency',
    'status' => 'approved'
]);
```

## Seeder Usage Example

```php
// In BonCommendSeeder.php
public function run(): void
{
    // Create 40 approved orders
    BonCommend::factory()->approved()->count(40)->create();
    
    // Create 35 pending orders
    BonCommend::factory()->pending()->count(35)->create();
    
    // Create 25 orders with random states
    BonCommend::factory()->count(25)->create();
}
```

## Usage in Tests

```php
public function test_bon_commend_belongs_to_supplier()
{
    $supplier = Fournisseur::factory()->create();
    $order = BonCommend::factory()->create(['fournisseur_id' => $supplier->id]);
    
    $this->assertEquals($supplier->id, $order->fournisseur->id);
}

public function test_approved_order_is_confirmed()
{
    $order = BonCommend::factory()->approved()->create();
    
    $this->assertEquals('approved', $order->status);
    $this->assertTrue($order->is_confirmed);
}
```

## API Endpoints
- `GET /api/bon-commends` - List all purchase orders
- `POST /api/bon-commends` - Create new purchase order
- `GET /api/bon-commends/{id}` - Get specific order
- `PUT /api/bon-commends/{id}` - Update order
- `DELETE /api/bon-commends/{id}` - Delete order
- `POST /api/bon-commends/{id}/approve` - Approve order
- `POST /api/bon-commends/{id}/confirm` - Confirm order
- `GET /api/bon-commends/{id}/pdf` - Generate order PDF

## Validation Rules
```php
'bonCommendCode' => 'required|string|unique:bon_commends,bonCommendCode',
'fournisseur_id' => 'required|exists:fournisseurs,id',
'order_date' => 'required|date',
'expected_delivery_date' => 'required|date|after:order_date',
'department' => 'required|string|max:255',
'priority' => 'required|in:low,medium,high,urgent',
'status' => 'required|in:pending,approved,rejected,completed,cancelled',
'approval_status' => 'required|in:pending,approved,rejected,under_review',
'price' => 'nullable|numeric|min:0'
```

## Business Logic
- Purchase orders follow approval workflow
- Urgent orders may bypass standard approval process
- Confirmed orders cannot be modified without special permissions
- PDF generation for official documentation
- Attachment support for supporting documents
- Status tracking throughout procurement lifecycle