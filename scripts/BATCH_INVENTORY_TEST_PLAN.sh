#!/bin/bash

# Batch-Level Inventory Testing Script
# This script demonstrates testing the batch inventory implementation

echo "=================================="
echo "Batch-Level Inventory Test Plan"
echo "=================================="
echo ""

echo "1. DATABASE CHECK"
echo "   Verify sub_items column exists:"
echo "   php artisan tinker"
echo "   >>> Schema::hasColumn('bon_entree_items', 'sub_items')"
echo ""

echo "2. CREATE BON ENTREE WITH BATCHES"
echo "   Test API endpoint with curl:"
cat << 'EOF'
   curl -X POST http://your-domain/api/bon-entrees \
   -H "Content-Type: application/json" \
   -H "Authorization: Bearer YOUR_TOKEN" \
   -d '{
     "service_id": 1,
     "fournisseur_id": 1,
     "items": [
       {
         "product_id": 1,
         "quantity": 100,
         "purchase_price": 50,
         "sub_items": [
           {
             "quantity": 40,
             "purchase_price": 50,
             "batch_number": "BATCH001",
             "expiry_date": "2026-12-31",
             "unit": "unit"
           },
           {
             "quantity": 60,
             "purchase_price": 50,
             "batch_number": "BATCH002",
             "expiry_date": "2027-06-30",
             "unit": "unit"
           }
         ]
       }
     ]
   }'
EOF
echo ""
echo ""

echo "3. VERIFY BON ENTREE SAVED"
echo "   Check database:"
echo "   SELECT * FROM bon_entree_items WHERE bon_entree_id = [ID];"
echo "   Verify sub_items JSON column contains batch data"
echo ""

echo "4. VALIDATE BON ENTREE"
cat << 'EOF'
   curl -X POST http://your-domain/api/bon-entrees/[ID]/validate \
   -H "Content-Type: application/json" \
   -H "Authorization: Bearer YOUR_TOKEN" \
   -d '{
     "service_id": 1,
     "storage_id": 1
   }'
EOF
echo ""
echo ""

echo "5. VERIFY INVENTORY CREATED"
echo "   Check that 2 separate inventory records were created:"
echo "   SELECT * FROM inventories WHERE product_id = 1 ORDER BY batch_number;"
echo ""
echo "   Expected result:"
echo "   - Record 1: quantity=40, batch_number='BATCH001', expiry_date='2026-12-31'"
echo "   - Record 2: quantity=60, batch_number='BATCH002', expiry_date='2027-06-30'"
echo ""

echo "6. TEST BATCH MERGING"
echo "   Create another Bon Entrée with same batch:"
cat << 'EOF'
   curl -X POST http://your-domain/api/bon-entrees \
   -H "Content-Type: application/json" \
   -H "Authorization: Bearer YOUR_TOKEN" \
   -d '{
     "service_id": 1,
     "fournisseur_id": 1,
     "items": [
       {
         "product_id": 1,
         "quantity": 30,
         "purchase_price": 50,
         "sub_items": [
           {
             "quantity": 30,
             "purchase_price": 50,
             "batch_number": "BATCH001",
             "expiry_date": "2026-12-31",
             "unit": "unit"
           }
         ]
       }
     ]
   }'
EOF
echo ""
echo ""

echo "7. VALIDATE SECOND BON ENTREE"
echo "   After validation, check inventory:"
echo "   SELECT * FROM inventories WHERE batch_number = 'BATCH001';"
echo ""
echo "   Expected result:"
echo "   - Quantity should be 70 (40 + 30 merged)"
echo ""

echo "8. TEST PHARMACY PRODUCTS"
echo "   Repeat steps 2-5 with pharmacy products:"
echo "   - Use pharmacy_product_id instead of product_id"
echo "   - Verify pharmacy_inventories table is used"
echo "   - Check separate records per batch"
echo ""

echo "9. CHECK LOGS"
echo "   tail -f storage/logs/laravel.log"
echo "   Look for messages:"
echo "   - 'New inventory created for batch'"
echo "   - 'Inventory merged for batch'"
echo "   - 'New pharmacy inventory created for batch'"
echo ""

echo "10. FRONTEND TESTING"
echo "    - Open Bon Entrée Edit page"
echo "    - Add a product"
echo "    - Click 'Manage Batches' button"
echo "    - Add multiple batches with different:"
echo "      * Batch numbers"
echo "      * Expiry dates"
echo "      * Quantities"
echo "    - Save Bon Entrée"
echo "    - Validate Bon Entrée"
echo "    - Check inventory table for separate records"
echo ""

echo "=================================="
echo "Test Plan Complete"
echo "=================================="
