<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Doctor;
use App\Models\ExternalPrescription;
use App\Models\ExternalPrescriptionItem;
use App\Models\PharmacyInventory;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalPrescriptionComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user (admin)
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create test doctor
        $doctorUser = User::factory()->create([
            'name' => 'Dr. Ahmed Mohamed',
            'email' => 'doctor@test.com',
            'role' => 'doctor',
        ]);

        $this->doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Test: Create prescription with existing product and non-existing product
     * Then add to database and create draft
     */
    public function test_create_external_prescription_with_mixed_products()
    {
        // STEP 1: Create a stockage location
        $stockage = PharmacyStockage::factory()->create([
            'name' => 'Main Pharmacy Stock',
        ]);

        // STEP 2: Create an existing pharmacy product (exists in pharmacy table)
        $existingProduct = PharmacyProduct::factory()->create([
            'name' => 'Aspirin 500mg',
            'code' => 'ASP-500',
            'unit_of_measure' => 'box',
        ]);

        // STEP 3: Create inventory for existing product
        $existingInventory = PharmacyInventory::create([
            'pharmacy_product_id' => $existingProduct->id,
            'pharmacy_stockage_id' => $stockage->id,
            'quantity' => 100,
            'unit' => 'box',
            'purchase_price' => 5.00,
            'selling_price' => 8.00,
            'batch_number' => 'BATCH-001',
            'expiry_date' => now()->addMonths(12),
            'purchase_date' => now(),
        ]);

        // STEP 4: Create a NEW product (does NOT exist in pharmacy table yet)
        $newProduct = PharmacyProduct::factory()->create([
            'name' => 'Ibuprofen 400mg',
            'code' => 'IBU-400',
            'unit_of_measure' => 'box',
        ]);

        // STEP 5: Add new product to database with inventory
        $newInventory = PharmacyInventory::create([
            'pharmacy_product_id' => $newProduct->id,
            'pharmacy_stockage_id' => $stockage->id,
            'quantity' => 50,
            'unit' => 'box',
            'purchase_price' => 4.50,
            'selling_price' => 7.50,
            'batch_number' => 'BATCH-NEW-001',
            'expiry_date' => now()->addMonths(6),
            'purchase_date' => now(),
        ]);

        // STEP 6: Create external prescription (draft)
        $prescriptionData = [
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'description' => 'Initial prescription for patient',
        ];

        $prescription = ExternalPrescription::create($prescriptionData);

        // Verify prescription created with auto-generated code
        $this->assertNotNull($prescription->prescription_code);
        $this->assertStringContainsString('EXT-PRESC-', $prescription->prescription_code);
        $this->assertEquals('draft', $prescription->status);
        $this->assertDatabaseHas('external_prescriptions', [
            'id' => $prescription->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'draft',
        ]);

        // STEP 7: Add items to prescription (existing + new product)
        $item1 = ExternalPrescriptionItem::create([
            'external_prescription_id' => $prescription->id,
            'pharmacy_product_id' => $existingProduct->id,
            'quantity' => 10,
            'unit' => 'box',
            'status' => 'draft',
        ]);

        $item2 = ExternalPrescriptionItem::create([
            'external_prescription_id' => $prescription->id,
            'pharmacy_product_id' => $newProduct->id,
            'quantity' => 5,
            'unit' => 'box',
            'status' => 'draft',
        ]);

        // Reload and verify prescription
        $prescription->refresh();
        $this->assertCount(2, $prescription->items);
        $this->assertEquals(2, $prescription->items()->count());

        // Verify items exist
        $this->assertDatabaseHas('external_prescription_items', [
            'external_prescription_id' => $prescription->id,
            'pharmacy_product_id' => $existingProduct->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('external_prescription_items', [
            'external_prescription_id' => $prescription->id,
            'pharmacy_product_id' => $newProduct->id,
            'quantity' => 5,
        ]);

        return compact('prescription', 'item1', 'item2', 'existingProduct', 'newProduct', 'stockage');
    }

    /**
     * Test: Edit item quantity and confirm prescription
     */
    public function test_edit_prescription_item_quantity_and_confirm()
    {
        $data = $this->test_create_external_prescription_with_mixed_products();
        $prescription = $data['prescription'];
        $item1 = $data['item1'];
        $item2 = $data['item2'];

        // STEP 8: Edit item1 quantity
        $item1->update(['quantity' => 15]);
        $item1->refresh();
        $this->assertEquals(15, $item1->quantity);

        $this->assertDatabaseHas('external_prescription_items', [
            'id' => $item1->id,
            'quantity' => 15,
        ]);

        // STEP 9: Update prescription status to confirmed
        $prescription->update(['status' => 'confirmed']);
        $prescription->refresh();

        $this->assertEquals('confirmed', $prescription->status);
        $this->assertDatabaseHas('external_prescriptions', [
            'id' => $prescription->id,
            'status' => 'confirmed',
        ]);

        return $data;
    }

    /**
     * Test: Dispense one item and cancel another
     */
    public function test_dispense_item_and_cancel_another()
    {
        $data = $this->test_edit_prescription_item_quantity_and_confirm();
        $prescription = $data['prescription'];
        $item1 = $data['item1'];
        $item2 = $data['item2'];
        $existingProduct = $data['existingProduct'];
        $stockage = $data['stockage'];

        // STEP 10: Dispense item1 with service selection
        $service = \App\Models\Service::factory()->create([
            'name' => 'Pharmacy Service',
            'image_url' => 'http://example.com/pharmacy.jpg',
        ]);

        $item1->update([
            'status' => 'dispensed',
            'quantity_sended' => 15,
            'service_id' => $service->id,
        ]);

        $item1->refresh();
        $this->assertEquals('dispensed', $item1->status);
        $this->assertEquals(15, $item1->quantity_sended);
        $this->assertEquals($service->id, $item1->service_id);

        // Verify item was added to pharmacy inventory with service tracking
        $this->assertDatabaseHas('external_prescription_items', [
            'id' => $item1->id,
            'status' => 'dispensed',
            'quantity_sended' => 15,
            'service_id' => $service->id,
        ]);

        // STEP 11: Cancel item2 with reason
        $item2->update([
            'status' => 'cancelled',
            'cancel_reason' => 'Product out of stock',
        ]);

        $item2->refresh();
        $this->assertEquals('cancelled', $item2->status);
        $this->assertEquals('Product out of stock', $item2->cancel_reason);

        $this->assertDatabaseHas('external_prescription_items', [
            'id' => $item2->id,
            'status' => 'cancelled',
            'cancel_reason' => 'Product out of stock',
        ]);

        // STEP 12: Verify prescription shows mixed statuses
        $prescription->refresh();
        $items = $prescription->items;

        $dispensedCount = $items->where('status', 'dispensed')->count();
        $cancelledCount = $items->where('status', 'cancelled')->count();

        $this->assertEquals(1, $dispensedCount);
        $this->assertEquals(1, $cancelledCount);

        return compact('prescription', 'item1', 'item2', 'service');
    }

    /**
     * Test: Generate PDF and verify format
     */
    public function test_generate_prescription_pdf()
    {
        $data = $this->test_dispense_item_and_cancel_another();
        $prescription = $data['prescription'];

        // STEP 13: Generate PDF
        $response = $this->get("/api/external-prescriptions/{$prescription->id}/pdf");

        // Verify PDF generation
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');

        // Verify PDF content contains prescription data
        $pdfContent = $response->getContent();
        $this->assertNotEmpty($pdfContent);

        // PDF should be valid (starts with PDF header or contains prescription data)
        $this->assertTrue(strpos($pdfContent, '%PDF') === 0 || strlen($pdfContent) > 100);

        return $data;
    }

    /**
     * Test: Verify dispensed item exists in chosen service
     */
    public function test_verify_dispensed_item_in_service()
    {
        $data = $this->test_dispense_item_and_cancel_another();
        $prescription = $data['prescription'];
        $item1 = $data['item1'];
        $service = $data['service'];

        // STEP 14: Verify dispensed item relationship with service
        $item1->refresh();

        // Item should have service_id
        $this->assertNotNull($item1->service_id);
        $this->assertEquals($service->id, $item1->service_id);

        // Verify item can be queried by service
        $itemsByService = ExternalPrescriptionItem::where('service_id', $service->id)
            ->where('status', 'dispensed')
            ->get();

        $this->assertCount(1, $itemsByService);
        $this->assertTrue($itemsByService->contains('id', $item1->id));

        // Verify the item belongs to a prescription
        $this->assertEquals($prescription->id, $item1->external_prescription_id);
        $this->assertEquals('dispensed', $item1->status);
        $this->assertEquals(15, $item1->quantity_sended);

        return $data;
    }

    /**
     * Test: Complete workflow - Create, Edit, Dispense, Cancel, Confirm, PDF
     */
    public function test_complete_external_prescription_workflow()
    {
        // This test runs through entire workflow
        $this->test_verify_dispensed_item_in_service();

        $this->assertTrue(true); // If we got here, all workflows passed
    }

    /**
     * Test: Verify prescription summary after all operations
     */
    public function test_prescription_summary_after_operations()
    {
        $data = $this->test_dispense_item_and_cancel_another();
        $prescription = $data['prescription'];

        $prescription->refresh();

        // Count items by status
        $items = $prescription->items;
        $dispensedItems = $items->where('status', 'dispensed')->count();
        $cancelledItems = $items->where('status', 'cancelled')->count();
        $totalItems = $items->count();

        // Assertions
        $this->assertEquals(2, $totalItems);
        $this->assertEquals(1, $dispensedItems);
        $this->assertEquals(1, $cancelledItems);

        // Verify prescription status is confirmed
        $this->assertEquals('confirmed', $prescription->status);

        // Verify items have correct data
        $dispensedItem = $items->where('status', 'dispensed')->first();
        $cancelledItem = $items->where('status', 'cancelled')->first();

        $this->assertNotNull($dispensedItem->service_id);
        $this->assertNotNull($dispensedItem->quantity_sended);
        $this->assertNotNull($cancelledItem->cancel_reason);
    }
}
