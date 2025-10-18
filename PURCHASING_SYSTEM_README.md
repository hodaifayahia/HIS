# Service Demand Management System

This comprehensive backend solution provides a complete system for managing service demand orders with supplier assignments and facture proforma generation.

## Features Implemented

### 1. Backend Infrastructure
- **ServiceDemandItemFournisseur Model**: Pivot model for assigning suppliers to service demand items with quantity tracking
- **Enhanced ServiceDemandPurchasingController**: Added methods for supplier assignment, bulk operations, and facture proforma generation
- **FactureProforma System**: Complete facture proforma management with product tracking
- **API Routes**: Comprehensive RESTful API for all operations

### 2. Database Structure
- **service_demand_item_fournisseurs**: Manages supplier assignments to individual items
- **factureproformas**: Purchase order documents
- **factureproforma_products**: Products within each facture proforma

### 3. Vue.js Frontend Components
- **ServiceDemandsList**: Main list view with filtering and search
- **ServiceDemandDetail**: Detailed view with supplier assignment interface
- **SupplierAssignmentForm**: Form for assigning suppliers to items
- **ServiceDemandForm**: Create new service demands
- **BulkSupplierAssignment**: Bulk assignment interface (placeholder)
- **FactureProformaForm**: Generate purchase orders (placeholder)

## API Endpoints

### Service Demands
```
GET /api/service-demands - List all service demands
POST /api/service-demands - Create new service demand
GET /api/service-demands/{id} - Get specific service demand with assignments
PUT /api/service-demands/{id} - Update service demand
DELETE /api/service-demands/{id} - Delete service demand

# Status Management
POST /api/service-demands/{id}/send - Send service demand

# Item Management
POST /api/service-demands/{id}/items - Add item to demand
PUT /api/service-demands/{id}/items/{itemId} - Update item
DELETE /api/service-demands/{id}/items/{itemId} - Remove item

# Supplier Assignment
POST /api/service-demands/{id}/items/{itemId}/assign-fournisseur - Assign supplier to item
POST /api/service-demands/{id}/bulk-assign-fournisseurs - Bulk assign suppliers
PUT /api/service-demands/{id}/items/{itemId}/assignments/{assignmentId} - Update assignment
DELETE /api/service-demands/{id}/items/{itemId}/assignments/{assignmentId} - Remove assignment

# Facture Proforma Creation
POST /api/service-demands/{id}/create-facture-proforma - Create facture proforma from assignments
GET /api/service-demands/{id}/assignment-summary - Get assignment summary

# Helper Endpoints
GET /api/service-demands/meta/services - Get available services
GET /api/service-demands/meta/products - Get available products
GET /api/service-demands/meta/fournisseurs - Get available suppliers
GET /api/service-demands/meta/stats - Get demand statistics
```

### Facture Proforma
```
GET /api/facture-proformas - List all facture proformas
POST /api/facture-proformas - Create new facture proforma
GET /api/facture-proformas/{id} - Get specific facture proforma
PUT /api/facture-proformas/{id} - Update facture proforma
DELETE /api/facture-proformas/{id} - Delete facture proforma

# Operations
POST /api/facture-proformas/{id}/send - Send to supplier
POST /api/facture-proformas/{id}/mark-as-paid - Mark as paid
GET /api/facture-proformas/{id}/download - Download PDF

# Helper Endpoints
GET /api/facture-proformas/service-demands - Get service demands for selection
GET /api/facture-proformas/suppliers - Get suppliers
GET /api/facture-proformas/products - Get products
GET /api/facture-proformas/stats - Get statistics
```

## Key Features

### 1. Service Demand Management
- Create and manage service demands
- Add products to demands with quantities
- Track demand status (draft, sent, approved, rejected)
- Filter by service, status, date range

### 2. Supplier Assignment System
- Assign suppliers to individual products
- Track assigned quantities vs. required quantities
- Support partial assignments across multiple suppliers
- Bulk assignment capabilities
- Assignment status tracking (pending, confirmed, ordered, received)

