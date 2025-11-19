<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;
use App\Models\Product;
use App\Models\INFRASTRUCTURE\Pavilion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_doctors_with_english_names_and_terminology()
    {
        $doctor = Doctor::factory()->create();
        
        // Verify English names are used
        $this->assertNotEmpty($doctor->user->name);
        $this->assertStringContainsString('Dr.', $doctor->user->name);
        
        // Verify English email domains
        $this->assertStringContainsString('@hospital.com', $doctor->user->email);
        
        // Verify English phone format
        $this->assertStringStartsWith('+1 555', $doctor->user->phone);
        
        // Verify English medical notes
        if ($doctor->notes) {
            $this->assertStringNotContainsString('Spécialiste', $doctor->notes);
            $this->assertStringNotContainsString('médecin', $doctor->notes);
        }
    }

    /** @test */
    public function it_creates_specializations_with_english_terminology()
    {
        $specialization = Specialization::factory()->create();
        
        // Verify English specialization names
        $englishSpecializations = [
            'Cardiology', 'Neurology', 'General Surgery', 'Emergency Medicine',
            'Pediatrics', 'Gynecology-Obstetrics', 'Radiology', 'Laboratory Medicine',
            'Hospital Pharmacy', 'Dermatology', 'Orthopedics', 'Psychiatry',
            'Anesthesia-Intensive Care', 'Internal Medicine', 'Oncology',
            'Hematology', 'Endocrinology', 'Nephrology', 'Gastroenterology',
            'Pulmonology', 'Rheumatology', 'Infectious Diseases', 'Nuclear Medicine',
            'Pathology', 'Geriatrics', 'Palliative Care', 'Occupational Medicine',
            'Physical Medicine and Rehabilitation'
        ];
        
        $this->assertContains($specialization->name, $englishSpecializations);
        
        // Verify English descriptions
        if ($specialization->description) {
            $this->assertStringNotContainsString('Spécialité', $specialization->description);
            $this->assertStringContainsString('specialty', strtolower($specialization->description));
        }
    }

    /** @test */
    public function it_creates_services_with_english_terminology()
    {
        $service = Service::factory()->create();
        
        // Verify English service names
        $englishServices = [
            'Emergency Department', 'Outpatient Consultations', 'Inpatient Services',
            'General Surgery', 'Radiology', 'Laboratory', 'Pharmacy', 'Physiotherapy',
            'Cardiology', 'Neurology', 'Pediatrics', 'Gynecology-Obstetrics',
            'Orthopedics', 'Dermatology', 'Ophthalmology', 'ENT', 'Psychiatry',
            'Anesthesia-Intensive Care', 'Internal Medicine', 'Oncology', 'Hematology',
            'Endocrinology', 'Nephrology', 'Gastroenterology', 'Pulmonology',
            'Rheumatology', 'Infectious Diseases', 'Nuclear Medicine', 'Pathology',
            'Geriatrics', 'Palliative Care', 'Occupational Medicine',
            'Physical Medicine and Rehabilitation'
        ];
        
        $this->assertContains($service->name, $englishServices);
        
        // Verify English descriptions
        if ($service->description) {
            $this->assertStringNotContainsString('Service de', $service->description);
            $this->assertStringContainsString('service', strtolower($service->description));
        }
    }

    /** @test */
    public function test_product_factory_uses_english_terminology()
    {
        $product = Product::factory()->create();
        
        // Test that English categories are used
        $englishCategories = ['Antibiotic', 'Analgesic', 'Anti-inflammatory', 'Cardiovascular', 'Neurological'];
        $this->assertContains($product->category, $englishCategories);
        
        // Test that English medication types are used
        $englishMedicationTypes = ['Tablet', 'Capsule', 'Syrup', 'Injectable', 'Ointment', 'Drops'];
        $this->assertContains($product->type_medicament, $englishMedicationTypes);
        
        // Test that English forms are used
        $englishForms = ['Oral', 'Injectable', 'Topical', 'Ophthalmic', 'Otic'];
        $this->assertContains($product->forme, $englishForms);
        
        // Test that status uses correct enum values
        $validStatuses = ['In Stock', 'Low Stock', 'Out of Stock'];
        $this->assertContains($product->status, $validStatuses);
        
        // Ensure no French terms are present
        $this->assertStringNotContainsString('Comprimé', $product->type_medicament);
        $this->assertStringNotContainsString('Sirop', $product->type_medicament);
        $this->assertStringNotContainsString('Oral', $product->forme);
    }

    /** @test */
    public function it_creates_pavilions_with_english_names()
    {
        // Create pavilions using seeder data
        $pavilionData = [
            'name' => 'Surgery Pavilion',
            'description' => 'Main wing for surgical procedures and post-operative care.'
        ];
        
        $pavilion = Pavilion::create($pavilionData);
        
        // Verify English pavilion names
        $englishPavilionNames = [
            'Surgery Pavilion', 'Mother-Child Pavilion', 'Outpatient Consultations Pavilion',
            'Internal Medicine Pavilion', 'Technical Platform'
        ];
        
        $this->assertContains($pavilion->name, $englishPavilionNames);
        
        // Verify English descriptions
        $this->assertStringNotContainsString('Pavillon', $pavilion->description);
        $this->assertStringContainsString('wing', strtolower($pavilion->description));
    }

    /** @test */
    public function it_verifies_no_french_terminology_in_generated_data()
    {
        // Create multiple instances to test consistency
        $doctors = Doctor::factory()->count(5)->create();
        $specializations = Specialization::factory()->count(5)->create();
        $services = Service::factory()->count(5)->create();
        $products = Product::factory()->count(5)->create();
        
        // Check doctors for French terms
        foreach ($doctors as $doctor) {
            $this->assertStringNotContainsString('Docteur', $doctor->user->name);
            $this->assertStringNotContainsString('@hopital.fr', $doctor->user->email);
            if ($doctor->notes) {
                $this->assertStringNotContainsString('Spécialiste', $doctor->notes);
                $this->assertStringNotContainsString('médecin', $doctor->notes);
            }
        }
        
        // Check specializations for French terms
        foreach ($specializations as $specialization) {
            $this->assertStringNotContainsString('Cardiologie', $specialization->name);
            $this->assertStringNotContainsString('Chirurgie', $specialization->name);
            $this->assertStringNotContainsString('Pédiatrie', $specialization->name);
            if ($specialization->description) {
                $this->assertStringNotContainsString('Spécialité', $specialization->description);
            }
        }
        
        // Check services for French terms
        foreach ($services as $service) {
            $this->assertStringNotContainsString('Urgences', $service->name);
            $this->assertStringNotContainsString('Pharmacie', $service->name);
            $this->assertStringNotContainsString('Laboratoire', $service->name);
        }
        
        // Check products for French terms
        foreach ($products as $product) {
            $this->assertStringNotContainsString('Médicament', $product->type);
            $this->assertStringNotContainsString('Comprimé', $product->forme);
            $this->assertStringNotContainsString('Sirop', $product->forme);
        }
    }
}
