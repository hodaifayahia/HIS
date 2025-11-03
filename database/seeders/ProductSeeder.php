<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the products table with 5000 realistic medical products.
     */
    public function run(): void
    {
        // Product data arrays
        $categories = ['Medical Supplies', 'Equipment', 'Medication', 'Others'];
        $medicamentTypes = [
            'Antibiotics', 'Analgesics', 'Antihistamines', 'Antihypertensives', 'Antidiabetics',
            'Corticosteroids', 'Antivirals', 'Antifungals', 'Antiinflammatories', 'Bronchodilators',
            'Anticoagulants', 'Antiplatelet', 'Beta Blockers', 'ACE Inhibitors', 'Statins',
            'Anticonvulsants', 'Antidepressants', 'Antipsychotics', 'Anxiolytics', 'Sedatives',
            'Stimulants', 'Antipyretics', 'Muscle Relaxants', 'Immunoglobulins', 'Vaccines'
        ];

        $formes = [
            'Tablet', 'Capsule', 'Injection', 'Infusion', 'Oral Suspension',
            'Ointment', 'Cream', 'Gel', 'Patch', 'Inhaler', 'Nasal Spray',
            'Eye Drops', 'Ear Drops', 'Solution', 'Syrup', 'Powder', 'Suppository',
            'Implant', 'Lotion', 'Foam', 'Liquid', 'Granules'
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

        $strengths = ['250mg', '500mg', '1g', '2g', '5mg', '10mg', '20mg', '50mg', '100mg',
                     '200mg', '5%', '10%', '20%', '0.5%', '1%', '15mg/ml', '25mg/ml'];

        $statuses = ['In Stock', 'Low Stock', 'Out of Stock'];

        $batchSize = 250;
        $totalProducts = 5000;
        $products = [];

        for ($i = 1; $i <= $totalProducts; $i++) {
            $generic = $genericDrugs[array_rand($genericDrugs)];
            $strength = $strengths[array_rand($strengths)];
            $isClinic = rand(0, 100) < 60 ? 1 : 0; // 60% clinical products
            $isMedication = rand(0, 100) < 70 ? 1 : 0; // 70% are medications

            $category = $isMedication ? 'Medication' : $categories[array_rand($categories)];

            $products[] = [
                'name' => $generic . ' ' . $strength,
                'description' => 'High quality ' . strtolower($generic) . ' ' . strtolower($strength) . ' - suitable for clinical use. Brand: ' . $brandNames[array_rand($brandNames)],
                'category' => $category,
                'is_clinical' => $isClinic,
                'code_interne' => $i,
                'code_pch' => 'PCH-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'designation' => $generic . ' Pharmaceutical Product',
                'type_medicament' => $isMedication ? $medicamentTypes[array_rand($medicamentTypes)] : null,
                'forme' => $isMedication ? $formes[array_rand($formes)] : null,
                'code' => 'PROD-' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'boite_de' => $isMedication ? [10, 20, 30, 50, 100, 200, 500, 1000][array_rand([10, 20, 30, 50, 100, 200, 500, 1000])] : null,
                'quantity_by_box' => rand(1, 100),
                'nom_commercial' => $brandNames[array_rand($brandNames)],
                'status' => $statuses[array_rand($statuses)],
                'is_request_approval' => rand(0, 100) < 20 ? 1 : 0, // 20% require approval
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches
            if (count($products) === $batchSize || $i === $totalProducts) {
                try {
                    Product::insert($products);
                    $progressPercent = round(($i / $totalProducts) * 100);
                    echo "Progress: {$progressPercent}% ({$i}/{$totalProducts} products seeded)\n";
                } catch (\Exception $e) {
                    echo "Error at product {$i}: " . $e->getMessage() . "\n";
                    throw $e;
                }
                $products = [];
            }
        }

        echo "✓ Successfully seeded {$totalProducts} products!\n";
    }
}