### 3. Quantity Management
- Display quantities by item or by box
- Prevent over-assignment of quantities
- Track remaining quantities to be assigned
- Visual indicators for assignment status

### 4. Facture Proforma Generation
- Create purchase order documents from assignments
- Group products by supplier automatically
- PDF generation capability (placeholder)
- Track facture proforma status
- Integration with supplier assignments

### 5. User Interface Features
- Modern Vue.js + PrimeVue + TailwindCSS design
- Responsive layout for all screen sizes
- Real-time filtering and search
- Interactive tables with sorting and pagination
- Modal dialogs for detailed operations
- Toast notifications for user feedback
- Confirmation dialogs for destructive actions

## Usage Instructions

### 1. Accessing the System
Navigate to the purchasing section in the sidebar to access service demand management.

### 2. Creating Service Demands
1. Click "New Service Demand" button
2. Select service and expected date
3. Add notes if needed
4. Submit to create demand

### 3. Adding Products to Demands
1. Open a service demand in detail view
2. Use "Add Item" functionality (from existing controller)
3. Specify quantities and notes

### 4. Assigning Suppliers
1. For each product in a demand, click "Assign Supplier"
2. Select supplier from dropdown
3. Enter quantity to assign (cannot exceed remaining quantity)
4. Add unit price and notes
5. Submit assignment

### 5. Bulk Operations
1. Select multiple service demands from the list
2. Use "Create Facture Proforma" to generate purchase orders
3. Or use bulk supplier assignment for efficiency

### 6. Generating Purchase Orders
1. Ensure items have supplier assignments
2. Click "Create Facture Proforma" from demand detail
3. Select supplier and assigned items
4. Generate purchase order document

## Database Relationships

```
ServiceDemendPurchcing (Service Demand)
├── ServiceDemendPurchcingItem (Demand Items)
│   ├── Product
│   └── ServiceDemandItemFournisseur (Supplier Assignments)
│       └── Fournisseur (Supplier)
└── FactureProforma (Purchase Orders)
    └── FactureProformaProduct (Purchase Order Items)
        └── Product
```

## Technical Implementation

### Models
- **ServiceDemandItemFournisseur**: Pivot model with quantity tracking and pricing
- **Enhanced ServiceDemendPurchcingItem**: Added relationships and computed attributes
- **FactureProforma**: Purchase order management with status tracking
- **FactureProformaProduct**: Individual items in purchase orders

### Controllers
- **ServiceDemandPurchasingController**: Enhanced with supplier assignment methods
- **FactureProformaController**: Complete CRUD and PDF generation

### Vue Components
- Modern Vue 3 Composition API
- PrimeVue component library for consistent UI
- TailwindCSS for responsive styling
- Reactive data management
- Error handling and user feedback

## Deployment Notes

1. Run migrations to create the new database tables:
   ```bash
   php artisan migrate
   ```

2. Ensure all required dependencies are installed:
   - Vue.js 3
   - PrimeVue
   - TailwindCSS
   - date-fns for date formatting

3. Add the service demands route to your main router configuration

4. Ensure proper authentication and authorization for the purchasing module

## Future Enhancements

1. **PDF Generation**: Implement actual PDF generation for facture proformas
2. **Email Integration**: Send facture proformas directly to suppliers
3. **Approval Workflow**: Add approval steps for assignments and purchase orders
4. **Inventory Integration**: Connect with inventory management system
5. **Reporting**: Advanced analytics and reporting capabilities
6. **Mobile App**: Mobile interface for field operations
7. **Barcode Scanning**: Integration with barcode scanning for efficiency

## Security Considerations

- All operations require authentication
- Role-based access control should be implemented
- Input validation on both frontend and backend
- SQL injection prevention through Eloquent ORM
- XSS prevention through proper data escaping

## Support

For issues or questions regarding this implementation, please refer to the development team or check the API documentation for detailed endpoint specifications.