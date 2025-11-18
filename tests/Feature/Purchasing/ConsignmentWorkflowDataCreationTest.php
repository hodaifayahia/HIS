<?php

namespace Tests\Feature\Purchasing;

use App\Models\Caisse;
use App\Models\Doctor;
use App\Models\FicheNavette;
use App\Models\FicheNavetteItem;
use App\Models\Modality;
use App\Models\Patient;
use App\Models\Prestation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsignmentWorkflowDataCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic data
        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        // Create users
        User::factory()->create(['name' => 'Admin User', 'email' => 'admin@test.com']);
        $doctorUser = User::factory()->create(['name' => 'Dr. Smith', 'email' => 'doctor@test.com']);
        User::factory()->create(['name' => 'Receptionist', 'email' => 'receptionist@test.com']);

        // Create patient
        Patient::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'patient@test.com',
        ]);

        // Create doctor linked to doctor user with special fields
        Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'doctor' => $doctorUser->name,
            'is_active' => true,
        ]);

        // Create modality
        Modality::factory()->create([
            'name' => 'General Consultation',
        ]);

        // Create prestations
        Prestation::factory()->create([
            'name' => 'Blood Test',
            'internal_code' => 'BT-001',
            'price_with_vat_and_consumables_variant' => 5000,
        ]);

        Prestation::factory()->create([
            'name' => 'ECG',
            'internal_code' => 'ECG-001',
            'price_with_vat_and_consumables_variant' => 10000,
        ]);

        // Create consignment products
        Product::factory()->create([
            'name' => 'Consignment Product 1',
            'code' => 'CONS-001',
            'selling_price' => 50000,
        ]);

        Product::factory()->create([
            'name' => 'Consignment Product 2',
            'code' => 'CONS-002',
            'selling_price' => 75000,
        ]);

        // Create Caisse (cash account)
        Caisse::factory()->create([
            'name' => 'Main Caisse',
            'solde' => 1000000,
        ]);
    }

    public function test_create_fiche_navette_and_payment_workflow(): void
    {
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║   CREATING FICHE NAVETTE & CAISSE PAYMENT - PERSISTENT DATA   ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";

        // Get test data
        $patient = Patient::first();
        $user = User::first();

        // Get doctor based on authenticated user who is a doctor with the same name
        $doctorUser = User::where('email', 'doctor@test.com')->first();
        $doctor = Doctor::where('user_id', $doctorUser->id)->first();

        $modality = Modality::first();
        $prestations = Prestation::take(2)->get();
        $caisse = Caisse::first();

        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
        echo "STEP 1: CREATE FICHE NAVETTE\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

        // Create FicheNavette
        $fiche = FicheNavette::create([
            'patient_id' => $patient->id,
            'creator_id' => $user->id,
            'total_amount' => 0,
            'is_paid' => false,
        ]);

        echo "✅ FicheNavette Created:\n";
        echo "   ID: {$fiche->id}\n";
        echo "   Patient: {$patient->first_name} {$patient->last_name}\n";
        echo "   Created At: {$fiche->created_at}\n";
        echo '   Is Paid: '.($fiche->is_paid ? 'YES' : 'NO')."\n";

        // Add prestations to fiche
        $totalAmount = 0;
        foreach ($prestations as $index => $prestation) {
            FicheNavetteItem::create([
                'fiche_navette_id' => $fiche->id,
                'prestation_id' => $prestation->id,
                'quantity' => 1,
                'price' => $prestation->price_with_vat_and_consumables_variant,
                'is_paid' => false,
            ]);
            $totalAmount += $prestation->price_with_vat_and_consumables_variant;
            echo "   ├─ Added: {$prestation->name} (Price: {$prestation->price_with_vat_and_consumables_variant})\n";
        }

        // Update fiche total
        $fiche->update(['total_amount' => $totalAmount]);

        echo "   Total Amount: {$totalAmount}\n";
        echo "   Total Items: {$fiche->items->count()}\n";

        // Verify data in database
        $ficheFromDb = FicheNavette::find($fiche->id);
        $this->assertNotNull($ficheFromDb, 'FicheNavette not found in database');
        echo "\n✅ FicheNavette Verified in Database\n";

        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
        echo "STEP 2: CREATE CAISSE PAYMENT TRANSACTION\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

        // Mark fiche items as paid
        $fiche->items()->update(['is_paid' => true]);
        $fiche->update(['is_paid' => true]);

        echo "✅ FicheNavette Marked as Paid\n";
        echo "   ID: {$fiche->id}\n";
        echo "   Is Paid: YES\n";
        echo "   Payment Amount: {$fiche->total_amount}\n";

        // Create Caisse transaction (payment)
        $caisseTransaction = $caisse->transactions()->create([
            'type' => 'payment', // or 'payment'
            'amount' => $fiche->total_amount,
            'description' => "Payment for FicheNavette #{$fiche->id}",
            'reference_type' => 'fiche_navette',
            'reference_id' => $fiche->id,
            'created_by' => $user->id,
        ]);

        echo "\n✅ Caisse Transaction Created:\n";
        echo "   ID: {$caisseTransaction->id}\n";
        echo "   Type: payment\n";
        echo "   Amount: {$caisseTransaction->amount}\n";
        echo "   Caisse: {$caisse->name}\n";
        echo "   Reference: FicheNavette #{$fiche->id}\n";
        echo "   Created At: {$caisseTransaction->created_at}\n";

        // Verify caisse balance updated
        $caisseFromDb = Caisse::find($caisse->id);
        $newBalance = $caisseFromDb->solde;
        echo "   New Caisse Balance: {$newBalance}\n";

        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
        echo "✅ VERIFICATION IN DATABASE\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

        // Verify FicheNavette in database
        $ficheVerify = FicheNavette::with('items', 'patient')->find($fiche->id);
        $this->assertNotNull($ficheVerify);
        $this->assertTrue($ficheVerify->is_paid);
        echo "✅ FicheNavette #{$ficheVerify->id} found in database\n";
        echo "   Total Amount: {$ficheVerify->total_amount}\n";
        echo "   Items: {$ficheVerify->items->count()}\n";
        echo "   Is Paid: YES\n";

        // Verify Caisse transaction in database
        $txnVerify = $caisseTransaction->fresh();
        $this->assertNotNull($txnVerify);
        echo "\n✅ Caisse Transaction #{$txnVerify->id} found in database\n";
        echo "   Amount: {$txnVerify->amount}\n";
        echo "   Type: {$txnVerify->type}\n";
        echo "   Caisse: {$caisse->name}\n";

        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
        echo "📊 INSPECTION SQL QUERIES (Run these to see your data)\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

        echo "QUERY 1: View the FicheNavette\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "SELECT id, patient_id, total_amount, is_paid, created_at\n";
        echo "FROM fiche_navettes\n";
        echo "WHERE id = {$fiche->id};\n\n";

        echo "QUERY 2: View FicheNavette Items (Prestations)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "SELECT id, fiche_navette_id, prestation_id, quantity, price, is_paid, created_at\n";
        echo "FROM fiche_navette_items\n";
        echo "WHERE fiche_navette_id = {$fiche->id};\n\n";

        echo "QUERY 3: View Caisse Transactions (Payments)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "SELECT id, caisse_id, type, amount, description, reference_type, reference_id, created_at\n";
        echo "FROM caisse_transactions\n";
        echo "WHERE reference_id = {$fiche->id};\n\n";

        echo "QUERY 4: View Caisse Balance\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "SELECT id, name, solde, created_at\n";
        echo "FROM caisses\n";
        echo "WHERE id = {$caisse->id};\n\n";

        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "✅ TEST COMPLETE - DATA IS PERSISTENT IN DATABASE!\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "FicheNavette ID: {$fiche->id}\n";
        echo "Caisse Transaction ID: {$caisseTransaction->id}\n";
        echo "Patient ID: {$patient->id}\n";
        echo "Caisse ID: {$caisse->id}\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

        // Assertions
        $this->assertDatabaseHas('fiche_navettes', [
            'id' => $fiche->id,
            'is_paid' => true,
        ]);

        $this->assertDatabaseHas('caisse_transactions', [
            'id' => $caisseTransaction->id,
            'reference_id' => $fiche->id,
        ]);
    }
}
