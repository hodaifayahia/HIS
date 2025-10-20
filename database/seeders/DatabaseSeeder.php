<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder; // Ensure this is imported

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create base users first
        User::factory(10)->create();

        // Seed in dependency order
        $this->call([
            // Core configuration seeders
            // ServiceSeeder::class,
            SpecializationSeeder::class,
            ModalitySeeder::class,
            PavilionSeeder::class,
            CategorySeeder::class,
            
            // CRM and B2B seeders
            OrganismeSeeder::class,
            ConventionSeeder::class,
            
            // Medical staff and resources
            DoctorSeeder::class,
            PatientSeeder::class,
            ScheduleSeeder::class,
            
            // Products and inventory
            ProductSeeder::class,
            PrestationSeeder::class,
            FournisseurSeeder::class,
            MedicationSeeder::class,
            StockageSeeder::class,
            InventorySeeder::class,
            
            // Medical records and operations
            AllergySeeder::class,
            AppointmentSeeder::class,
            WaitListSeeder::class,
            BonCommendSeeder::class,
            
            // Existing seeders
            MedicationDoctorFavoratSeeder::class,
            CaissePermissionSeeder::class,
            RolesTableSeeder::class,
            // Ensure an admin user exists
            AdminUserSeeder::class,
        ]);
    }
}