/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agreements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `convention_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agreements_convention_id_foreign` (`convention_id`),
  CONSTRAINT `agreements_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allergies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `allergies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `patient_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `allergies_patient_id_foreign` (`patient_id`),
  CONSTRAINT `allergies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annexes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `annexes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `annex_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `convention_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `min_price` int NOT NULL,
  `prestation_prix_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'empty',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `annexes_convention_id_index` (`convention_id`),
  KEY `annexes_service_id_index` (`service_id`),
  KEY `annexes_annex_name_index` (`annex_name`),
  KEY `annexes_is_active_index` (`is_active`),
  KEY `annexes_created_at_index` (`created_at`),
  CONSTRAINT `annexes_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annexes_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_available_month`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_available_month` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `doctor_id` int NOT NULL,
  `is_available` tinyint(1) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_forcers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_forcers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `is_able_to_force` tinyint(1) NOT NULL DEFAULT '0',
  `number_of_patients` int NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `include_time` tinyint(1) NOT NULL DEFAULT '0',
  `specific_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_forcers_doctor_id_foreign` (`doctor_id`),
  KEY `appointment_forcers_user_id_foreign` (`user_id`),
  CONSTRAINT `appointment_forcers_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_forcers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_modality_forces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_modality_forces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modality_id` bigint unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `number_of_patients` int NOT NULL DEFAULT '1' COMMENT 'Number of patients that can be forced into this appointment',
  `user_id` bigint unsigned NOT NULL,
  `is_able_to_force` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indicates if the user can force appointments for this modality',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_modality_forces_modality_id_foreign` (`modality_id`),
  KEY `appointment_modality_forces_user_id_foreign` (`user_id`),
  CONSTRAINT `appointment_modality_forces_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_modality_forces_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_prestations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_prestations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prestation_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `appointment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_prestations_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_prestations_prestation_id_foreign` (`prestation_id`),
  KEY `appointment_prestations_patient_id_foreign` (`patient_id`),
  KEY `appointment_prestations_package_id_foreign` (`package_id`),
  CONSTRAINT `appointment_prestations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_prestations_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `prestation_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_prestations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_prestations_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `add_to_waitlist` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `created_by` int DEFAULT NULL,
  `canceled_by` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_updated_by_foreign` (`updated_by`),
  KEY `appointments_doctor_id_index` (`doctor_id`),
  KEY `appointments_patient_id_index` (`patient_id`),
  KEY `appointments_appointment_date_index` (`appointment_date`),
  KEY `appointments_status_index` (`status`),
  KEY `appointments_created_by_index` (`created_by`),
  KEY `appointments_doctor_id_appointment_date_index` (`doctor_id`,`appointment_date`),
  KEY `appointments_patient_id_appointment_date_index` (`patient_id`,`appointment_date`),
  KEY `appointments_appointment_date_status_index` (`appointment_date`,`status`),
  KEY `appointments_created_at_index` (`created_at`),
  KEY `appointments_updated_at_index` (`updated_at`),
  KEY `appointments_deleted_at_index` (`deleted_at`),
  CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `approval_persons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_persons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approval_persons_user_id_foreign` (`user_id`),
  KEY `approval_persons_is_active_max_amount_index` (`is_active`,`max_amount`),
  CONSTRAINT `approval_persons_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_type` tinyint(1) DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attributes_placeholder_id_foreign` (`placeholder_id`),
  CONSTRAINT `attributes_placeholder_id_foreign` FOREIGN KEY (`placeholder_id`) REFERENCES `placeholders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attributes_placeholder_doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes_placeholder_doctors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `placeholder_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attributes_placeholder_doctors_doctor_id_foreign` (`doctor_id`),
  KEY `attributes_placeholder_doctors_placeholder_id_foreign` (`placeholder_id`),
  KEY `attributes_placeholder_doctors_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `attributes_placeholder_doctors_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attributes_placeholder_doctors_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attributes_placeholder_doctors_placeholder_id_foreign` FOREIGN KEY (`placeholder_id`) REFERENCES `placeholders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authorized_modality_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorized_modality_users` (
  `user_id` bigint unsigned NOT NULL,
  `modality_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`modality_id`),
  KEY `authorized_modality_users_modality_id_foreign` (`modality_id`),
  CONSTRAINT `authorized_modality_users_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `authorized_modality_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `convention_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `activation_at` timestamp  NULL COMMENT 'The exact moment the changes take effect',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'draft, pending-approval, approved, active, archived',
  `creator_id` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `inactive_at` timestamp NULL DEFAULT NULL COMMENT 'The exact moment the changes become inactive',
  `head` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'yes, no',
  `updated_by_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `avenants_convention_id_foreign` (`convention_id`),
  KEY `avenants_creator_id_foreign` (`creator_id`),
  KEY `avenants_approver_id_foreign` (`approver_id`),
  KEY `avenants_updated_by_id_foreign` (`updated_by_id`),
  CONSTRAINT `avenants_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `avenants_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avenants_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avenants_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ayants_droit_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ayants_droit_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `convention_id` bigint unsigned DEFAULT NULL,
  `avenant_id` bigint unsigned DEFAULT NULL,
  `relationship_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., Spouse, Child',
  `max_age` int DEFAULT NULL,
  `is_covered` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ayants_droit_rules_convention_id_foreign` (`convention_id`),
  KEY `ayants_droit_rules_avenant_id_foreign` (`avenant_id`),
  CONSTRAINT `ayants_droit_rules_avenant_id_foreign` FOREIGN KEY (`avenant_id`) REFERENCES `avenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ayants_droit_rules_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `b2b_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `b2b_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `b2b_invoice_id` bigint unsigned NOT NULL,
  `fiche_navette_item_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `b2b_invoice_items_fiche_navette_item_id_unique` (`fiche_navette_item_id`),
  KEY `b2b_invoice_items_b2b_invoice_id_foreign` (`b2b_invoice_id`),
  CONSTRAINT `b2b_invoice_items_b2b_invoice_id_foreign` FOREIGN KEY (`b2b_invoice_id`) REFERENCES `b2b_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `b2b_invoice_items_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `b2b_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `b2b_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organisme_id` bigint unsigned NOT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'draft, sent, paid, partially-paid, overdue',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `b2b_invoices_invoice_number_unique` (`invoice_number`),
  KEY `b2b_invoices_organisme_id_foreign` (`organisme_id`),
  CONSTRAINT `b2b_invoices_organisme_id_foreign` FOREIGN KEY (`organisme_id`) REFERENCES `organismes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_account_transaction_pack_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_account_transaction_pack_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_account_transaction_packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_account_transaction_packs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_account_transaction_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(15,2) NOT NULL,
  `has_packs` tinyint(1) NOT NULL DEFAULT '0',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_batp_transaction` (`bank_account_transaction_id`),
  KEY `bank_account_transaction_packs_user_id_foreign` (`user_id`),
  CONSTRAINT `bank_account_transaction_packs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_batp_transaction` FOREIGN KEY (`bank_account_transaction_id`) REFERENCES `bank_account_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_account_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_account_id` bigint unsigned NOT NULL,
  `accepted_by_user_id` bigint unsigned NOT NULL,
  `transaction_type` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','cancelled','reconciled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reconciled_by_user_id` bigint unsigned DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Payer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Payment_date` timestamp NULL DEFAULT NULL,
  `reference_validation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Attachment_validation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Reason_validation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coffre_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_account_transactions_reference_unique` (`reference`),
  KEY `bank_account_transactions_accepted_by_user_id_foreign` (`accepted_by_user_id`),
  KEY `bank_account_transactions_reconciled_by_user_id_foreign` (`reconciled_by_user_id`),
  KEY `bank_account_transactions_bank_account_id_status_index` (`bank_account_id`,`status`),
  KEY `bank_account_transactions_transaction_type_status_index` (`transaction_type`,`status`),
  KEY `bank_account_transactions_transaction_date_index` (`transaction_date`),
  KEY `bank_account_transactions_reference_index` (`reference`),
  KEY `bank_account_transactions_coffre_id_foreign` (`coffre_id`),
  CONSTRAINT `bank_account_transactions_accepted_by_user_id_foreign` FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bank_account_transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_account_transactions_coffre_id_foreign` FOREIGN KEY (`coffre_id`) REFERENCES `coffres` (`id`),
  CONSTRAINT `bank_account_transactions_reconciled_by_user_id_foreign` FOREIGN KEY (`reconciled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_id` bigint unsigned NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DZD',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `available_balance` decimal(15,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_accounts_account_number_unique` (`account_number`),
  KEY `bank_accounts_bank_id_is_active_index` (`bank_id`,`is_active`),
  KEY `bank_accounts_currency_index` (`currency`),
  KEY `bank_accounts_account_number_index` (`account_number`),
  CONSTRAINT `bank_accounts_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `swift_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supported_currencies` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banks_name_unique` (`name`),
  UNIQUE KEY `banks_code_unique` (`code`),
  KEY `banks_is_active_sort_order_index` (`is_active`,`sort_order`),
  KEY `banks_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banque_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banque_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `banque_id` bigint unsigned NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_reconciliation',
  `reconciled_by_user_id` bigint unsigned DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banque_transactions_banque_id_foreign` (`banque_id`),
  KEY `banque_transactions_reconciled_by_user_id_foreign` (`reconciled_by_user_id`),
  CONSTRAINT `banque_transactions_banque_id_foreign` FOREIGN KEY (`banque_id`) REFERENCES `banks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `banque_transactions_reconciled_by_user_id_foreign` FOREIGN KEY (`reconciled_by_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `beds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `bed_identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., A, B',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'free, occupied, reserved',
  `current_patient_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `beds_room_id_bed_identifier_unique` (`room_id`,`bed_identifier`),
  KEY `beds_current_patient_id_foreign` (`current_patient_id`),
  CONSTRAINT `beds_current_patient_id_foreign` FOREIGN KEY (`current_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `beds_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_commend_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_commend_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_commend_id` bigint unsigned NOT NULL,
  `approval_person_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','rejected','sent_back') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_commend_approvals_requested_by_foreign` (`requested_by`),
  KEY `bon_commend_approvals_status_created_at_index` (`status`,`created_at`),
  KEY `bon_commend_approvals_bon_commend_id_status_index` (`bon_commend_id`,`status`),
  KEY `bon_commend_approvals_approval_person_id_index` (`approval_person_id`),
  CONSTRAINT `bon_commend_approvals_approval_person_id_foreign` FOREIGN KEY (`approval_person_id`) REFERENCES `approval_persons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_commend_approvals_bon_commend_id_foreign` FOREIGN KEY (`bon_commend_id`) REFERENCES `bon_commends` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_commend_approvals_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_commend_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_commend_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_commend_id` bigint unsigned NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_commend_attachments_bon_commend_id_foreign` (`bon_commend_id`),
  CONSTRAINT `bon_commend_attachments_bon_commend_id_foreign` FOREIGN KEY (`bon_commend_id`) REFERENCES `bon_commends` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_commend_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_commend_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_commend_id` bigint unsigned DEFAULT NULL,
  `factureproforma_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int DEFAULT NULL,
  `quntity_by_box` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity_desired` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified_by_approver` tinyint(1) NOT NULL DEFAULT '0',
  `original_quantity_desired` int DEFAULT NULL,
  `quantity_sended` int NOT NULL DEFAULT '0',
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service_demand',
  `source_id` bigint unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `bon_commend_items` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_commend_items_factureproforma_id_foreign` (`factureproforma_id`),
  KEY `bon_commend_items_product_id_foreign` (`product_id`),
  KEY `bon_commend_items_bon_commend_id_foreign` (`bon_commend_id`),
  KEY `bon_commend_items_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `bon_commend_items_bon_commend_id_foreign` FOREIGN KEY (`bon_commend_id`) REFERENCES `bon_commends` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_commend_items_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `bon_commend_items_factureproforma_id_foreign` FOREIGN KEY (`factureproforma_id`) REFERENCES `factureproformas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_commend_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_commends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_commends` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `pdf_content` longtext COLLATE utf8mb4_unicode_ci,
  `pdf_generated_at` timestamp NULL DEFAULT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `approval_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `has_approver_modifications` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `bon_commends_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `bon_commends_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_entree_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_entree_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_entree_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_entree_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_entree_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `bon_reception_item_id` bigint unsigned DEFAULT NULL,
  `in_stock_id` bigint unsigned DEFAULT NULL,
  `storage_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `boite_de` int NOT NULL DEFAULT '1',
  `quantity` int NOT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `sell_price` decimal(10,2) DEFAULT NULL,
  `tva` decimal(5,2) DEFAULT NULL,
  `by_box` tinyint(1) NOT NULL DEFAULT '0',
  `qte_by_box` int NOT NULL DEFAULT '1',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_entree_items_created_by_foreign` (`created_by`),
  KEY `bon_entree_items_bon_entree_id_product_id_index` (`bon_entree_id`,`product_id`),
  KEY `bon_entree_items_product_id_index` (`product_id`),
  KEY `bon_entree_items_batch_number_index` (`batch_number`),
  KEY `bon_entree_items_expiry_date_index` (`expiry_date`),
  KEY `bon_entree_items_bon_reception_item_id_index` (`bon_reception_item_id`),
  CONSTRAINT `bon_entree_items_bon_entree_id_foreign` FOREIGN KEY (`bon_entree_id`) REFERENCES `bon_entrees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_entree_items_bon_reception_item_id_foreign` FOREIGN KEY (`bon_reception_item_id`) REFERENCES `bon_reception_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_entree_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_entree_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_entrees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_entrees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_entree_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bon_reception_id` bigint unsigned DEFAULT NULL,
  `fournisseur_id` bigint unsigned DEFAULT NULL,
  `status` enum('draft','validated','transferred','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `service_abv` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bon_entrees_bon_entree_code_unique` (`bon_entree_code`),
  KEY `bon_entrees_created_by_foreign` (`created_by`),
  KEY `bon_entrees_status_created_at_index` (`status`,`created_at`),
  KEY `bon_entrees_fournisseur_id_created_at_index` (`fournisseur_id`,`created_at`),
  KEY `bon_entrees_bon_reception_id_index` (`bon_reception_id`),
  CONSTRAINT `bon_entrees_bon_reception_id_foreign` FOREIGN KEY (`bon_reception_id`) REFERENCES `bon_receptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_entrees_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_entrees_fournisseur_id_foreign` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_reception_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_reception_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_reception_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_reception_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_reception_id` bigint unsigned NOT NULL,
  `bon_commend_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity_ordered` int NOT NULL,
  `quantity_received` int NOT NULL,
  `quantity_surplus` int NOT NULL DEFAULT '0',
  `quantity_shortage` int NOT NULL DEFAULT '0',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','received','partial','excess','missing') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_unexpected` tinyint(1) NOT NULL DEFAULT '0',
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_reception_items_bon_commend_item_id_foreign` (`bon_commend_item_id`),
  KEY `bon_reception_items_bon_reception_id_status_index` (`bon_reception_id`,`status`),
  KEY `bon_reception_items_product_id_index` (`product_id`),
  CONSTRAINT `bon_reception_items_bon_commend_item_id_foreign` FOREIGN KEY (`bon_commend_item_id`) REFERENCES `bon_commend_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_reception_items_bon_reception_id_foreign` FOREIGN KEY (`bon_reception_id`) REFERENCES `bon_receptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_reception_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_receptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_receptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bon_retour_id` bigint unsigned DEFAULT NULL,
  `bon_entree_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_receptions_bon_retour_id_index` (`bon_retour_id`),
  KEY `bon_receptions_bon_entree_id_index` (`bon_entree_id`),
  CONSTRAINT `bon_receptions_bon_entree_id_foreign` FOREIGN KEY (`bon_entree_id`) REFERENCES `bon_entrees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_receptions_bon_retour_id_foreign` FOREIGN KEY (`bon_retour_id`) REFERENCES `bon_retours` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_retour_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_retour_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_retour_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `bon_entree_item_id` bigint unsigned DEFAULT NULL COMMENT 'Reference to original item',
  `bon_reception_item_id` bigint unsigned DEFAULT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity_returned` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `tva` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL,
  `return_reason` enum('defective','expired','damaged','wrong_item','quality_issue','other','overstock') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `storage_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock_updated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bon_retour_items_bon_retour_id_index` (`bon_retour_id`),
  KEY `bon_retour_items_product_id_index` (`product_id`),
  KEY `bon_retour_items_batch_number_index` (`batch_number`),
  KEY `bon_retour_items_bon_retour_id_product_id_index` (`bon_retour_id`,`product_id`),
  KEY `bon_retour_items_bon_entree_item_id_foreign` (`bon_entree_item_id`),
  KEY `bon_retour_items_bon_reception_item_id_index` (`bon_reception_item_id`),
  CONSTRAINT `bon_retour_items_bon_entree_item_id_foreign` FOREIGN KEY (`bon_entree_item_id`) REFERENCES `bon_entree_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_retour_items_bon_reception_item_id_foreign` FOREIGN KEY (`bon_reception_item_id`) REFERENCES `bon_reception_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_retour_items_bon_retour_id_foreign` FOREIGN KEY (`bon_retour_id`) REFERENCES `bon_retours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bon_retour_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_retours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_retours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bon_retour_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bon_entree_id` bigint unsigned DEFAULT NULL COMMENT 'Reference to original bon entree',
  `fournisseur_id` bigint unsigned NOT NULL,
  `return_type` enum('defective','expired','wrong_delivery','overstock','quality_issue','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'defective',
  `status` enum('draft','pending','approved','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `service_abv` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `return_date` date NOT NULL DEFAULT '2025-10-19',
  `reference_invoice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_note_received` tinyint(1) NOT NULL DEFAULT '0',
  `credit_note_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bon_retours_bon_retour_code_unique` (`bon_retour_code`),
  KEY `bon_retours_bon_retour_code_index` (`bon_retour_code`),
  KEY `bon_retours_status_index` (`status`),
  KEY `bon_retours_return_type_index` (`return_type`),
  KEY `bon_retours_fournisseur_id_index` (`fournisseur_id`),
  KEY `bon_retours_service_abv_index` (`service_abv`),
  KEY `bon_retours_status_return_date_index` (`status`,`return_date`),
  KEY `bon_retours_bon_entree_id_foreign` (`bon_entree_id`),
  KEY `bon_retours_created_by_foreign` (`created_by`),
  KEY `bon_retours_approved_by_foreign` (`approved_by`),
  CONSTRAINT `bon_retours_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_retours_bon_entree_id_foreign` FOREIGN KEY (`bon_entree_id`) REFERENCES `bon_entrees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bon_retours_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bon_retours_fournisseur_id_foreign` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `caisse_session_denominations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse_session_denominations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `caisse_session_id` bigint unsigned NOT NULL,
  `denomination_type` enum('coin','note') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caisse_session_denominations_caisse_session_id_value_index` (`caisse_session_id`,`value`),
  CONSTRAINT `caisse_session_denominations_caisse_session_id_foreign` FOREIGN KEY (`caisse_session_id`) REFERENCES `caisse_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `caisse_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `caisse_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `opened_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `theoretical_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `closed_at` timestamp NULL DEFAULT NULL,
  `opening_amount` decimal(15,2) NOT NULL,
  `closing_amount` decimal(15,2) DEFAULT NULL,
  `total_cash_counted` decimal(15,2) DEFAULT NULL,
  `cash_difference` decimal(15,2) DEFAULT NULL,
  `expected_closing_amount` decimal(15,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `opening_notes` text COLLATE utf8mb4_unicode_ci,
  `closing_notes` text COLLATE utf8mb4_unicode_ci,
  `is_transfer` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `open_by` bigint unsigned NOT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `coffre_id_source` bigint unsigned DEFAULT NULL,
  `coffre_id_destination` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_open_session` (`caisse_id`,`status`),
  KEY `caisse_sessions_open_by_foreign` (`open_by`),
  KEY `caisse_sessions_closed_by_foreign` (`closed_by`),
  KEY `caisse_sessions_coffre_id_source_foreign` (`coffre_id_source`),
  KEY `caisse_sessions_coffre_id_destination_foreign` (`coffre_id_destination`),
  KEY `caisse_sessions_caisse_id_index` (`caisse_id`),
  KEY `caisse_sessions_user_id_index` (`user_id`),
  KEY `caisse_sessions_status_index` (`status`),
  CONSTRAINT `caisse_sessions_caisse_id_foreign` FOREIGN KEY (`caisse_id`) REFERENCES `caisses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_sessions_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_sessions_coffre_id_destination_foreign` FOREIGN KEY (`coffre_id_destination`) REFERENCES `coffres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_sessions_coffre_id_source_foreign` FOREIGN KEY (`coffre_id_source`) REFERENCES `coffres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_sessions_open_by_foreign` FOREIGN KEY (`open_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `caisse_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sourceable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sourceable_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caisse_transactions_user_id_foreign` (`user_id`),
  KEY `caisse_transactions_sourceable_type_sourceable_id_index` (`sourceable_type`,`sourceable_id`),
  CONSTRAINT `caisse_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `caisse_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `caisse_id` bigint unsigned NOT NULL,
  `caisse_session_id` bigint unsigned NOT NULL,
  `from_user_id` bigint unsigned NOT NULL,
  `to_user_id` bigint unsigned NOT NULL,
  `have_problems` tinyint(1) NOT NULL DEFAULT '0',
  `amount_sended` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_received` decimal(15,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transfer_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caisse_transfers_caisse_id_foreign` (`caisse_id`),
  KEY `caisse_transfers_caisse_session_id_foreign` (`caisse_session_id`),
  KEY `caisse_transfers_from_user_id_foreign` (`from_user_id`),
  KEY `caisse_transfers_to_user_id_foreign` (`to_user_id`),
  CONSTRAINT `caisse_transfers_caisse_id_foreign` FOREIGN KEY (`caisse_id`) REFERENCES `caisses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_transfers_caisse_session_id_foreign` FOREIGN KEY (`caisse_session_id`) REFERENCES `caisse_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_transfers_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caisse_transfers_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `caisses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `caisses_name_unique` (`name`),
  KEY `caisses_service_id_foreign` (`service_id`),
  CONSTRAINT `caisses_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chronic_diseases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chronic_diseases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `diagnosis_date` date DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chronic_diseases_patient_id_foreign` (`patient_id`),
  CONSTRAINT `chronic_diseases_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coffre_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coffre_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coffre_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `description` text COLLATE utf8mb4_unicode_ci,
  `source_caisse_session_id` bigint unsigned DEFAULT NULL,
  `destination_banque_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `dest_coffre_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coffre_transactions_source_caisse_session_id_foreign` (`source_caisse_session_id`),
  KEY `coffre_transactions_destination_banque_id_foreign` (`destination_banque_id`),
  KEY `coffre_transactions_dest_coffre_id_foreign` (`dest_coffre_id`),
  KEY `coffre_transactions_coffre_id_created_at_index` (`coffre_id`,`created_at`),
  KEY `coffre_transactions_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `coffre_transactions_transaction_type_index` (`transaction_type`),
  CONSTRAINT `coffre_transactions_coffre_id_foreign` FOREIGN KEY (`coffre_id`) REFERENCES `coffres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coffre_transactions_dest_coffre_id_foreign` FOREIGN KEY (`dest_coffre_id`) REFERENCES `coffres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coffre_transactions_destination_banque_id_foreign` FOREIGN KEY (`destination_banque_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `coffre_transactions_source_caisse_session_id_foreign` FOREIGN KEY (`source_caisse_session_id`) REFERENCES `caisse_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `coffre_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coffres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coffres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsible_user_id` bigint unsigned NOT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coffres_name_unique` (`name`),
  KEY `coffres_responsible_user_id_foreign` (`responsible_user_id`),
  CONSTRAINT `coffres_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultation_placeholder_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultation_placeholder_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `placeholder_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `attribute_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultation_placeholder_attributes_consultation_id_foreign` (`consultation_id`),
  KEY `consultation_placeholder_attributes_appointment_id_foreign` (`appointment_id`),
  KEY `consultation_placeholder_attributes_placeholder_id_foreign` (`placeholder_id`),
  KEY `consultation_placeholder_attributes_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `consultation_placeholder_attributes_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_placeholder_attributes_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_placeholder_attributes_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_placeholder_attributes_placeholder_id_foreign` FOREIGN KEY (`placeholder_id`) REFERENCES `placeholders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `consultation_end_at` bigint unsigned DEFAULT NULL,
  `codebash` text COLLATE utf8mb4_unicode_ci,
  `appointment_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultations_template_id_foreign` (`template_id`),
  KEY `consultations_patient_id_index` (`patient_id`),
  KEY `consultations_doctor_id_index` (`doctor_id`),
  KEY `consultations_appointment_id_index` (`appointment_id`),
  KEY `consultations_doctor_id_patient_id_index` (`doctor_id`,`patient_id`),
  CONSTRAINT `consultations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultationworkspace_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultationworkspace_list` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint unsigned NOT NULL,
  `consultation_workspace_id` bigint unsigned NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultationworkspace_list_consultation_id_foreign` (`consultation_id`),
  KEY `consultationworkspace_list_consultation_workspace_id_foreign` (`consultation_workspace_id`),
  CONSTRAINT `consultationworkspace_list_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultationworkspace_list_consultation_workspace_id_foreign` FOREIGN KEY (`consultation_workspace_id`) REFERENCES `consultationworkspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultationworkspaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultationworkspaces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultationworkspaces_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `consultationworkspaces_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_percentages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_percentages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_percentages_contract_id_foreign` (`contract_id`),
  CONSTRAINT `contract_percentages_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `conventions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `convention_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `convention_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `convention_id` bigint unsigned DEFAULT NULL,
  `avenant_id` bigint unsigned DEFAULT NULL,
  `prestation_id` bigint unsigned DEFAULT NULL,
  `coverage_percentage` decimal(5,2) DEFAULT NULL,
  `negotiated_price` decimal(15,2) DEFAULT NULL,
  `financial_cap` decimal(15,2) DEFAULT NULL COMMENT 'Plafond for this specific prestation',
  `is_covered` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `convention_rules_convention_id_foreign` (`convention_id`),
  KEY `convention_rules_avenant_id_foreign` (`avenant_id`),
  KEY `convention_rules_prestation_id_foreign` (`prestation_id`),
  CONSTRAINT `convention_rules_avenant_id_foreign` FOREIGN KEY (`avenant_id`) REFERENCES `avenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `convention_rules_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `convention_rules_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conventions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conventions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organisme_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_general` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `activation_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conventions_organisme_id_foreign` (`organisme_id`),
  CONSTRAINT `conventions_organisme_id_foreign` FOREIGN KEY (`organisme_id`) REFERENCES `organismes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conventions_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conventions_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `family_auth` json DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `min_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `avenant_id` bigint unsigned DEFAULT NULL,
  `convention_id` bigint unsigned NOT NULL,
  `updated_by_id` bigint unsigned NOT NULL,
  `head` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension_count` int NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conventions_details_avenant_id_foreign` (`avenant_id`),
  KEY `conventions_details_convention_id_foreign` (`convention_id`),
  KEY `conventions_details_updated_by_id_foreign` (`updated_by_id`),
  CONSTRAINT `conventions_details_avenant_id_foreign` FOREIGN KEY (`avenant_id`) REFERENCES `avenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conventions_details_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`),
  CONSTRAINT `conventions_details_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `b2b_invoice_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(15,2) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_notes_b2b_invoice_id_foreign` (`b2b_invoice_id`),
  CONSTRAINT `credit_notes_b2b_invoice_id_foreign` FOREIGN KEY (`b2b_invoice_id`) REFERENCES `b2b_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `demandes_de_fonds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demandes_de_fonds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `nature` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `justification` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `disbursed_from_coffre_id` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `disbursed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demandes_de_fonds_requester_id_foreign` (`requester_id`),
  KEY `demandes_de_fonds_approver_id_foreign` (`approver_id`),
  KEY `demandes_de_fonds_disbursed_from_coffre_id_foreign` (`disbursed_from_coffre_id`),
  CONSTRAINT `demandes_de_fonds_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `demandes_de_fonds_disbursed_from_coffre_id_foreign` FOREIGN KEY (`disbursed_from_coffre_id`) REFERENCES `coffres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `demandes_de_fonds_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doctor_emergency_plannings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_emergency_plannings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `planning_date` date NOT NULL,
  `shift_start_time` time NOT NULL,
  `shift_end_time` time NOT NULL,
  `shift_type` enum('day','night','emergency') COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doctor_shift` (`doctor_id`,`planning_date`,`shift_start_time`,`shift_end_time`),
  KEY `doctor_emergency_plannings_service_id_foreign` (`service_id`),
  CONSTRAINT `doctor_emergency_plannings_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_emergency_plannings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doctor_medication_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_medication_favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `specialization_id` bigint unsigned DEFAULT NULL,
  `allowed_appointment_today` tinyint(1) NOT NULL DEFAULT '1',
  `number_of_patient` int NOT NULL DEFAULT '0',
  `frequency` enum('Daily','Weekly','Monthly','Custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Weekly',
  `specific_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `patients_based_on_time` tinyint(1) NOT NULL DEFAULT '0',
  `time_slot` int DEFAULT NULL,
  `appointment_booking_window` int NOT NULL DEFAULT '1',
  `include_time` int DEFAULT '0',
  `created_by` int NOT NULL DEFAULT '2',
  `user_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doctors_schedule_id_foreign` (`schedule_id`),
  KEY `doctors_specialization_id_index` (`specialization_id`),
  KEY `doctors_user_id_index` (`user_id`),
  KEY `doctors_created_by_index` (`created_by`),
  KEY `doctors_frequency_index` (`frequency`),
  KEY `doctors_specialization_id_frequency_index` (`specialization_id`,`frequency`),
  KEY `doctors_user_id_specialization_id_index` (`user_id`,`specialization_id`),
  KEY `doctors_created_at_index` (`created_at`),
  KEY `doctors_updated_at_index` (`updated_at`),
  KEY `doctors_deleted_at_index` (`deleted_at`),
  CONSTRAINT `doctors_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctors_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `excluded_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `excluded_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `afternoon_start_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `morning_end_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `morning_start_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_patients_per_day` int DEFAULT NULL,
  `morning_patients` int DEFAULT NULL,
  `shift_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `is_afternoon_active` tinyint(1) DEFAULT NULL,
  `is_morning_active` tinyint(1) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apply_for_all_years` tinyint(1) NOT NULL,
  `afternoon_end_time` timestamp NULL DEFAULT NULL,
  `afternoon_patients` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `excluded_dates_doctor_id_index` (`doctor_id`),
  KEY `excluded_dates_start_date_index` (`start_date`),
  KEY `excluded_dates_end_date_index` (`end_date`),
  KEY `excluded_dates_doctor_id_start_date_index` (`doctor_id`,`start_date`),
  KEY `excluded_dates_doctor_id_end_date_index` (`doctor_id`,`end_date`),
  CONSTRAINT `excluded_dates_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facture_proforma_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facture_proforma_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `facture_proforma_id` bigint unsigned NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facture_proforma_attachments_facture_proforma_id_foreign` (`facture_proforma_id`),
  CONSTRAINT `facture_proforma_attachments_facture_proforma_id_foreign` FOREIGN KEY (`facture_proforma_id`) REFERENCES `factureproformas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factureproforma_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factureproforma_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `factureproforma_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `quantity_sended` int NOT NULL DEFAULT '0',
  `confirmation_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `factureproforma_products_factureproforma_id_foreign` (`factureproforma_id`),
  KEY `factureproforma_products_product_id_foreign` (`product_id`),
  KEY `factureproforma_products_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `factureproforma_products_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `factureproforma_products_factureproforma_id_foreign` FOREIGN KEY (`factureproforma_id`) REFERENCES `factureproformas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factureproforma_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factureproformas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factureproformas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `factureProformaCode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fournisseur_id` bigint unsigned NOT NULL,
  `service_demand_purchasing_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `attachments` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `pdf_content` longtext COLLATE utf8mb4_unicode_ci,
  `pdf_generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `factureproformas_factureproformacode_unique` (`factureProformaCode`),
  KEY `factureproformas_fournisseur_id_foreign` (`fournisseur_id`),
  KEY `factureproformas_service_demand_purchasing_id_foreign` (`service_demand_purchasing_id`),
  KEY `factureproformas_created_by_foreign` (`created_by`),
  KEY `factureproformas_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `factureproformas_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `factureproformas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `factureproformas_fournisseur_id_foreign` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  CONSTRAINT `factureproformas_service_demand_purchasing_id_foreign` FOREIGN KEY (`service_demand_purchasing_id`) REFERENCES `service_demand_purchasings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `family_diseases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `family_diseases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `disease_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `patient_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `family_diseases_patient_id_foreign` (`patient_id`),
  CONSTRAINT `family_diseases_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fee_distribution_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_distribution_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prestation_id` bigint unsigned DEFAULT NULL,
  `role_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., Primary Doctor, Assistant, Technician, Clinic',
  `share_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'percentage, fixed_amount',
  `share_value` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_distribution_models_prestation_id_foreign` (`prestation_id`),
  CONSTRAINT `fee_distribution_models_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiche_navette_custom_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiche_navette_custom_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the custom package',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description of the custom package',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiche_navette_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiche_navette_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiche_navette_id` bigint unsigned NOT NULL,
  `prestation_id` bigint unsigned DEFAULT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The core status engine for a single service, e.g., scheduled, awaiting-payment, visa-granted',
  `base_price` decimal(15,2) NOT NULL,
  `final_price` decimal(15,2) NOT NULL COMMENT 'Price after all calculations',
  `patient_share` decimal(15,2) DEFAULT NULL,
  `organisme_share` decimal(15,2) DEFAULT NULL,
  `primary_clinician_id` bigint unsigned DEFAULT NULL,
  `assistant_clinician_id` bigint unsigned DEFAULT NULL,
  `technician_id` bigint unsigned DEFAULT NULL,
  `modality_id` bigint unsigned DEFAULT NULL,
  `convention_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `uploaded_file` json DEFAULT NULL,
  `family_authorization` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `custom_name` text COLLATE utf8mb4_unicode_ci,
  `prise_en_charge_date` date DEFAULT NULL COMMENT 'The crucial date from the B2B guarantee document',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `package_id` bigint unsigned DEFAULT NULL,
  `is_nursing_consumption` tinyint(1) NOT NULL DEFAULT '0',
  `remise_id` bigint unsigned DEFAULT NULL,
  `insured_id` bigint unsigned DEFAULT NULL,
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fiche_navette_items_fiche_navette_id_foreign` (`fiche_navette_id`),
  KEY `fiche_navette_items_prestation_id_foreign` (`prestation_id`),
  KEY `fiche_navette_items_doctor_id_foreign` (`doctor_id`),
  KEY `fiche_navette_items_primary_clinician_id_foreign` (`primary_clinician_id`),
  KEY `fiche_navette_items_assistant_clinician_id_foreign` (`assistant_clinician_id`),
  KEY `fiche_navette_items_technician_id_foreign` (`technician_id`),
  KEY `fiche_navette_items_modality_id_foreign` (`modality_id`),
  KEY `fiche_navette_items_package_id_foreign` (`package_id`),
  KEY `fiche_navette_items_remise_id_foreign` (`remise_id`),
  KEY `fiche_navette_items_convention_id_foreign` (`convention_id`),
  KEY `fiche_navette_items_insured_id_foreign` (`insured_id`),
  KEY `fiche_navette_items_patient_id_foreign` (`patient_id`),
  CONSTRAINT `fiche_navette_items_assistant_clinician_id_foreign` FOREIGN KEY (`assistant_clinician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiche_navette_items_fiche_navette_id_foreign` FOREIGN KEY (`fiche_navette_id`) REFERENCES `fiche_navettes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiche_navette_items_insured_id_foreign` FOREIGN KEY (`insured_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `prestation_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiche_navette_items_primary_clinician_id_foreign` FOREIGN KEY (`primary_clinician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_remise_id_foreign` FOREIGN KEY (`remise_id`) REFERENCES `remises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_navette_items_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiche_navettes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiche_navettes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `creator_id` bigint unsigned NOT NULL,
  `fiche_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., scheduled, checked-in, completed, cancelled',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `companion_id` bigint unsigned DEFAULT NULL,
  `is_emergency` tinyint(1) DEFAULT NULL,
  `emergency_doctor_id` int DEFAULT NULL COMMENT 'ID of the emergency doctor if applicable',
  PRIMARY KEY (`id`),
  KEY `fiche_navettes_patient_id_foreign` (`patient_id`),
  KEY `fiche_navettes_creator_id_foreign` (`creator_id`),
  KEY `fiche_navettes_companion_id_foreign` (`companion_id`),
  CONSTRAINT `fiche_navettes_companion_id_foreign` FOREIGN KEY (`companion_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiche_navettes_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiche_navettes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiche_navette_item_id` bigint unsigned DEFAULT NULL,
  `item_dependency_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `cashier_id` bigint unsigned DEFAULT NULL,
  `caisse_session_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'payment, refund, deposit, withdrawal, donation',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'cash, card, patient-balance, bank-transfer',
  `b2b_invoice_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_transactions_fiche_navette_item_id_foreign` (`fiche_navette_item_id`),
  KEY `financial_transactions_patient_id_foreign` (`patient_id`),
  KEY `financial_transactions_cashier_id_foreign` (`cashier_id`),
  KEY `financial_transactions_caisse_session_id_foreign` (`caisse_session_id`),
  KEY `financial_transactions_b2b_invoice_id_foreign` (`b2b_invoice_id`),
  KEY `financial_transactions_approved_by_foreign` (`approved_by`),
  KEY `financial_transactions_item_dependency_id_foreign` (`item_dependency_id`),
  CONSTRAINT `financial_transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_b2b_invoice_id_foreign` FOREIGN KEY (`b2b_invoice_id`) REFERENCES `b2b_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_caisse_session_id_foreign` FOREIGN KEY (`caisse_session_id`) REFERENCES `caisse_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_item_dependency_id_foreign` FOREIGN KEY (`item_dependency_id`) REFERENCES `item_dependencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fournisseur_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fournisseur_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fournisseur_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fournisseur_contacts_fournisseur_id_foreign` (`fournisseur_id`),
  CONSTRAINT `fournisseur_contacts_fournisseur_id_foreign` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fournisseurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fournisseurs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `stockage_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_units` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventories_composite_unique` (`product_id`,`stockage_id`,`batch_number`,`serial_number`,`expiry_date`),
  KEY `inventories_stockage_id_foreign` (`stockage_id`),
  CONSTRAINT `inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventories_stockage_id_foreign` FOREIGN KEY (`stockage_id`) REFERENCES `stockages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_dependencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_item_id` bigint unsigned NOT NULL,
  `dependent_item_id` bigint unsigned DEFAULT NULL,
  `dependent_prestation_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `convention_id` bigint unsigned DEFAULT NULL,
  `remise_id` bigint unsigned DEFAULT NULL,
  `final_price_after_convention` decimal(10,2) DEFAULT NULL,
  `final_price_after_remise` decimal(10,2) DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `organisme_share` decimal(10,2) DEFAULT NULL,
  `dependency_type` enum('contraindication','prerequisite','alternative','required','optional') COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `custom_name` text COLLATE utf8mb4_unicode_ci,
  `is_package` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indicates if this dependency is part of a custom package',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_dependencies_dependent_prestation_id_foreign` (`dependent_prestation_id`),
  KEY `item_dependencies_patient_id_foreign` (`patient_id`),
  KEY `item_dependencies_doctor_id_foreign` (`doctor_id`),
  KEY `item_dependencies_convention_id_foreign` (`convention_id`),
  KEY `item_dependencies_remise_id_foreign` (`remise_id`),
  KEY `item_dependencies_parent_item_id_dependency_type_index` (`parent_item_id`,`dependency_type`),
  KEY `item_dependencies_dependent_item_id_index` (`dependent_item_id`),
  CONSTRAINT `item_dependencies_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_dependencies_dependent_item_id_foreign` FOREIGN KEY (`dependent_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_dependencies_dependent_prestation_id_foreign` FOREIGN KEY (`dependent_prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_dependencies_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_dependencies_parent_item_id_foreign` FOREIGN KEY (`parent_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_dependencies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_dependencies_remise_id_foreign` FOREIGN KEY (`remise_id`) REFERENCES `remises` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medication_doctor_favorats_`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medication_doctor_favorats_` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_medicament` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `boite_de` text COLLATE utf8mb4_unicode_ci,
  `__v` int DEFAULT NULL,
  `isSelected` tinyint(1) DEFAULT NULL,
  `code_pch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_commercial` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moality_appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `moality_appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modality_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `appointment_booking_window` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `canceled_at` date DEFAULT NULL,
  `appointment_time` time NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `canceled_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `moality_appointments_modality_id_foreign` (`modality_id`),
  KEY `moality_appointments_patient_id_foreign` (`patient_id`),
  KEY `moality_appointments_created_by_foreign` (`created_by`),
  KEY `moality_appointments_canceled_by_foreign` (`canceled_by`),
  KEY `moality_appointments_updated_by_foreign` (`updated_by`),
  CONSTRAINT `moality_appointments_canceled_by_foreign` FOREIGN KEY (`canceled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `moality_appointments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `moality_appointments_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moality_appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `moality_appointments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modalities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., IRM Siemens 1.5T - Salle 1',
  `internal_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modality_type_id` bigint unsigned DEFAULT NULL,
  `dicom_ae_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int DEFAULT NULL,
  `physical_location_id` bigint unsigned DEFAULT NULL,
  `operational_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'operational, maintenance, out-of-service',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `integration_protocol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection_configuration` text COLLATE utf8mb4_unicode_ci,
  `data_retrieval_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` enum('Daily','Weekly','Monthly','Custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Weekly',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to the modality image',
  `time_slot_duration` int DEFAULT NULL COMMENT 'Default duration in minutes for this modality',
  `slot_type` enum('minutes','days') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'minutes',
  `booking_window` int NOT NULL DEFAULT '30' COMMENT 'How many days in advance can appointments be booked',
  `availability_months` json DEFAULT NULL COMMENT 'JSON array of available months for booking',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `consumption_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consumption_unit` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modalities_internal_code_unique` (`internal_code`),
  KEY `modalities_modality_type_id_foreign` (`modality_type_id`),
  KEY `modalities_physical_location_id_foreign` (`physical_location_id`),
  KEY `modalities_service_id_foreign` (`service_id`),
  CONSTRAINT `modalities_modality_type_id_foreign` FOREIGN KEY (`modality_type_id`) REFERENCES `modality_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `modalities_physical_location_id_foreign` FOREIGN KEY (`physical_location_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `modalities_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modality_available_months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modality_available_months` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modality_id` bigint unsigned NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `modality_available_months_modality_id_foreign` (`modality_id`),
  CONSTRAINT `modality_available_months_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modality_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modality_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modality_id` bigint unsigned NOT NULL,
  `day_of_week` enum('sunday','monday','tuesday','wednesday','thursday','friday','saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_period` enum('morning','afternoon') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `date` date DEFAULT NULL,
  `time_slot_duration` int NOT NULL COMMENT 'Duration in minutes',
  `slot_type` enum('minutes','days') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'minutes',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `break_duration` int DEFAULT NULL COMMENT 'Break duration in minutes',
  `break_times` json DEFAULT NULL COMMENT 'JSON array of break start times',
  `excluded_dates` json DEFAULT NULL COMMENT 'Dates when this schedule does not apply',
  `modified_times` json DEFAULT NULL COMMENT 'Special timing modifications for specific dates',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `modality_schedules_modality_id_foreign` (`modality_id`),
  KEY `modality_schedules_created_by_foreign` (`created_by`),
  KEY `modality_schedules_updated_by_foreign` (`updated_by`),
  CONSTRAINT `modality_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `modality_schedules_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `modality_schedules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modality_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modality_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `image_url` text COLLATE utf8mb4_unicode_ci,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modality_types_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organisme_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organisme_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organisme_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organisme_contacts_organisme_id_foreign` (`organisme_id`),
  CONSTRAINT `organisme_contacts_organisme_id_foreign` FOREIGN KEY (`organisme_id`) REFERENCES `organismes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organismes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organismes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_form` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trade_register_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id_nif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statistical_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `article_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wilaya` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `initial_invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `initial_credit_note_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `industry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creation_date` date DEFAULT NULL,
  `number_of_employees` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `organism_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organismes_trade_register_number_unique` (`trade_register_number`),
  UNIQUE KEY `organismes_tax_id_nif_unique` (`tax_id_nif`),
  UNIQUE KEY `organismes_statistical_id_unique` (`statistical_id`),
  UNIQUE KEY `organismes_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_consumptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_consumptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiche_id` bigint unsigned NOT NULL,
  `fiche_navette_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `consumed_by` bigint unsigned NOT NULL,
  `product_pharmacy_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_consumptions_fiche_id_foreign` (`fiche_id`),
  KEY `patient_consumptions_product_id_foreign` (`product_id`),
  KEY `patient_consumptions_consumed_by_foreign` (`consumed_by`),
  KEY `patient_consumptions_fiche_navette_item_id_foreign` (`fiche_navette_item_id`),
  CONSTRAINT `patient_consumptions_consumed_by_foreign` FOREIGN KEY (`consumed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_consumptions_fiche_id_foreign` FOREIGN KEY (`fiche_id`) REFERENCES `fiche_navettes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_consumptions_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_consumptions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_docements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_docements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `folder_id` bigint unsigned NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_docements_patient_id_foreign` (`patient_id`),
  KEY `patient_docements_doctor_id_foreign` (`doctor_id`),
  KEY `patient_docements_appointment_id_foreign` (`appointment_id`),
  KEY `patient_docements_folder_id_foreign` (`folder_id`),
  CONSTRAINT `patient_docements_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_docements_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_docements_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_docements_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_trackings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_trackings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiche_navette_item_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `salle_id` bigint unsigned DEFAULT NULL,
  `specialization_id` bigint unsigned DEFAULT NULL,
  `prestation_id` bigint unsigned DEFAULT NULL,
  `check_in_time` timestamp NULL DEFAULT NULL,
  `check_out_time` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_trackings_fiche_navette_item_id_foreign` (`fiche_navette_item_id`),
  KEY `patient_trackings_patient_id_foreign` (`patient_id`),
  KEY `patient_trackings_salle_id_foreign` (`salle_id`),
  KEY `patient_trackings_specialization_id_foreign` (`specialization_id`),
  KEY `patient_trackings_prestation_id_foreign` (`prestation_id`),
  KEY `patient_trackings_created_by_foreign` (`created_by`),
  CONSTRAINT `patient_trackings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_trackings_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_trackings_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_trackings_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_trackings_salle_id_foreign` FOREIGN KEY (`salle_id`) REFERENCES `salls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_trackings_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Parent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateOfBirth` date DEFAULT NULL,
  `Idnum` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL DEFAULT '2',
  `gender` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `age` int DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `firstname_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nss` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `mother_lastname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_clinical_info` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `patients_phone_index` (`phone`),
  KEY `patients_dateofbirth_index` (`dateOfBirth`),
  KEY `patients_created_by_index` (`created_by`),
  KEY `patients_firstname_lastname_index` (`Firstname`,`Lastname`),
  KEY `patients_created_at_index` (`created_at`),
  KEY `patients_updated_at_index` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pavilion_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pavilion_service` (
  `pavilion_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`pavilion_id`,`service_id`),
  KEY `pavilion_service_service_id_foreign` (`service_id`),
  CONSTRAINT `pavilion_service_pavilion_id_foreign` FOREIGN KEY (`pavilion_id`) REFERENCES `pavilions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pavilion_service_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pavilions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pavilions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., Surgical Wing, Outpatient Wing',
  `description` text COLLATE utf8mb4_unicode_ci,
  `service_id` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pavilions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_type_overrides_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_type_overrides_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiche_navette_item_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `original_payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justification` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_type_overrides_log_fiche_navette_item_id_foreign` (`fiche_navette_item_id`),
  KEY `payment_type_overrides_log_user_id_foreign` (`user_id`),
  CONSTRAINT `payment_type_overrides_log_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_type_overrides_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pharmacy_product_id` bigint unsigned NOT NULL,
  `pharmacy_stockage_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pharmacy_product_stockage` (`pharmacy_product_id`,`pharmacy_stockage_id`),
  KEY `pharmacy_inventories_pharmacy_stockage_id_foreign` (`pharmacy_stockage_id`),
  KEY `pharmacy_inventories_expiry_date_quantity_index` (`expiry_date`,`quantity`),
  KEY `pharmacy_inventories_batch_number_index` (`batch_number`),
  KEY `pharmacy_inventories_barcode_index` (`barcode`),
  CONSTRAINT `pharmacy_inventories_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_inventories_pharmacy_stockage_id_foreign` FOREIGN KEY (`pharmacy_stockage_id`) REFERENCES `pharmacy_stockages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_movement_inventory_selections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_movement_inventory_selections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pharmacy_stock_movement_item_id` bigint unsigned NOT NULL,
  `pharmacy_inventory_id` bigint unsigned NOT NULL,
  `selected_quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pharmacy_inventory_selection` (`pharmacy_stock_movement_item_id`,`pharmacy_inventory_id`),
  KEY `psmis_inventory_fk` (`pharmacy_inventory_id`),
  CONSTRAINT `psmis_inventory_fk` FOREIGN KEY (`pharmacy_inventory_id`) REFERENCES `pharmacy_inventories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_product_global_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_product_global_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pharmacy_product_id` bigint unsigned NOT NULL,
  `global_min_threshold` int DEFAULT NULL,
  `global_max_threshold` int DEFAULT NULL,
  `default_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `standard_cost` decimal(10,2) DEFAULT NULL,
  `markup_percentage` decimal(5,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `is_controlled_substance` tinyint(1) NOT NULL DEFAULT '0',
  `requires_prescription` tinyint(1) NOT NULL DEFAULT '0',
  `storage_requirements` json DEFAULT NULL,
  `handling_instructions` json DEFAULT NULL,
  `disposal_instructions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_product_global_settings_pharmacy_product_id_unique` (`pharmacy_product_id`),
  KEY `ppgs_controlled_rx_idx` (`is_controlled_substance`,`requires_prescription`),
  CONSTRAINT `pharmacy_product_global_settings_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medication',
  `description` text COLLATE utf8mb4_unicode_ci,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_of_measure` enum('tablet','capsule','ml','mg','g','kg','piece','box','vial','ampoule','syringe','bottle','tube','patch','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tablet',
  `strength` decimal(10,3) DEFAULT NULL,
  `strength_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dosage_form` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_of_administration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_ingredients` text COLLATE utf8mb4_unicode_ci,
  `inactive_ingredients` text COLLATE utf8mb4_unicode_ci,
  `is_controlled_substance` tinyint(1) NOT NULL DEFAULT '0',
  `controlled_substance_schedule` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_temperature_min` decimal(5,2) DEFAULT NULL,
  `storage_temperature_max` decimal(5,2) DEFAULT NULL,
  `storage_humidity_min` decimal(5,2) DEFAULT NULL,
  `storage_humidity_max` decimal(5,2) DEFAULT NULL,
  `storage_conditions` text COLLATE utf8mb4_unicode_ci,
  `requires_cold_chain` tinyint(1) NOT NULL DEFAULT '0',
  `light_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `shelf_life_days` int DEFAULT NULL,
  `contraindications` text COLLATE utf8mb4_unicode_ci,
  `side_effects` text COLLATE utf8mb4_unicode_ci,
  `drug_interactions` text COLLATE utf8mb4_unicode_ci,
  `warnings` text COLLATE utf8mb4_unicode_ci,
  `precautions` text COLLATE utf8mb4_unicode_ci,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `markup_percentage` decimal(5,2) DEFAULT NULL,
  `therapeutic_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pharmacological_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atc_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ndc_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_prescription` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lot_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_discontinued` tinyint(1) NOT NULL DEFAULT '0',
  `discontinued_date` date DEFAULT NULL,
  `discontinuation_reason` text COLLATE utf8mb4_unicode_ci,
  `regulatory_info` json DEFAULT NULL,
  `quality_control_info` json DEFAULT NULL,
  `packaging_info` json DEFAULT NULL,
  `labeling_info` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_products_barcode_unique` (`barcode`),
  UNIQUE KEY `pharmacy_products_sku_unique` (`sku`),
  KEY `pharmacy_products_category_is_active_index` (`category`,`is_active`),
  KEY `pharmacy_products_manufacturer_is_active_index` (`manufacturer`,`is_active`),
  KEY `pharmacy_products_therapeutic_class_is_active_index` (`therapeutic_class`,`is_active`),
  KEY `pharmacy_products_requires_prescription_is_active_index` (`requires_prescription`,`is_active`),
  KEY `pharmacy_products_is_controlled_substance_is_active_index` (`is_controlled_substance`,`is_active`),
  KEY `pharmacy_products_expiry_date_is_active_index` (`expiry_date`,`is_active`),
  KEY `pharmacy_products_created_at_is_active_index` (`created_at`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_service_product_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_service_product_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `pharmacy_product_id` bigint unsigned NOT NULL,
  `low_stock_threshold` int DEFAULT NULL,
  `critical_stock_threshold` int DEFAULT NULL,
  `max_stock_level` int DEFAULT NULL,
  `reorder_point` int DEFAULT NULL,
  `preferred_supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority_level` int NOT NULL DEFAULT '1',
  `alert_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_reorder` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `color_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pharmacy_service_product` (`service_id`,`pharmacy_product_id`),
  KEY `psps_service_priority_idx` (`service_id`,`priority_level`),
  KEY `psps_product_reorder_idx` (`pharmacy_product_id`,`auto_reorder`),
  CONSTRAINT `pharmacy_service_product_settings_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_service_product_settings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `movement_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pharmacy_product_id` bigint unsigned DEFAULT NULL,
  `requesting_service_id` bigint unsigned DEFAULT NULL,
  `providing_service_id` bigint unsigned DEFAULT NULL,
  `requesting_user_id` bigint unsigned DEFAULT NULL,
  `approving_user_id` bigint unsigned DEFAULT NULL,
  `executing_user_id` bigint unsigned DEFAULT NULL,
  `requested_quantity` decimal(10,2) DEFAULT NULL,
  `approved_quantity` decimal(10,2) DEFAULT NULL,
  `executed_quantity` decimal(10,2) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','in_transfer','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `request_reason` text COLLATE utf8mb4_unicode_ci,
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `execution_notes` text COLLATE utf8mb4_unicode_ci,
  `requested_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `transfer_initiated_at` timestamp NULL DEFAULT NULL,
  `transfer_initiated_by` bigint unsigned DEFAULT NULL,
  `delivery_confirmed_at` timestamp NULL DEFAULT NULL,
  `delivery_confirmed_by` bigint unsigned DEFAULT NULL,
  `delivery_status` enum('good','manque','mixed','damaged') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_notes` text COLLATE utf8mb4_unicode_ci,
  `missing_quantity` decimal(10,2) DEFAULT NULL,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `prescription_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_stock_movements_movement_number_unique` (`movement_number`),
  KEY `pharmacy_stock_movements_pharmacy_product_id_foreign` (`pharmacy_product_id`),
  KEY `pharmacy_stock_movements_requesting_user_id_foreign` (`requesting_user_id`),
  KEY `pharmacy_stock_movements_approving_user_id_foreign` (`approving_user_id`),
  KEY `pharmacy_stock_movements_executing_user_id_foreign` (`executing_user_id`),
  KEY `pharmacy_stock_movements_patient_id_foreign` (`patient_id`),
  KEY `pharmacy_stock_movements_transfer_initiated_by_foreign` (`transfer_initiated_by`),
  KEY `pharmacy_stock_movements_delivery_confirmed_by_foreign` (`delivery_confirmed_by`),
  KEY `pharmacy_stock_movements_status_created_at_index` (`status`,`created_at`),
  KEY `pharmacy_stock_movements_requesting_service_id_status_index` (`requesting_service_id`,`status`),
  KEY `pharmacy_stock_movements_providing_service_id_status_index` (`providing_service_id`,`status`),
  KEY `pharmacy_stock_movements_movement_number_index` (`movement_number`),
  CONSTRAINT `pharmacy_stock_movements_approving_user_id_foreign` FOREIGN KEY (`approving_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_delivery_confirmed_by_foreign` FOREIGN KEY (`delivery_confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_executing_user_id_foreign` FOREIGN KEY (`executing_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_providing_service_id_foreign` FOREIGN KEY (`providing_service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_requesting_service_id_foreign` FOREIGN KEY (`requesting_service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_requesting_user_id_foreign` FOREIGN KEY (`requesting_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_movements_transfer_initiated_by_foreign` FOREIGN KEY (`transfer_initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_stockage_tools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stockage_tools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pharmacy_stockage_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','maintenance','out_of_order') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `maintenance_schedule` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specifications` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_stockage_tools_pharmacy_stockage_id_status_index` (`pharmacy_stockage_id`,`status`),
  KEY `pharmacy_stockage_tools_serial_number_index` (`serial_number`),
  KEY `pharmacy_stockage_tools_type_index` (`type`),
  CONSTRAINT `pharmacy_stockage_tools_pharmacy_stockage_id_foreign` FOREIGN KEY (`pharmacy_stockage_id`) REFERENCES `pharmacy_stockages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_stockages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stockages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `pharmacy_storage_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `manager_id` bigint unsigned DEFAULT NULL,
  `temperature_controlled` tinyint(1) NOT NULL DEFAULT '0',
  `security_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_type` enum('Central Pharmacy (PC)','Service Pharmacy (PS)') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_stockages_manager_id_foreign` (`manager_id`),
  KEY `pharmacy_stockages_status_type_index` (`status`,`type`),
  KEY `pharmacy_stockages_warehouse_type_index` (`warehouse_type`),
  KEY `pharmacy_stockages_location_code_index` (`location_code`),
  KEY `pharmacy_stockages_pharmacy_storage_id_index` (`pharmacy_storage_id`),
  CONSTRAINT `pharmacy_stockages_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stockages_pharmacy_storage_id_foreign` FOREIGN KEY (`pharmacy_storage_id`) REFERENCES `pharmacy_storages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_storages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_storages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `service_id` bigint unsigned DEFAULT NULL,
  `temperature_controlled` tinyint(1) NOT NULL DEFAULT '0',
  `security_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `controlled_substance_vault` tinyint(1) NOT NULL DEFAULT '0',
  `refrigeration_unit` tinyint(1) NOT NULL DEFAULT '0',
  `humidity_controlled` tinyint(1) NOT NULL DEFAULT '0',
  `light_protection` tinyint(1) NOT NULL DEFAULT '0',
  `access_control_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pharmacist_access_only` tinyint(1) NOT NULL DEFAULT '0',
  `dea_registration_required` tinyint(1) NOT NULL DEFAULT '0',
  `temperature_min` decimal(5,1) DEFAULT NULL,
  `temperature_max` decimal(5,1) DEFAULT NULL,
  `humidity_min` decimal(5,1) DEFAULT NULL,
  `humidity_max` decimal(5,1) DEFAULT NULL,
  `monitoring_system` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backup_power` tinyint(1) NOT NULL DEFAULT '0',
  `alarm_system` tinyint(1) NOT NULL DEFAULT '0',
  `compliance_certification` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_inspection_due` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_storages_status_type_index` (`status`,`type`),
  KEY `pharmacy_storages_warehouse_type_index` (`warehouse_type`),
  KEY `pharmacy_storages_location_code_index` (`location_code`),
  KEY `pharmacy_storages_service_id_index` (`service_id`),
  CONSTRAINT `pharmacy_storages_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `placeholder_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placeholder_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `placeholder_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `placeholder_templates_template_id_foreign` (`template_id`),
  KEY `placeholder_templates_placeholder_id_foreign` (`placeholder_id`),
  KEY `placeholder_templates_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `placeholder_templates_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `placeholder_templates_placeholder_id_foreign` FOREIGN KEY (`placeholder_id`) REFERENCES `placeholders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `placeholder_templates_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `placeholders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `placeholders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `specializations_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `placeholders_doctor_id_foreign` (`doctor_id`),
  KEY `placeholders_specializations_id_foreign` (`specializations_id`),
  CONSTRAINT `placeholders_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `placeholders_specializations_id_foreign` FOREIGN KEY (`specializations_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescription_medications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescription_medications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint unsigned NOT NULL,
  `cd_active_substance` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pharmaceutical_form` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dose_per_intake` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_intakes_per_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_or_boxes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `medication_id` bigint unsigned DEFAULT NULL,
  `form` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_times` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency-period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_intakes` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `pills_matin` int DEFAULT NULL,
  `pills_apres_midi` int DEFAULT NULL,
  `pills_midi` int DEFAULT NULL,
  `pills_soir` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescription_medications_prescription_id_foreign` (`prescription_id`),
  KEY `prescription_medications_medication_id_foreign` (`medication_id`),
  CONSTRAINT `prescription_medications_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescription_medications_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `signature_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prescription_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescriptions_consultation_id_index` (`consultation_id`),
  KEY `prescriptions_patient_id_index` (`patient_id`),
  KEY `prescriptions_doctor_id_index` (`doctor_id`),
  KEY `prescriptions_prescription_date_index` (`prescription_date`),
  KEY `prescriptions_patient_id_prescription_date_index` (`patient_id`,`prescription_date`),
  CONSTRAINT `prescriptions_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescriptiontemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptiontemplates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prescription_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescriptiontemplates_doctor_id_foreign` (`doctor_id`),
  KEY `prescriptiontemplates_prescription_id_foreign` (`prescription_id`),
  CONSTRAINT `prescriptiontemplates_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptiontemplates_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestation_packageitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestation_packageitems` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prestation_package_id` bigint unsigned NOT NULL,
  `prestation_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prestation_packageitems_prestation_package_id_foreign` (`prestation_package_id`),
  KEY `prestation_packageitems_prestation_id_foreign` (`prestation_id`),
  CONSTRAINT `prestation_packageitems_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_packageitems_prestation_package_id_foreign` FOREIGN KEY (`prestation_package_id`) REFERENCES `prestation_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestation_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestation_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prestation_packages_created_by_foreign` (`created_by`),
  KEY `prestation_packages_updated_by_foreign` (`updated_by`),
  CONSTRAINT `prestation_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prestation_packages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestation_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestation_pricing` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prestation_id` bigint unsigned NOT NULL,
  `annex_id` bigint unsigned NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  `company_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `patient_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_price_exceeded` tinyint(1) NOT NULL DEFAULT '0',
  `original_company_share` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_patient_share` decimal(10,2) NOT NULL DEFAULT '0.00',
  `updated_by_id` bigint unsigned NOT NULL,
  `avenant_id` bigint unsigned NOT NULL,
  `subname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `head` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activation_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contract_percentage_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prestation_pricing_unique` (`prestation_id`,`annex_id`,`contract_percentage_id`),
  KEY `prestation_pricing_annex_id_foreign` (`annex_id`),
  KEY `prestation_pricing_avenant_id_foreign` (`avenant_id`),
  KEY `prestation_pricing_updated_by_id_foreign` (`updated_by_id`),
  KEY `prestation_pricing_contract_percentage_id_foreign` (`contract_percentage_id`),
  CONSTRAINT `prestation_pricing_annex_id_foreign` FOREIGN KEY (`annex_id`) REFERENCES `annexes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_pricing_avenant_id_foreign` FOREIGN KEY (`avenant_id`) REFERENCES `avenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_pricing_contract_percentage_id_foreign` FOREIGN KEY (`contract_percentage_id`) REFERENCES `contract_percentages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_pricing_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_pricing_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestation_remises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestation_remises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remise_id` bigint unsigned NOT NULL,
  `prestation_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prestation_remises_remise_id_foreign` (`remise_id`),
  KEY `prestation_remises_prestation_id_foreign` (`prestation_id`),
  CONSTRAINT `prestation_remises_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_remises_remise_id_foreign` FOREIGN KEY (`remise_id`) REFERENCES `remises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prestations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `internal_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Official code for invoices',
  `description` text COLLATE utf8mb4_unicode_ci,
  `service_id` bigint unsigned NOT NULL,
  `specialization_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_price` decimal(15,2) NOT NULL COMMENT 'Standard price (HT)',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tva_const_prestation` decimal(8,2) DEFAULT NULL,
  `night_tariff` decimal(10,2) DEFAULT NULL,
  `consumables_cost` decimal(15,2) DEFAULT NULL COMMENT 'For profitability calculation',
  `default_payment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pre-pay, post-pay, versement',
  `min_versement_amount` decimal(15,2) DEFAULT NULL,
  `default_duration_minutes` int DEFAULT NULL COMMENT 'Estimated time to perform the service',
  `required_prestations_info` json DEFAULT NULL,
  `patient_instructions` text COLLATE utf8mb4_unicode_ci,
  `required_consents` json DEFAULT NULL,
  `requires_hospitalization` tinyint(1) NOT NULL DEFAULT '0',
  `default_hosp_nights` int DEFAULT NULL,
  `required_modality_type_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_social_security_reimbursable` tinyint(1) NOT NULL DEFAULT '0',
  `reimbursement_conditions` text COLLATE utf8mb4_unicode_ci,
  `non_applicable_discount_rules` json DEFAULT NULL,
  `fee_distribution_model` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `primary_doctor_share` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_doctor_is_percentage` tinyint(1) NOT NULL DEFAULT '0',
  `assistant_doctor_share` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assistant_doctor_is_percentage` tinyint(1) NOT NULL DEFAULT '0',
  `technician_share` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `technician_is_percentage` tinyint(1) NOT NULL DEFAULT '0',
  `clinic_share` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clinic_is_percentage` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Tarif_de_nuit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `Tarif_de_nuit_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `convenience_prix` decimal(15,2) NOT NULL COMMENT 'convenience Price',
  `need_an_appointment` tinyint(1) NOT NULL DEFAULT '0',
  `Urgent_Prestation` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prestations_internal_code_unique` (`internal_code`),
  KEY `prestations_service_id_foreign` (`service_id`),
  KEY `prestations_specialization_id_foreign` (`specialization_id`),
  KEY `prestations_required_modality_type_id_foreign` (`required_modality_type_id`),
  CONSTRAINT `prestations_required_modality_type_id_foreign` FOREIGN KEY (`required_modality_type_id`) REFERENCES `modality_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prestations_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestations_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_global_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_global_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `setting_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` json NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_global_settings_product_id_setting_key_unique` (`product_id`,`setting_key`),
  CONSTRAINT `product_global_settings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('Medical Supplies','Equipment','Medication','Others') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_clinical` tinyint(1) NOT NULL DEFAULT '0',
  `code_interne` int DEFAULT NULL,
  `code_pch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_medicament` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `boite_de` int DEFAULT NULL,
  `quantity_by_box` tinyint(1) NOT NULL DEFAULT '0',
  `nom_commercial` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_request_approval` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proforma_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proforma_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proforma_invoice_id` bigint unsigned NOT NULL,
  `prestation_id` bigint unsigned NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `patient_share` decimal(15,2) DEFAULT NULL,
  `organisme_share` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proforma_invoice_items_proforma_invoice_id_foreign` (`proforma_invoice_id`),
  KEY `proforma_invoice_items_prestation_id_foreign` (`prestation_id`),
  CONSTRAINT `proforma_invoice_items_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proforma_invoice_items_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proforma_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proforma_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proforma_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `organisme_id` bigint unsigned NOT NULL,
  `adherent_id` bigint unsigned DEFAULT NULL,
  `pricing_date` date NOT NULL COMMENT 'The date used to calculate the rules',
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'draft, sent, accepted, expired',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proforma_invoices_proforma_number_unique` (`proforma_number`),
  KEY `proforma_invoices_patient_id_foreign` (`patient_id`),
  KEY `proforma_invoices_organisme_id_foreign` (`organisme_id`),
  KEY `proforma_invoices_adherent_id_foreign` (`adherent_id`),
  CONSTRAINT `proforma_invoices_adherent_id_foreign` FOREIGN KEY (`adherent_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proforma_invoices_organisme_id_foreign` FOREIGN KEY (`organisme_id`) REFERENCES `organismes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proforma_invoices_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refund_authorizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refund_authorizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiche_navette_item_id` bigint unsigned NOT NULL,
  `item_dependency_id` bigint unsigned DEFAULT NULL,
  `requested_by_id` bigint unsigned NOT NULL,
  `authorized_by_id` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_amount` decimal(15,2) NOT NULL,
  `authorized_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired','used') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refund_authorizations_item_dependency_id_foreign` (`item_dependency_id`),
  KEY `refund_authorizations_status_created_at_index` (`status`,`created_at`),
  KEY `refund_authorizations_fiche_navette_item_id_status_index` (`fiche_navette_item_id`,`status`),
  KEY `refund_authorizations_requested_by_id_status_index` (`requested_by_id`,`status`),
  KEY `refund_authorizations_authorized_by_id_status_index` (`authorized_by_id`,`status`),
  KEY `refund_authorizations_expires_at_index` (`expires_at`),
  KEY `refund_authorizations_priority_index` (`priority`),
  CONSTRAINT `refund_authorizations_authorized_by_id_foreign` FOREIGN KEY (`authorized_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refund_authorizations_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refund_authorizations_item_dependency_id_foreign` FOREIGN KEY (`item_dependency_id`) REFERENCES `item_dependencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refund_authorizations_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_approvers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_approvers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `comments` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remise_approvers_user_id_foreign` (`user_id`),
  KEY `remise_approvers_approver_id_foreign` (`approver_id`),
  CONSTRAINT `remise_approvers_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remise_approvers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percentage','fixed_amount') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `funding_source` enum('par_clinique','par_docteur','par_personnel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_request_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_request_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remise_request_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('receiver','approver') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `acted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remise_request_approvals_remise_request_id_foreign` (`remise_request_id`),
  KEY `remise_request_approvals_user_id_foreign` (`user_id`),
  CONSTRAINT `remise_request_approvals_remise_request_id_foreign` FOREIGN KEY (`remise_request_id`) REFERENCES `remise_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remise_request_approvals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_request_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_request_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remise_request_id` bigint unsigned NOT NULL,
  `receiver_id` bigint unsigned NOT NULL,
  `type` enum('request','response') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remise_request_notifications_remise_request_id_foreign` (`remise_request_id`),
  KEY `remise_request_notifications_receiver_id_foreign` (`receiver_id`),
  CONSTRAINT `remise_request_notifications_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remise_request_notifications_remise_request_id_foreign` FOREIGN KEY (`remise_request_id`) REFERENCES `remise_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_request_prestation_contributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_request_prestation_contributions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remise_request_prestation_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('doctor','user') COLLATE utf8mb4_unicode_ci NOT NULL,
  `proposed_amount` decimal(10,2) DEFAULT NULL,
  `approved_amount` decimal(10,2) DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rrp_contrib_prest_fk` (`remise_request_prestation_id`),
  KEY `rrp_contrib_user_fk` (`user_id`),
  KEY `rrp_contrib_approved_fk` (`approved_by`),
  CONSTRAINT `rrp_contrib_approved_fk` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rrp_contrib_prest_fk` FOREIGN KEY (`remise_request_prestation_id`) REFERENCES `remise_request_prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rrp_contrib_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_request_prestations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_request_prestations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remise_request_id` bigint unsigned NOT NULL,
  `prestation_id` bigint unsigned NOT NULL,
  `proposed_amount` decimal(10,2) DEFAULT NULL,
  `final_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remise_request_prestations_remise_request_id_foreign` (`remise_request_id`),
  KEY `remise_request_prestations_prestation_id_foreign` (`prestation_id`),
  CONSTRAINT `remise_request_prestations_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remise_request_prestations_remise_request_id_foreign` FOREIGN KEY (`remise_request_id`) REFERENCES `remise_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint unsigned NOT NULL,
  `receiver_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `status` enum('pending','accepted','rejected','applied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remise_requests_sender_id_foreign` (`sender_id`),
  KEY `remise_requests_receiver_id_foreign` (`receiver_id`),
  KEY `remise_requests_patient_id_foreign` (`patient_id`),
  CONSTRAINT `remise_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `remise_requests_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remise_requests_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remise_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remise_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remise_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remise_users_remise_id_user_id_unique` (`remise_id`,`user_id`),
  KEY `remise_users_user_id_foreign` (`user_id`),
  CONSTRAINT `remise_users_remise_id_foreign` FOREIGN KEY (`remise_id`) REFERENCES `remises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remise_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prestation_id` bigint unsigned DEFAULT NULL,
  `fiche_navette_item_id` bigint unsigned DEFAULT NULL,
  `requester_id` bigint unsigned DEFAULT NULL,
  `approver_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pending-approval, approved, declined',
  `policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'par-clinic, par-doctor, par-personel',
  `justification` text COLLATE utf8mb4_unicode_ci,
  `type` enum('fixed','percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remises_name_unique` (`name`),
  UNIQUE KEY `remises_code_unique` (`code`),
  KEY `remises_prestation_id_foreign` (`prestation_id`),
  KEY `remises_fiche_navette_item_id_foreign` (`fiche_navette_item_id`),
  KEY `remises_requester_id_foreign` (`requester_id`),
  KEY `remises_approver_id_foreign` (`approver_id`),
  CONSTRAINT `remises_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `remises_fiche_navette_item_id_foreign` FOREIGN KEY (`fiche_navette_item_id`) REFERENCES `fiche_navette_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remises_prestation_id_foreign` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `remises_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_transaction_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `request_transaction_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `request_transaction_id` bigint unsigned NOT NULL,
  `candidate_user_ids` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_transaction_approvals_requested_by_foreign` (`requested_by`),
  KEY `request_transaction_approvals_approved_by_foreign` (`approved_by`),
  KEY `request_transaction_approvals_request_transaction_id_foreign` (`request_transaction_id`),
  CONSTRAINT `request_transaction_approvals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `request_transaction_approvals_request_transaction_id_foreign` FOREIGN KEY (`request_transaction_id`) REFERENCES `coffre_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_transaction_approvals_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., Hospitalisation, Consultation, Radiology, Operating Theater, Waiting Area',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `room_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_types_name_unique` (`name`),
  KEY `room_types_service_id_foreign` (`service_id`),
  CONSTRAINT `room_types_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pavilion_id` bigint unsigned DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., Room 201, Consultation Box 5, Operating Theater 1',
  `number_of_people` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nightly_price` decimal(15,2) DEFAULT NULL,
  `service_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT 'free, occupied, cleaning, maintenance',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `room_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rooms_pavilion_id_foreign` (`pavilion_id`),
  KEY `rooms_service_id_foreign` (`service_id`),
  KEY `rooms_room_type_id_foreign` (`room_type_id`),
  CONSTRAINT `rooms_pavilion_id_foreign` FOREIGN KEY (`pavilion_id`) REFERENCES `pavilions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rooms_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rooms_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `defult_specialization_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salls_defult_specialization_id_foreign` (`defult_specialization_id`),
  CONSTRAINT `salls_defult_specialization_id_foreign` FOREIGN KEY (`defult_specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `day_of_week` enum('sunday','monday','tuesday','wednesday','thursday','friday','saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_period` enum('morning','afternoon') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `date` date DEFAULT NULL,
  `number_of_patients_per_day` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `break_duration` int DEFAULT NULL COMMENT 'Break duration in minutes',
  `break_times` json DEFAULT NULL COMMENT 'JSON array of break start times',
  `excluded_dates` json DEFAULT NULL COMMENT 'Dates when this schedule does not apply',
  `modified_times` json DEFAULT NULL COMMENT 'Special timing modifications for specific dates',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedules_doctor_id_day_of_week_shift_period_deleted_at_unique` (`doctor_id`,`day_of_week`,`shift_period`,`deleted_at`),
  KEY `schedules_created_by_foreign` (`created_by`),
  KEY `schedules_updated_by_foreign` (`updated_by`),
  KEY `schedules_doctor_id_index` (`doctor_id`),
  KEY `schedules_day_of_week_index` (`day_of_week`),
  KEY `schedules_is_active_index` (`is_active`),
  KEY `schedules_doctor_id_day_of_week_index` (`doctor_id`,`day_of_week`),
  KEY `schedules_doctor_id_is_active_index` (`doctor_id`,`is_active`),
  KEY `schedules_created_at_index` (`created_at`),
  KEY `schedules_updated_at_index` (`updated_at`),
  KEY `schedules_deleted_at_index` (`deleted_at`),
  CONSTRAINT `schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `schedules_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_demand_item_fournisseurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_demand_item_fournisseurs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_demand_purchasing_item_id` bigint unsigned NOT NULL,
  `fournisseur_id` bigint unsigned NOT NULL,
  `assigned_quantity` int NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sd_item_fournisseur_unique` (`service_demand_purchasing_item_id`,`fournisseur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_demand_purchasing_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_demand_purchasing_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_demand_purchasing_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `quantity_by_box` tinyint(1) NOT NULL DEFAULT '0',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sdpi_sdp_id` (`service_demand_purchasing_id`),
  KEY `fk_sdpi_product_id` (`product_id`),
  CONSTRAINT `fk_sdpi_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sdpi_sdp_id` FOREIGN KEY (`service_demand_purchasing_id`) REFERENCES `service_demand_purchasings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_demand_purchasings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_demand_purchasings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned DEFAULT NULL,
  `demand_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expected_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `proforma_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `boncommend_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `proforma_confirmed_at` timestamp NULL DEFAULT NULL,
  `boncommend_confirmed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_demand_purchasings_service_id_foreign` (`service_id`),
  CONSTRAINT `service_demand_purchasings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_product_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_product_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_forme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `low_stock_threshold` decimal(10,2) NOT NULL DEFAULT '10.00',
  `critical_stock_threshold` decimal(10,2) DEFAULT NULL,
  `max_stock_level` decimal(10,2) DEFAULT NULL,
  `reorder_point` decimal(10,2) DEFAULT NULL,
  `expiry_alert_days` int NOT NULL DEFAULT '30',
  `min_shelf_life_days` int NOT NULL DEFAULT '90',
  `email_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `sms_alerts` tinyint(1) NOT NULL DEFAULT '0',
  `alert_frequency` enum('immediate','daily','weekly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'immediate',
  `preferred_supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_tracking` tinyint(1) NOT NULL DEFAULT '1',
  `location_tracking` tinyint(1) NOT NULL DEFAULT '1',
  `auto_reorder` tinyint(1) NOT NULL DEFAULT '0',
  `custom_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_code` enum('default','red','orange','yellow','green','blue','purple') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `priority` enum('low','normal','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_product_settings_unique` (`service_id`,`product_id`,`product_forme`),
  KEY `service_product_settings_product_id_foreign` (`product_id`),
  CONSTRAINT `service_product_settings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_product_settings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `image_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_abv` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agmentation` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `specializations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specializations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `service_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `specializations_name_unique` (`name`),
  KEY `specializations_service_id_foreign` (`service_id`),
  KEY `specializations_created_by_index` (`created_by`),
  KEY `specializations_created_at_index` (`created_at`),
  KEY `specializations_updated_at_index` (`updated_at`),
  KEY `specializations_deleted_at_index` (`deleted_at`),
  CONSTRAINT `specializations_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `specializations_salls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specializations_salls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `specialization_id` bigint unsigned NOT NULL,
  `sall_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `specializations_salls_sall_id_foreign` (`sall_id`),
  KEY `fk_specializations_salls_specialization_id` (`specialization_id`),
  CONSTRAINT `fk_specializations_salls_specialization_id` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `specializations_salls_sall_id_foreign` FOREIGN KEY (`sall_id`) REFERENCES `salls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `specialty_minimum_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specialty_minimum_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `convention_id` bigint unsigned DEFAULT NULL,
  `avenant_id` bigint unsigned DEFAULT NULL,
  `specialization_id` bigint unsigned DEFAULT NULL,
  `minimum_required_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `specialty_minimum_rules_convention_id_foreign` (`convention_id`),
  KEY `specialty_minimum_rules_avenant_id_foreign` (`avenant_id`),
  KEY `specialty_minimum_rules_specialization_id_foreign` (`specialization_id`),
  CONSTRAINT `specialty_minimum_rules_avenant_id_foreign` FOREIGN KEY (`avenant_id`) REFERENCES `avenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `specialty_minimum_rules_convention_id_foreign` FOREIGN KEY (`convention_id`) REFERENCES `conventions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `specialty_minimum_rules_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movement_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movement_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_movement_id` bigint unsigned NOT NULL,
  `stock_movement_item_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movement_audit_logs_stock_movement_item_id_foreign` (`stock_movement_item_id`),
  KEY `stock_movement_audit_logs_stock_movement_id_created_at_index` (`stock_movement_id`,`created_at`),
  KEY `stock_movement_audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `stock_movement_audit_logs_action_created_at_index` (`action`,`created_at`),
  CONSTRAINT `stock_movement_audit_logs_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movement_audit_logs_stock_movement_item_id_foreign` FOREIGN KEY (`stock_movement_item_id`) REFERENCES `stock_movement_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movement_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movement_inventory_selections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movement_inventory_selections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_movement_item_id` bigint unsigned NOT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `selected_quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inventory_selection` (`stock_movement_item_id`,`inventory_id`),
  KEY `smis_inventory_fk` (`inventory_id`),
  CONSTRAINT `smis_inventory_fk` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `smis_item_fk` FOREIGN KEY (`stock_movement_item_id`) REFERENCES `stock_movement_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movement_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movement_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `quantity_by_box` tinyint(1) NOT NULL DEFAULT '0',
  `provided_quantity` decimal(10,2) DEFAULT NULL,
  `stock_movement_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `requested_quantity` decimal(10,2) DEFAULT NULL,
  `approved_quantity` decimal(10,2) DEFAULT NULL,
  `executed_quantity` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `stock_movement_items_stock_movement_id_foreign` (`stock_movement_id`),
  KEY `stock_movement_items_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_movement_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movement_items_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `movement_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `requesting_service_id` bigint unsigned DEFAULT NULL,
  `providing_service_id` bigint unsigned DEFAULT NULL,
  `requesting_user_id` bigint unsigned DEFAULT NULL,
  `approving_user_id` bigint unsigned DEFAULT NULL,
  `executing_user_id` bigint unsigned DEFAULT NULL,
  `requested_quantity` decimal(10,2) DEFAULT NULL,
  `approved_quantity` decimal(10,2) DEFAULT NULL,
  `executed_quantity` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `request_reason` text COLLATE utf8mb4_unicode_ci,
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `execution_notes` text COLLATE utf8mb4_unicode_ci,
  `requested_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `expected_delivery_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_movements_movement_number_unique` (`movement_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stockage_tools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockage_tools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stockage_id` bigint unsigned NOT NULL,
  `tool_type` enum('RY','AR','CF','FR','CS','CH','PL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool_number` int NOT NULL,
  `block` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shelf_level` int DEFAULT NULL,
  `location_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stockage_tools_stockage_id_tool_type_tool_number_unique` (`stockage_id`,`tool_type`,`tool_number`),
  CONSTRAINT `stockage_tools_stockage_id_foreign` FOREIGN KEY (`stockage_id`) REFERENCES `stockages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stockages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int DEFAULT NULL,
  `type` enum('warehouse','pharmacy','laboratory','emergency','storage_room','cold_room') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warehouse',
  `status` enum('active','inactive','maintenance','under_construction') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `service_id` bigint unsigned DEFAULT NULL,
  `temperature_controlled` tinyint(1) NOT NULL DEFAULT '0',
  `security_level` enum('low','medium','high','restricted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_type` enum('Central Pharmacy (PC)','Service Pharmacy (PS)') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stockages_service_id_foreign` (`service_id`),
  CONSTRAINT `stockages_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `surgicals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surgicals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `procedure_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `surgery_date` date NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `surgicals_patient_id_foreign` (`patient_id`),
  CONSTRAINT `surgicals_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `folder_id` bigint unsigned DEFAULT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `templates_folder_id_foreign` (`folder_id`),
  KEY `templates_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `templates_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `templates_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_bank_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_bank_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('card','cheque') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `approval_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` bigint unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `approved_at` timestamp NULL DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_bank_requests_transaction_id_foreign` (`transaction_id`),
  KEY `transaction_bank_requests_requested_by_foreign` (`requested_by`),
  KEY `transaction_bank_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `transaction_bank_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaction_bank_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_bank_requests_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `financial_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transfer_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maximum` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_approvals_user_id_foreign` (`user_id`),
  CONSTRAINT `transfer_approvals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_payment_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_payment_method` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `payment_method_key` json NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_payment_method_user_id_foreign` (`user_id`),
  CONSTRAINT `user_payment_method_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_refund_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_refund_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `granter_id` bigint unsigned NOT NULL,
  `grantee_id` bigint unsigned NOT NULL,
  `is_able_to_force` tinyint(1) DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urp_unique` (`granter_id`,`grantee_id`,`is_able_to_force`),
  KEY `user_refund_permissions_grantee_id_foreign` (`grantee_id`),
  CONSTRAINT `user_refund_permissions_grantee_id_foreign` FOREIGN KEY (`grantee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_refund_permissions_granter_id_foreign` FOREIGN KEY (`granter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_specializations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_specializations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `specialization_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_specializations_user_id_foreign` (`user_id`),
  KEY `user_specializations_specialization_id_foreign` (`specialization_id`),
  CONSTRAINT `user_specializations_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_specializations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_specialties` (
  `user_id` bigint unsigned NOT NULL,
  `specialization_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`specialization_id`),
  KEY `user_specialties_specialization_id_foreign` (`specialization_id`),
  CONSTRAINT `user_specialties_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_specialties_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `role` enum('admin','receptionist','doctor','SuperAdmin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'receptionist',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT '2',
  `is_active` int NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `professional_license_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_account_details` json DEFAULT NULL,
  `personal_discount_ceiling` decimal(10,2) DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `fichenavatte_max` decimal(10,2) DEFAULT NULL,
  `salary` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_service_id_foreign` (`service_id`),
  KEY `users_manager_id_foreign` (`manager_id`),
  KEY `users_updated_at_index` (`updated_at`),
  CONSTRAINT `users_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `waitlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `waitlist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `specialization_id` bigint unsigned NOT NULL,
  `is_Daily` tinyint(1) NOT NULL,
  `created_by` int NOT NULL,
  `appointmentId` int DEFAULT NULL,
  `importance` int DEFAULT '0',
  `MoveToEnd` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `waitlist_doctor_id_index` (`doctor_id`),
  KEY `waitlist_patient_id_index` (`patient_id`),
  KEY `waitlist_specialization_id_index` (`specialization_id`),
  KEY `waitlist_importance_index` (`importance`),
  KEY `waitlist_doctor_id_importance_index` (`doctor_id`,`importance`),
  CONSTRAINT `waitlist_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `waitlist_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `waitlist_specialization_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_12_11_200511_create_specializations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_12_11_200525_create_doctors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_12_11_200525_create_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_12_11_200527_create_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_12_27_213830_create_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_12_27_213839_add_schedule_id_to_doctors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_01_02_095527_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_01_15_100000_create_approval_persons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_01_15_100001_create_bon_commend_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_01_15_120000_add_is_required_approval_to_products',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_01_15_130000_add_price_to_factureproformas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_01_15_130001_add_price_to_bon_commends_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_01_20_082127_create_excluded_dates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_01_20_135211_create_appointment_available_month',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_01_22_072724_create_waitlist_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_01_22_142417_add_column_appointments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_02_12_203219_create_appointment_forcers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_02_26_120456_create_add_columns_appointment_force_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_04_10_080954_add_updated_by_to_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_04_12_120455_create_folders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_04_12_132553_create_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_04_12_132617_create_placeholders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_04_12_132636_create_placeholder_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_04_12_140016_create_attributes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_04_12_142938_create_consultations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_04_12_143103_create_consultation_placeholder_attributes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_04_15_163000_create_attributes_placeholder_doctors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_04_19_105821_add_content_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_04_23_150429_add_new__input_type_to_attributes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_05_19_132447_create_patient_docements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_05_28_110411_create_prescriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_05_28_110459_create_prescription_medications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_05_29_000000_add_attribute_id_to_placeholder_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_05_29_102655_add_age_and_weight_to_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_05_29_add_doctor_id_to_prescriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_06_03_085528_create_surgicals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_06_03_085550_create_chronic_diseases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_06_03_085618_create_family_diseases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_06_03_134512_create_prescriptiontemplates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_06_03_225542_create_allergies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_06_12_112233_create_medications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_06_14_121148_update_prescription_medications_table_structure',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_06_16_144943_create_doctor_medication_favorites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_06_16_145026_create_medication_doctor_favorats__table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_06_18_072954_create_salls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_06_18_073008_create_specializations_salls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_06_26_144741_create_consultationworkspaces_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_06_26_144814_create_consultationworkspace_lists_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_06_30_072530_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_07_01_075107_create_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_07_01_075420_create_modality_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_07_01_075655_create_remise_policies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_07_01_075656_create_organismes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_07_01_075657_create_pavilions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_07_01_081208_add_clinic_details_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_07_01_082949_create_rooms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_07_01_083636_create_beds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_07_01_084316_create_modalities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_07_01_084616_create_authorized_modality_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_07_01_084655_create_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_07_01_084656_create_fiche_navettes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_07_01_084657_create_fiche_navette_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_07_01_084735_create_fee_distribution_models_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_07_01_084909_create_conventions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_07_01_084944_create_avenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_07_01_085023_create_convention_rules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_07_01_085103_create_ayants_droit_rules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_07_01_085143_create_specialty_minimum_rules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_07_01_085319_add_balance_to_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_07_02_092552_add_consultation_timestamps_to_your_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_07_05_072455_create_proforma_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_07_05_072524_create_proforma_invoice_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_07_05_072550_create_b2b_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_07_05_072646_create_b2b_invoice_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_07_05_072730_create_credit_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_07_05_072746_create_payment_type_overrides_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_07_05_072828_create_caisses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_07_05_072833_create_caisse_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_07_05_072834_create_financial_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_07_05_072854_create_caisse_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_07_05_072859_create_coffres_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_07_05_073008_create_banks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_07_05_073009_create_coffre_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_07_05_073039_create_banque_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_07_05_073056_create_demandes_de_fonds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_07_05_093127_create_userspecialties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_07_06_142259_add_your_columns_to_modalities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_07_07_092806_add_your_columns_to_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_07_07_113650_add_your_columns_to_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_07_08_110027_add_financial_and_operational_fields_to_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_07_08_154854_add_new_cloumn_serviceid',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_07_09_135512_add_new_to_organismes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_07_10_142130_create_room_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_07_10_142921_add_location_room_number_and_room_type_id_to_rooms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_07_12_093253_create_pavilion_service_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_07_13_095313_add_pill_counts_to_prescription_medications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_07_15_093500_create_organisme_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_07_15_142720_create_agreements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_07_15_142805_create_conventions_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_07_16_070409_drop_start_end_date_from_conventions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_07_16_143603_create_annexes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_07_17_153524_create_prestation_pricing_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_07_20_082353_modify_avenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_07_20_100354_update_prestation_pricing_unique_constraint_add_avenant_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_07_20_100355_add_head_to_conventions_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_07_20_154608_add_activation_at_to_conventions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_07_21_113959_add_extra_fields_to_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_07_26_140533_add_new_filed',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_07_27_084217_add_new_field_to_prestationpricing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_07_28_124737_create_user_payment_method_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_07_29_085530_create_remises_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_07_29_090116_create_remise_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_07_29_095251_create_prestation_remises_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_07_29_154645_create_moality_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_07_30_000001_create_modality_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_07_30_000002_add_schedule_fields_to_modalities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_07_30_000003_create_modality_available_months_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_07_30_084446_create_appointment_modality_forces_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_07_30_084447_create_crm_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_08_03_074041_add_new_field_modalities',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_08_05_074156_create_prestation_packages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_08_05_074228_create_prestation_packageitems_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_08_05_085004_add_new_field_package_fiche_navette_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_08_06_113156_create_item_dependencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_08_07_074154_add_new_filed_to_fiche_navette_items_convention',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_08_10_100134_create_fiche_navette_custom_packages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_08_11_104310_add_new_field_in_organism',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_08_11_144416_add_new_field_doctors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_08_11_151926_add_new_field_prestation',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_08_16_115530_add_new_field_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_08_17_071427_create_remise_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_08_17_071428_table-remise_request_notifications',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_08_17_080911_create_remise_request_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_08_17_090712_create_remise_request_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_08_17_090848_create_remise_request_prestation_contributions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_08_18_114624_create_remise_approvers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_08_20_111005_create_appointment_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_08_21_150427_add_new_filed_to_caisses',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_08_23_084327_add_new_field_caisse_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_08_23_090109_create_caisse_session_denominations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2025_08_24_082830_add_new_field_coffre_transactions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2025_08_24_091846_create_bank_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2025_08_24_103519_create_bank_account_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2025_08_25_111725_create_transferes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2025_08_26_085704_add_financial_fields_to_fiche_navette_items_and_dependencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2025_08_27_104412_create_user_specializations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2025_08_28_103804_create_refund_authorizations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2025_08_31_144243_create_transaction_bank_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2025_08_31_155656_remove_item_fields_from_transaction_bank_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2025_08_31_155940_add_status_and_approved_by_to_financial_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2025_09_01_000000_create_user_refund_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2025_09_02_080308_create_refund_approve_permission',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2025_09_03_000000_add_approval_document_to_transaction_bank_requests',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2025_09_03_094600_add_new_field_to_table_caisse_session',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2025_09_03_104134_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2025_09_03_133429_add_new_field_coffre_transactions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2025_09_03_134112_create_stockages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2025_09_03_134113_add_service_id_to_stockages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2025_09_03_140424_create_transfer_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2025_09_03_151430_create_request_transaction_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2025_09_03_153446_add_status_to_coffre_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2025_09_03_153511_add_candidate_user_ids_to_request_transaction_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2025_09_04_114656_add_new_bank_account_transactions_field',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2025_09_04_122525_drop_manager_id_from_stockages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2025_09_04_122903_add_location_code_and_warehouse_type_to_stockages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2025_09_04_132826_create_stockage_tools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2025_09_04_141654_add_service_abv_to_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2025_09_04_151207_add_attachment_to_coffre_transactions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2025_09_05_101200_add_reason_to_bank_account_transactions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2025_09_06_074634_create_bank_account_transaction_packs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2025_09_06_080745_add_location_code_to_stockage_tools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2025_09_06_084020_create_bank_account_transaction_pack_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2025_09_06_084850_add_user_id_to_bank_account_transaction_packs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2025_09_06_084901_add_user_id_to_bank_account_transaction_packs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2025_09_06_090400_create_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2025_09_06_090416_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2025_09_06_091211_add_new_bank_account_transactions_coffre_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2025_09_06_091254_add_serial_purchase_barcode_to_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2025_09_06_100414_add_total_units_to_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2025_09_06_121100_modify_inventory_unique_constraint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2025_09_06_122027_modify_inventory_unique_constraint_composite',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2025_09_06_134915_add_global_min_alert_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2025_09_06_134930_create_service_product_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2025_09_08_082718_create_product_global_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2025_09_08_091418_modify_product_global_settings_add_product_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2025_09_08_093715_update_products_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2025_09_08_104729_create_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2025_09_08_104747_create_stock_movement_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2025_09_08_111413_make_product_id_nullable_in_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2025_09_08_111458_make_quantity_fields_nullable_in_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2025_09_08_113309_add_attachment_fields_to_transaction_bank_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2025_09_09_120000_add_quantity_mode_to_stock_movement_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2025_09_10_000001_add_indexes_for_performance_optimization',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2025_09_10_111219_create_stock_movement_inventory_selections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2025_09_13_171333_add_service_id_and_is_active_to_specializations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2025_09_15_081229_add_provided_quantity_to_stock_movement_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2025_09_17_084130_create_stock_movement_audit_logs_table_fixed',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2025_09_17_122836_fix_product_global_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2025_09_17_131659_add_doctor_and_specialization_to_folders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2025_09_17_140430_add_stock_movement_columns_to_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2025_09_17_141351_add_columns_to_stock_movement_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2025_09_17_142435_add_quantity_by_box_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2025_09_18_100011_add_new_prestation_field',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2025_09_18_100807_add_new_prestation_field',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2025_09_22_114015_create_fournisseurs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2025_09_22_114050_create_fournisseur_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2025_09_22_123014_fix_financial_transactions_item_dependency_foreign_key',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2025_09_22_155713_create_service_demend_purchcings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2025_09_22_160131_create_service_demend_purchcing_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2025_09_22_164205_add_foreign_keys_to_service_demand_purchasing_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2025_09_23_120036_add_quantity_by_box_to_service_demand_purchasing_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2025_09_23_134327_create_bon_commends_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2025_09_23_134341_create_bon_commends_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2025_09_23_134358_create_bon_receptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2025_09_23_135600_create_factureproformas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2025_09_23_135601_create_factureproforma_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2025_09_23_172104_create_service_demand_item_fournisseurs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2025_09_24_124446_create_bon_commend_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2025_09_24_131951_add_pdf_content_to_bon_commends_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2025_09_24_131957_add_pdf_content_to_factureproformas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2025_09_24_135844_add_bon_commend_id_to_bon_commend_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2025_09_24_180306_create_facture_proforma_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2025_09_24_180321_create_bon_commend_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2025_09_24_191452_remove_unit_price_from_bon_commend_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2025_09_25_092543_add_workflow_tracking_fields_to_purchasing_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2025_09_25_121235_create_bon_receptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2025_09_25_121303_create_bon_reception_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2025_09_25_174957_create_bon_entrees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2025_09_25_175013_create_bon_entree_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2025_09_28_152922_add_new_filed_companion_fichenvatte',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2025_09_28_161713_create_patient_consumptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2025_09_28_161823_add_companion_id_to_fiche_navettes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2025_09_29_171005_add_new_field_fichenavette',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2025_09_30_123208_create_doctor_emergency_plannings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2025_09_30_151146_create_bon_reception_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2025_09_30_151150_create_bon_entree_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2025_09_30_151155_create_facture_proforma_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2025_09_30_152454_add_attachments_to_bon_commends_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2025_09_30_152509_add_attachments_to_bon_receptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2025_09_30_152526_add_attachments_to_bon_entrees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2025_09_30_152533_add_attachments_to_factureproformas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2025_10_01_000001_add_is_nursing_flag_to_fiche_navette_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2025_10_01_000002_add_fiche_navette_item_id_to_patient_consumptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2025_10_02_123207_add_new_fieled_table_fichenavette',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2025_10_04_112701_add_approval_tracking_fields_to_bon_commends_and_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2025_10_04_121438_add_approval_workflow_fields_to_bon_commends_and_products',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2025_10_04_122434_add_approval_workflow_fields_to_bon_commends_and_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2025_10_04_155500_add_sent_back_status_to_bon_commend_approvals',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2025_10_04_155600_add_requested_at_to_bon_commend_approvals',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2025_10_05_152641_create_patient_trackings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2025_10_06_182749_add_can_approve_caisse_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2025_10_07_184906_add_package_id_to_appointment_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2025_10_08_164815_add_tva_const_prestation_to_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2025_10_08_164822_add_tva_const_prestation_to_prestations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2025_10_09_141645_create_contract_percentages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2025_10_09_142116_add_contract_percentage_id_to_prestation_pricing_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2025_10_09_151650_update_prestation_pricing_unique_constraint_add_percentage',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2025_10_09_160421_add_extension_count_to_conventions_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2025_10_13_000000_fix_specializations_salls_foreign_key',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2025_10_15_095747_add_new_default_payment_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2025_10_15_105703_add_new_filed_defult_payemtmet_method',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2025_10_15_181238_create_bon_retours_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2025_10_15_181302_create_bon_retour_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2025_10_15_190640_add_new_field_to_bonrecption_bonreturn',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2025_10_15_200500_add_overstock_to_bon_retour_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2025_10_15_205822_add_new_field_to_bonrecption_bonentre',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2025_10_15_210000_backfill_service_abv_on_bon_entrees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2026_01_01_000001_create_pharmacy_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2026_01_20_100000_create_pharmacy_storages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2026_01_20_100001_create_pharmacy_stockages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2026_01_20_100002_create_pharmacy_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2026_01_20_100003_create_pharmacy_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2026_01_20_100004_create_pharmacy_stock_movement_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2026_01_20_100005_create_pharmacy_stocks_movement_inventory_selections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2026_01_20_100006_create_pharmacy_stockage_tools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2026_01_20_100007_create_pharmacy_service_product_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2026_01_20_100008_create_pharmacy_product_global_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2026_01_25_000001_add_pharmacy_storage_id_to_pharmacy_stockages_table',1);
