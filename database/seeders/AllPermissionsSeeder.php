<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AllPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Caisse Permissions
            'caisse.approve' => 'Can approve caisse transactions',
            'caisse.view' => 'Can view caisse transactions',
            'caisse.create' => 'Can create caisse transactions',
            'caisse.edit' => 'Can edit caisse transactions',
            'caisse.delete' => 'Can delete caisse transactions',

            // Refund Permissions
            'refund.approve' => 'Can approve refunds',
            'refund.view' => 'Can view refunds',
            'refund.create' => 'Can create refunds',
            'refund.edit' => 'Can edit refunds',
            'refund.delete' => 'Can delete refunds',
            'refund.manage' => 'Can manage refunds',

            // Coffre Permissions
            'coffre.approve' => 'Can approve coffre transactions',
            'coffre.view' => 'Can view coffre transactions',
            'coffre.create' => 'Can create coffre transactions',
            'coffre.edit' => 'Can edit coffre transactions',
            'coffre.delete' => 'Can delete coffre transactions',

            // Stock/Inventory Permissions
            'stock.approve' => 'Can approve stock movements',
            'stock.view' => 'Can view stock',
            'stock.create' => 'Can create stock movements',
            'stock.edit' => 'Can edit stock',
            'stock.delete' => 'Can delete stock',
            'stock.manage' => 'Can manage stock',

            // Bon Commend (Purchase Order) Permissions
            'bon_commend.approve' => 'Can approve purchase orders',
            'bon_commend.view' => 'Can view purchase orders',
            'bon_commend.create' => 'Can create purchase orders',
            'bon_commend.edit' => 'Can edit purchase orders',
            'bon_commend.delete' => 'Can delete purchase orders',

            // Bon Reception (Receipt) Permissions
            'bon_reception.approve' => 'Can approve goods receipts',
            'bon_reception.view' => 'Can view goods receipts',
            'bon_reception.create' => 'Can create goods receipts',
            'bon_reception.edit' => 'Can edit goods receipts',
            'bon_reception.delete' => 'Can delete goods receipts',

            // User Management Permissions
            'users.manage' => 'Can manage users',
            'users.view' => 'Can view users',
            'users.create' => 'Can create users',
            'users.edit' => 'Can edit users',
            'users.delete' => 'Can delete users',

            // Role Management Permissions
            'roles.manage' => 'Can manage roles',
            'roles.view' => 'Can view roles',
            'roles.create' => 'Can create roles',
            'roles.edit' => 'Can edit roles',
            'roles.delete' => 'Can delete roles',

            // Permission Management
            'permissions.manage' => 'Can manage permissions',
            'permissions.view' => 'Can view permissions',
            'permissions.create' => 'Can create permissions',
            'permissions.edit' => 'Can edit permissions',
            'permissions.delete' => 'Can delete permissions',

            // Appointment Permissions
            'appointments.manage' => 'Can manage appointments',
            'appointments.view' => 'Can view appointments',
            'appointments.create' => 'Can create appointments',
            'appointments.edit' => 'Can edit appointments',
            'appointments.delete' => 'Can delete appointments',
            'appointments.force' => 'Can force appointments',

            // Patient Permissions
            'patients.manage' => 'Can manage patients',
            'patients.view' => 'Can view patients',
            'patients.create' => 'Can create patients',
            'patients.edit' => 'Can edit patients',
            'patients.delete' => 'Can delete patients',

            // Doctor Permissions
            'doctors.manage' => 'Can manage doctors',
            'doctors.view' => 'Can view doctors',
            'doctors.create' => 'Can create doctors',
            'doctors.edit' => 'Can edit doctors',
            'doctors.delete' => 'Can delete doctors',

            // Report Permissions
            'reports.view' => 'Can view reports',
            'reports.create' => 'Can create reports',
            'reports.export' => 'Can export reports',

            // Settings Permissions
            'settings.manage' => 'Can manage system settings',
            'settings.view' => 'Can view settings',

            // Consultation/Reception Permissions
            'consultations.manage' => 'Can manage consultations',
            'consultations.view' => 'Can view consultations',
            'consultations.create' => 'Can create consultations',
            'consultations.edit' => 'Can edit consultations',

            // Prescription Permissions
            'prescriptions.manage' => 'Can manage prescriptions',
            'prescriptions.view' => 'Can view prescriptions',
            'prescriptions.create' => 'Can create prescriptions',
            'prescriptions.edit' => 'Can edit prescriptions',

            // Finance/Billing Permissions
            'billing.manage' => 'Can manage billing',
            'billing.view' => 'Can view billing',
            'billing.create' => 'Can create invoices',
            'billing.approve' => 'Can approve billing transactions',

            // Convention Permissions
            'conventions.manage' => 'Can manage conventions',
            'conventions.view' => 'Can view conventions',
            'conventions.create' => 'Can create conventions',
            'conventions.edit' => 'Can edit conventions',

            // Pharmacy Permissions
            'pharmacy.manage' => 'Can manage pharmacy',
            'pharmacy.view' => 'Can view pharmacy',
            'pharmacy.dispense' => 'Can dispense medications',

            // Inventory Audit Permissions
            'inventory_audit.manage' => 'Can manage inventory audits',
            'inventory_audit.view' => 'Can view inventory audits',
            'inventory_audit.create' => 'Can create inventory audits',
            'inventory_audit.approve' => 'Can approve inventory audits',
        ];

        foreach ($permissions as $permissionName => $description) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('All permissions created/verified successfully!');
    }
}
