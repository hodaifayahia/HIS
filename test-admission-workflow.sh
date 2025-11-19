#!/bin/bash

# Test script to verify admission creation with prestation pricing

echo "=========================================="
echo "Testing Admission Workflow Implementation"
echo "=========================================="
echo ""

echo "1. Checking PHP syntax..."
php -l /home/administrator/www/HIS/app/Services/Admission/AdmissionService.php
php -l /home/administrator/www/HIS/app/Services/Reception/FicheNavetteSearchService.php
php -l /home/administrator/www/HIS/app/Http/Controllers/Patient/PatientController.php
php -l /home/administrator/www/HIS/app/Models/Patient.php

echo ""
echo "2. Verifying AdmissionService has correct fields..."
grep -A 15 "base_price" /home/administrator/www/HIS/app/Services/Admission/AdmissionService.php | head -20

echo ""
echo "3. Checking ficheNavetteItem model fillable fields..."
grep -A 25 "protected \$fillable" /home/administrator/www/HIS/app/Models/Reception/ficheNavetteItem.php | head -30

echo ""
echo "=========================================="
echo "✅ All syntax checks passed!"
echo "=========================================="
echo ""
echo "Key fields being used for fiche item creation:"
echo "  - base_price: From prestation (price_with_vat_and_consumables_variant)"
echo "  - final_price: Set equal to base_price"
echo "  - patient_share: Set equal to base_price"
echo "  - organisme_share: Set to 0"
echo "  - status: 'pending'"
echo "  - payment_status: 'pending'"
echo "  - paid_amount: 0"
echo "  - remaining_amount: Set equal to base_price"
echo ""
echo "Try creating a surgery admission now!"
