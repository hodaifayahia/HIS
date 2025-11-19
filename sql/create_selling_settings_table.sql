-- Create selling_settings table for SellingSettings feature
-- Run this on the production database if migration fails

CREATE TABLE IF NOT EXISTS `selling_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `service_id` bigint unsigned NOT NULL,
  `percentage` decimal(8,2) NOT NULL DEFAULT 0,
  `type` enum('pharmacy','stock') NOT NULL DEFAULT 'pharmacy',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint unsigned,
  `updated_by` bigint unsigned,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  
  CONSTRAINT `selling_settings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `selling_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `selling_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  
  UNIQUE KEY `unique_active_per_service_type` (`service_id`, `type`, `is_active`),
  KEY `selling_settings_service_type_active_index` (`service_id`, `type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
