<?php

namespace Database\Seeders;

use App\Models\PharmacyProduct;
use Illuminate\Database\Seeder;

class PharmacyProductSeeder extends Seeder
{
    /**
     * Seed the pharmacy products table with 5000 realistic pharmaceutical products.
     */
    public function run(): void
    {
        // Common pharmaceutical data
        $categories = [
            'Antibiotics', 'Analgesics', 'Antihistamines', 'Antihypertensives',
            'Antidiabetics', 'Corticosteroids', 'Antivirals', 'Antifungals',
            'Antiinflammatories', 'Bronchodilators', 'Anticoagulants', 'Antiplatelet',
            'Beta Blockers', 'ACE Inhibitors', 'Statins', 'Anticonvulsants',
            'Antidepressants', 'Antipsychotics', 'Anxiolytics', 'Sedatives',
            'Stimulants', 'Antipyretics', 'Muscle Relaxants', 'Proton Pump Inhibitors',
            'H2 Blockers', 'Laxatives', 'Antidiarrheals', 'Antiemetics',
            'Vaccines', 'Immunoglobulins', 'Chelating Agents', 'Vitamins',
            'Minerals', 'Probiotics', 'Antiparasitics', 'Antimalarials'
        ];

        $manufacturers = [
            'Pfizer', 'Novartis', 'Johnson & Johnson', 'Roche', 'Merck',
            'AbbVie', 'Amgen', 'Bristol Myers Squibb', 'Eli Lilly', 'AstraZeneca',
            'Gilead Sciences', 'Regeneron', 'Moderna', 'BioNTech', 'GSK',
            'Sanofi', 'Takeda', 'Bayer', 'Boehringer Ingelheim', 'Allergan',
            'Teva Pharmaceutical', 'Mylan', 'Hikma Pharmaceuticals', 'Sandoz',
            'Cipla', 'Dr. Reddy\'s', 'Lupin', 'Sun Pharma', 'Ranbaxy', 'Aurobindo'
        ];

        $suppliers = [
            'ABC Pharma Supply', 'MedDirect', 'HealthCare Solutions', 'Pharma Logistics',
            'Global Health Supplies', 'Premium Med Supplies', 'QuickMed Distribution',
            'Reliable Pharma', 'Elite Medical Supply', 'Professional Health Services'
        ];

        $dosageForms = [
            'Tablet', 'Capsule', 'Injection', 'Infusion', 'Oral Suspension',
            'Ointment', 'Cream', 'Gel', 'Patch', 'Inhaler', 'Nasal Spray',
            'Eye Drops', 'Ear Drops', 'Solution', 'Syrup', 'Powder', 'Suppository',
            'Implant', 'Lotion', 'Foam', 'Liquid', 'Granules'
        ];

        $routesOfAdmin = [
            'Oral', 'Intravenous', 'Intramuscular', 'Subcutaneous', 'Topical',
            'Inhalation', 'Intranasal', 'Ophthalmic', 'Otic', 'Rectal', 'Transdermal'
        ];

        $strength = ['250mg', '500mg', '1g', '2g', '5mg', '10mg', '20mg', '50mg', '100mg',
                     '200mg', '5%', '10%', '20%', '0.5%', '1%', '15mg/ml', '25mg/ml'];

        $therapeuticClasses = [
            'Cardiovascular', 'Respiratory', 'Gastrointestinal', 'Endocrine',
            'Neurological', 'Psychiatric', 'Immunological', 'Infectious Disease',
            'Dermatological', 'Rheumatological', 'Oncological', 'Hematological'
        ];

        $genericDrugs = [
            'Paracetamol', 'Ibuprofen', 'Aspirin', 'Amoxicillin', 'Azithromycin',
            'Ciprofloxacin', 'Metformin', 'Atorvastatin', 'Lisinopril', 'Amlodipine',
            'Omeprazole', 'Cimetidine', 'Loratadine', 'Cetirizine', 'Enalapril',
            'Ramipril', 'Hydrochlorothiazide', 'Furosemide', 'Spironolactone', 'Digoxin',
            'Warfarin', 'Clopidogrel', 'Acetylsalicylic acid', 'Ticlopidine', 'Heparin',
            'Enoxaparin', 'Fluoxetine', 'Sertraline', 'Paroxetine', 'Citalopram',
            'Diazepam', 'Lorazepam', 'Alprazolam', 'Clonazepam', 'Phenytoin',
            'Valproic acid', 'Levetiracetam', 'Gabapentin', 'Pregabalin', 'Carbamazepine',
            'Prednisone', 'Dexamethasone', 'Methylprednisolone', 'Betamethasone', 'Hydrocortisone',
            'Insulin', 'Glibenclamide', 'Glipizide', 'Sitagliptin', 'Pioglitazone'
        ];

        $brandNames = [
            'Tylenol', 'Advil', 'Asprin', 'Amoxil', 'Zithromax', 'Cipro', 'Glucophage',
            'Lipitor', 'Prinivil', 'Norvasc', 'Prilosec', 'Tagamet', 'Claritin',
            'Zyrtec', 'Vasotec', 'Altace', 'HydroDiuril', 'Lasix', 'Aldactone',
            'Lanoxin', 'Coumadin', 'Plavix', 'Ecotrin', 'Ticlid', 'Fragmin',
            'Lovenox', 'Prozac', 'Zoloft', 'Paxil', 'Celexa', 'Valium', 'Ativan',
            'Xanax', 'Klonopin', 'Dilantin', 'Depakote', 'Keppra', 'Neurontin',
            'Lyrica', 'Tegretol', 'Deltasone', 'Decadron', 'Solu-Medrol', 'Celestone',
            'Cortef', 'Humulin', 'Euglucan', 'Glyburide', 'Januvia', 'Actos'
        ];

        $controlledSchedules = ['Schedule I', 'Schedule II', 'Schedule III', 'Schedule IV', 'Schedule V'];

        $atcCodes = [
            'A01AA01', 'A01AB11', 'A01AC02', 'A02BA01', 'A02BC01', 'A03AA04', 'A04AA02',
            'A06AB65', 'A07AA02', 'A07DA03', 'A08AA01', 'A09AA02', 'A10AA02', 'A10AB01',
            'A10AC01', 'A10AD04', 'A10BA02', 'A10BB12', 'A10BG02', 'A10BX03', 'A11AA07',
            'A11CC05', 'A11DA01', 'A11HA04', 'A12AA04', 'A12AX', 'A13AA04', 'A14AA02',
            'A16AX01', 'A16AB02', 'A16AB09', 'B01AA03', 'B01AB01', 'B01AC06', 'B01AE07',
            'B01BA01', 'B01BB01', 'B02AB01', 'B02AC01', 'B02BA01', 'B03AA01', 'B03AD01'
        ];

        $storageConditions = [
            'Room temperature (15-25°C)',
            'Cool place (2-15°C)',
            'Refrigerated (2-8°C)',
            'Frozen (-20°C or below)',
            'Store in dry place',
            'Protect from light'
        ];

        // Batch insert for better performance
        $batchSize = 250;
        $totalProducts = 5000;
        $products = [];
        
        // Valid ENUM values for unit_of_measure
        $unitEnumValues = ['tablet', 'capsule', 'ml', 'mg', 'g', 'kg', 'piece', 'box', 'vial', 'ampoule', 'syringe', 'bottle', 'tube', 'patch', 'other'];

        for ($i = 1; $i <= $totalProducts; $i++) {
            $generic = $genericDrugs[array_rand($genericDrugs)];
            $strength_str = $strength[array_rand($strength)];
            $isControlled = rand(0, 100) < 15; // 15% controlled substances

            $products[] = [
                'name' => $generic . ' ' . $strength_str,
                'generic_name' => $generic,
                'brand_name' => $brandNames[array_rand($brandNames)],
                'barcode' => null, // Allow NULL for unique constraint
                'sku' => null, // Allow NULL for unique constraint
                'category' => $categories[array_rand($categories)],
                'description' => 'Pharmaceutical product: ' . $generic . ' - ' . $strength_str . '. High quality product suitable for clinical use.',
                'manufacturer' => $manufacturers[array_rand($manufacturers)],
                'supplier' => $suppliers[array_rand($suppliers)],
                'unit_of_measure' => $unitEnumValues[array_rand($unitEnumValues)], // Use valid ENUM value
                'strength' => (float)filter_var($strength_str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                'strength_unit' => preg_replace('/[0-9.,]/', '', $strength_str),
                'dosage_form' => $dosageForms[array_rand($dosageForms)],
                'route_of_administration' => $routesOfAdmin[array_rand($routesOfAdmin)],
                'active_ingredients' => json_encode([$generic]),
                'inactive_ingredients' => json_encode(['Cellulose', 'Silicon dioxide', 'Magnesium stearate']),
                'is_controlled_substance' => $isControlled,
                'controlled_substance_schedule' => $isControlled ? $controlledSchedules[array_rand($controlledSchedules)] : null,
                'storage_temperature_min' => (float)rand(2, 15),
                'storage_temperature_max' => (float)rand(20, 30),
                'storage_humidity_min' => (float)rand(30, 50),
                'storage_humidity_max' => (float)rand(60, 80),
                'storage_conditions' => $storageConditions[array_rand($storageConditions)],
                'requires_cold_chain' => rand(0, 1) < 30 ? true : false, // 30% need cold chain
                'light_sensitive' => rand(0, 1) < 40 ? true : false, // 40% light sensitive
                'shelf_life_days' => rand(90, 1825), // 3 months to 5 years
                'contraindications' => 'See full prescribing information',
                'side_effects' => json_encode(['Common side effects may vary by patient', 'Consult healthcare provider']),
                'drug_interactions' => json_encode(['Check with pharmacist before use', 'Report all medications']),
                'warnings' => 'Use as directed. Not for use in children under 2 years.',
                'precautions' => 'Pregnancy Category: Consult prescriber. Nursing: Use caution.',
                'unit_cost' => (float)rand(50, 5000) / 100,
                'selling_price' => (float)rand(100, 10000) / 100,
                'markup_percentage' => (float)rand(20, 100),
                'therapeutic_class' => $therapeuticClasses[array_rand($therapeuticClasses)],
                'pharmacological_class' => 'See product information',
                'atc_code' => $atcCodes[array_rand($atcCodes)],
                'ndc_number' => rand(10000, 99999) . '-' . rand(1000, 9999) . '-' . rand(10, 99),
                'requires_prescription' => rand(0, 1) ? 'yes' : 'no',
                'lot_number' => 'LOT-' . strtoupper(bin2hex(random_bytes(4))),
                'expiry_date' => now()->addMonths(rand(6, 36))->toDateString(),
                'is_active' => rand(0, 1) < 95 ? true : false, // 95% active
                'is_discontinued' => false,
                'regulatory_info' => json_encode(['FDA Approved', 'WHO Listed']),
                'quality_control_info' => json_encode(['GMP Certified', 'ISO 9001:2015']),
                'packaging_info' => json_encode(['Blister packs', 'Bottles', 'Vials']),
                'labeling_info' => json_encode(['Full prescribing information included', 'Multiple language support']),
                'notes' => 'Batch ' . $i . ' - Quality assured pharmaceutical product',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches
            if (count($products) === $batchSize || $i === $totalProducts) {
                try {
                    PharmacyProduct::insert($products);
                    $progressPercent = round(($i / $totalProducts) * 100);
                    echo "Progress: {$progressPercent}% ({$i}/{$totalProducts} products seeded)\n";
                } catch (\Exception $e) {
                    echo "Error at product {$i}: " . $e->getMessage() . "\n";
                    throw $e;
                }
                $products = [];
            }
        }

        echo "✓ Successfully seeded {$totalProducts} pharmacy products!\n";
    }
}
