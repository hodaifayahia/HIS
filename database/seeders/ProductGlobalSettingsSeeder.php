<?php

namespace Database\Seeders;

use App\Models\ProductGlobalSetting;
use Illuminate\Database\Seeder;

class ProductGlobalSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'setting_key' => 'min_quantity_all_services',
                'setting_value' => [
                    'threshold' => 10,
                    'apply_to_services' => true,
                    'auto_alert' => true
                ],
                'description' => 'Minimum quantity threshold for all services across products',
            ],
            [
                'setting_key' => 'critical_stock_threshold',
                'setting_value' => [
                    'threshold' => 5,
                    'apply_to_services' => true,
                    'auto_alert' => true,
                    'block_operations' => false
                ],
                'description' => 'Critical stock level that requires immediate attention',
            ],
            [
                'setting_key' => 'expiry_alert_days',
                'setting_value' => [
                    'days' => 30,
                    'apply_to_services' => true,
                    'auto_alert' => true
                ],
                'description' => 'Number of days before expiry to trigger alerts',
            ],
            [
                'setting_key' => 'auto_reorder_settings',
                'setting_value' => [
                    'enabled' => false,
                    'reorder_point' => 20,
                    'reorder_quantity' => 50,
                    'apply_to_services' => true
                ],
                'description' => 'Automatic reorder settings for low stock items',
            ],
            [
                'setting_key' => 'notification_settings',
                'setting_value' => [
                    'email_alerts' => true,
                    'sms_alerts' => false,
                    'alert_frequency' => 'daily',
                    'apply_to_services' => true
                ],
                'description' => 'Notification preferences for stock alerts',
            ],
        ];

        foreach ($settings as $setting) {
            ProductGlobalSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }
}
