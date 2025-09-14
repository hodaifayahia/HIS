<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Surgical Gloves',
                'description' => 'Sterile latex-free surgical gloves, size M',
                'category' => 'Medical Supplies',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Blood Pressure Monitor',
                'description' => 'Digital automatic blood pressure monitor with memory',
                'category' => 'Equipment',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Paracetamol 500mg',
                'description' => 'Pain relief medication, 100 tablets per pack',
                'category' => 'Medication',
                'is_clinical' => true,
                'code_interne' => 1001,
                'code_pch' => 'PCH-001',
                'designation' => 'Paracetamol',
                'type_medicament' => 'MÉDICAMENT',
                'forme' => 'COMPRIME',
                'boite_de' => 100,
                'nom_commercial' => 'Acetaminophen',
                'status' => 'In Stock'
            ],
            [
                'name' => 'Stethoscope',
                'description' => 'Professional grade stethoscope for medical examination',
                'category' => 'Equipment',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Bandages',
                'description' => 'Sterile adhesive bandages, assorted sizes',
                'category' => 'Medical Supplies',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Amoxicillin 250mg',
                'description' => 'Antibiotic medication, 50 capsules per pack',
                'category' => 'Medication',
                'is_clinical' => true,
                'code_interne' => 1002,
                'code_pch' => 'PCH-002',
                'designation' => 'Amoxicillin',
                'type_medicament' => 'ANTISEPTIQUE',
                'forme' => 'GELULE',
                'boite_de' => 50,
                'nom_commercial' => 'Amoxil',
                'status' => 'In Stock'
            ],
            [
                'name' => 'Ibuprofen 400mg',
                'description' => 'Anti-inflammatory medication, 30 tablets per pack',
                'category' => 'Medication',
                'is_clinical' => true,
                'code_interne' => 1003,
                'code_pch' => 'PCH-003',
                'designation' => 'Ibuprofen',
                'type_medicament' => 'MÉDICAMENT',
                'forme' => 'COMPRIME',
                'boite_de' => 30,
                'nom_commercial' => 'Advil',
                'status' => 'In Stock'
            ],
            [
                'name' => 'Defibrillator Pads',
                'description' => 'Replacement pads for AED defibrillator',
                'category' => 'Medical Supplies',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Office Supplies Kit',
                'description' => 'Stationery and office supplies for administrative use',
                'category' => 'Others',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Cleaning Supplies',
                'description' => 'Disinfectants and cleaning materials for facility maintenance',
                'category' => 'Others',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Patient Gowns',
                'description' => 'Disposable patient gowns for examination rooms',
                'category' => 'Others',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Computer Accessories',
                'description' => 'Keyboards, mice, and other computer peripherals',
                'category' => 'Others',
                'is_clinical' => false,
                'status' => 'In Stock'
            ],
            [
                'name' => 'Aspirin 100mg',
                'description' => 'Cardiovascular medication, 28 tablets per pack',
                'category' => 'Medication',
                'is_clinical' => true,
                'code_interne' => 1004,
                'code_pch' => 'PCH-004',
                'designation' => 'Acetylsalicylic Acid',
                'type_medicament' => 'MÉDICAMENT',
                'forme' => 'COMPRIME',
                'boite_de' => 28,
                'nom_commercial' => 'Bayer Aspirin',
                'status' => 'In Stock'
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
