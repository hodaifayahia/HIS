INSERT INTO `allergies` (`id`, `name`, `severity`, `date`, `note`, `patient_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `notes`, `appointment_date`, `appointment_time`, `created_by`, `status`, `reason`, `deleted_at`, `created_at`, `updated_at`, `add_to_waitlist`, `canceled_by`, `updated_by`) VALUES
INSERT INTO `appointment_available_month` (`id`, `month`, `year`, `doctor_id`, `is_available`, `deleted_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `appointment_forcers` (`id`, `doctor_id`, `user_id`, `is_able_to_force`, `number_of_patients`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
INSERT INTO `attributes` (`id`, `name`, `input_type`, `value`, `placeholder_id`, `created_at`, `updated_at`) VALUES
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
INSERT INTO `chronic_diseases` (`id`, `name`, `description`, `diagnosis_date`, `patient_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `consultations` (`id`, `template_id`, `patient_id`, `doctor_id`, `appointment_id`, `created_at`, `updated_at`, `name`, `codebash`, `consultation_end_at`) VALUES
INSERT INTO `consultationworkspaces` (`id`, `name`, `doctor_id`, `last_accessed`, `is_archived`, `description`, `created_at`, `updated_at`) VALUES
INSERT INTO `consultationworkspace_lists` (`consultation_id`, `consultation_workspace_id`, `notes`, `created_at`, `updated_at`) VALUES
INSERT INTO `consultation_placeholder_attributes` (`id`, `consultation_id`, `placeholder_id`, `attribute_id`, `created_at`, `updated_at`, `appointment_id`, `attribute_value`) VALUES
INSERT INTO `consultation_placeholder_attributes` (`id`, `consultation_id`, `placeholder_id`, `attribute_id`, `created_at`, `updated_at`, `appointment_id`, `attribute_value`) VALUES
INSERT INTO `consultation_placeholder_attributes` (`id`, `consultation_id`, `placeholder_id`, `attribute_id`, `created_at`, `updated_at`, `appointment_id`, `attribute_value`) VALUES
INSERT INTO `consultation_placeholder_attributes` (`id`, `consultation_id`, `placeholder_id`, `attribute_id`, `created_at`, `updated_at`, `appointment_id`, `attribute_value`) VALUES
INSERT INTO `doctors` (`id`, `specialization_id`, `allowed_appointment_today`, `number_of_patient`, `frequency`, `specific_date`, `notes`, `patients_based_on_time`, `time_slot`, `appointment_booking_window`, `created_by`, `user_id`, `deleted_at`, `created_at`, `updated_at`, `schedule_id`, `include_time`) VALUES
INSERT INTO `doctor_fiche_navettes` (`id`, `fiche_navette_id`, `doctor_id`, `created_at`, `updated_at`) VALUES
INSERT INTO `excluded_dates` (`id`, `doctor_id`, `start_date`, `end_date`, `reason`, `apply_for_all_years`, `created_at`, `updated_at`, `deleted_at`, `start_time`, `number_of_patients_per_day`, `end_time`, `shift_period`, `is_active`, `exclusionType`, `created_by`, `updated_by`, `deleted_by`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
INSERT INTO `family_diseases` (`id`, `disease_name`, `relation`, `notes`, `patient_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `folders` (`id`, `name`, `description`, `created_at`, `updated_at`, `doctor_id`, `specializations_id`) VALUES
INSERT INTO `medications` (`id`, `designation`, `type_medicament`, `forme`, `boite_de`, `__v`, `isSelected`, `code_pch`, `nom_commercial`, `created_at`, `updated_at`, `deleted_at`) VALUES
INSERT INTO `medications` (`id`, `designation`, `type_medicament`, `forme`, `boite_de`, `__v`, `isSelected`, `code_pch`, `nom_commercial`, `created_at`, `updated_at`, `deleted_at`) VALUES
INSERT INTO `medications` (`id`, `designation`, `type_medicament`, `forme`, `boite_de`, `__v`, `isSelected`, `code_pch`, `nom_commercial`, `created_at`, `updated_at`, `deleted_at`) VALUES
INSERT INTO `medications` (`id`, `designation`, `type_medicament`, `forme`, `boite_de`, `__v`, `isSelected`, `code_pch`, `nom_commercial`, `created_at`, `updated_at`, `deleted_at`) VALUES
INSERT INTO `medication_doctor_favorats` (`id`, `medication_id`, `doctor_id`, `favorited_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
INSERT INTO `opinion_requests` (`id`, `sender_doctor_id`, `reciver_doctor_id`, `appointment_id`, `patient_id`, `request`, `status`, `Reply`, `created_at`, `updated_at`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patients` (`id`, `Firstname`, `Lastname`, `phone`, `dateOfBirth`, `Idnum`, `Parent`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `age`, `weight`, `gender`, `nss`, `balance`, `firstname_ar`, `lastname_ar`, `birth_place`) VALUES
INSERT INTO `patient_docements` (`id`, `patient_id`, `doctor_id`, `appointment_id`, `folder_id`, `document_type`, `document_path`, `document_name`, `document_size`, `created_at`, `updated_at`) VALUES
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
INSERT INTO `placeholders` (`id`, `name`, `doctor_id`, `specializations_id`, `description`, `created_at`, `updated_at`) VALUES
INSERT INTO `prescriptions` (`id`, `consultation_id`, `patient_id`, `signature_status`, `pdf_path`, `created_at`, `updated_at`, `doctor_id`, `start_date`, `end_date`, `appointment_id`, `prescription_date`) VALUES
INSERT INTO `prescriptiontemplates` (`id`, `doctor_id`, `name`, `prescription_id`, `description`, `deleted_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `prescription_medications` (`id`, `prescription_id`, `medication_id`, `cd_active_substance`, `brand_name`, `pharmaceutical_form`, `dose_per_intake`, `num_intakes_per_time`, `frequency`, `duration_or_boxes`, `created_at`, `updated_at`, `form`, `num_times`, `start_date`, `end_date`, `description`, `pills_matin`, `pills_apres_midi`, `pills_midi`, `pills_soir`, `frequency_period`, `period_intakes`, `timing_preference`) VALUES
INSERT INTO `schedules` (`id`, `doctor_id`, `day_of_week`, `shift_period`, `start_time`, `end_time`, `date`, `number_of_patients_per_day`, `is_active`, `break_duration`, `break_times`, `excluded_dates`, `modified_times`, `deleted_at`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
INSERT INTO `specializations` (`id`, `name`, `description`, `photo`, `deleted_at`, `created_by`, `created_at`, `updated_at`, `service_id`) VALUES
INSERT INTO `specialization_fichenavettes` (`id`, `fiche_navette_id`, `specialization_id`, `created_at`, `updated_at`) VALUES
INSERT INTO `surgicals` (`id`, `procedure_name`, `description`, `surgery_date`, `patient_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `templates` (`id`, `name`, `mime_type`, `file_size`, `doctor_id`, `description`, `created_at`, `updated_at`, `content`, `folder_id`) VALUES
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `avatar`, `background`, `created_by`, `remember_token`, `deleted_at`, `created_at`, `updated_at`, `is_active`, `job_title`, `account_status`, `professional_license_number`, `fee_account_details`, `personal_discount_ceiling`, `service_id`, `manager_id`, `fichenavatte_max`, `salary`) VALUES
INSERT INTO `waitlist` (`id`, `doctor_id`, `patient_id`, `specialization_id`, `is_Daily`, `created_by`, `appointmentId`, `importance`, `MoveToEnd`, `notes`, `deleted_at`, `created_at`, `updated_at`) VALUES
