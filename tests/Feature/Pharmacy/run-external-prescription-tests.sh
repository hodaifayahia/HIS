#!/bin/bash

# External Prescription Test Runner
# Quick commands to run the comprehensive test suite

echo "=== External Prescription Comprehensive Test Suite ==="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

case "${1:-all}" in
  all)
    echo -e "${BLUE}Running all External Prescription tests...${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --verbose
    ;;
  
  create)
    echo -e "${BLUE}Running: Create prescription with mixed products${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_create_external_prescription_with_mixed_products --verbose
    ;;
  
  edit)
    echo -e "${BLUE}Running: Edit quantity and confirm${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_edit_prescription_item_quantity_and_confirm --verbose
    ;;
  
  dispense)
    echo -e "${BLUE}Running: Dispense and cancel items${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_dispense_item_and_cancel_another --verbose
    ;;
  
  pdf)
    echo -e "${BLUE}Running: Generate PDF${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_generate_prescription_pdf --verbose
    ;;
  
  verify)
    echo -e "${BLUE}Running: Verify dispensed item in service${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_verify_dispensed_item_in_service --verbose
    ;;
  
  workflow)
    echo -e "${BLUE}Running: Complete workflow test${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_complete_external_prescription_workflow --verbose
    ;;
  
  summary)
    echo -e "${BLUE}Running: Prescription summary after operations${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_prescription_summary_after_operations --verbose
    ;;
  
  coverage)
    echo -e "${BLUE}Running tests with coverage report...${NC}"
    php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --coverage
    ;;
  
  *)
    echo -e "${YELLOW}Usage: $0 [command]${NC}"
    echo ""
    echo "Commands:"
    echo "  all          - Run all tests"
    echo "  create       - Create prescription with mixed products"
    echo "  edit         - Edit quantity and confirm"
    echo "  dispense     - Dispense and cancel items"
    echo "  pdf          - Generate PDF"
    echo "  verify       - Verify dispensed item in service"
    echo "  workflow     - Complete workflow test"
    echo "  summary      - Prescription summary after operations"
    echo "  coverage     - Run with coverage report"
    echo ""
    echo "Examples:"
    echo "  $0 all"
    echo "  $0 create"
    echo "  $0 dispense"
    ;;
esac
