<?php

/**
 * Test Doctor Duplication Feature
 * 
 * This script tests the complete doctor duplication functionality
 * including all consultation configurations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Doctor;
use App\Models\User;
use App\Models\Folder;
use App\Models\Placeholder;
use App\Models\Attribute;
use App\Models\Template;
use App\Models\PlaceholderTemplate;
use App\Models\MedicationDoctorFavorat;
use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 80) . "\n";
echo "DOCTOR DUPLICATION TEST SCRIPT\n";
echo str_repeat("=", 80) . "\n\n";

// CLI support: usage
// php test_doctor_duplication.php <sourceDoctorId> <name> <email> <password> [phone] [--dry-run]

// Get CLI args if provided
$cliArgs = $_SERVER['argv'] ?? [];
$isDryRun = in_array('--dry-run', $cliArgs, true);
$cliSource = $cliArgs[1] ?? null;
$cliName = $cliArgs[2] ?? null;
$cliEmail = $cliArgs[3] ?? null;
$cliPassword = $cliArgs[4] ?? null;
$cliPhone = $cliArgs[5] ?? null;

// Get a source doctor ID (you can change this)
echo "Enter source doctor ID to test (or press Enter to list all doctors): ";
$handle = fopen("php://stdin", "r");
if ($cliSource) {
    $input = trim($cliSource);
} else {
    $input = trim(fgets($handle));
}

if (empty($input)) {
    echo "\nAvailable Doctors:\n";
    echo str_repeat("-", 80) . "\n";
    $doctors = Doctor::with('user')->get();
    foreach ($doctors as $doctor) {
        echo sprintf("ID: %d | Name: %s | Email: %s\n", 
            $doctor->id, 
            $doctor->user->name ?? 'N/A', 
            $doctor->user->email ?? 'N/A'
        );
    }
    echo str_repeat("-", 80) . "\n";
    echo "Enter source doctor ID: ";
    $input = trim(fgets($handle));
}

$sourceDoctorId = intval($input);

// Find the source doctor
$sourceDoctor = Doctor::with(['user'])->find($sourceDoctorId);

if (!$sourceDoctor) {
    die("❌ Error: Doctor with ID {$sourceDoctorId} not found!\n");
}

echo "\n✓ Source doctor found: {$sourceDoctor->user->name} (ID: {$sourceDoctorId})\n\n";

// Check what the source doctor has
echo "Checking source doctor's configurations...\n";
echo str_repeat("-", 80) . "\n";

$counts = [
    'schedules' => DB::table('schedules')->where('doctor_id', $sourceDoctorId)->count(),
    'appointment_months' => DB::table('appointment_available_month')->where('doctor_id', $sourceDoctorId)->count(),
    'appointment_forcers' => DB::table('appointment_forcers')->where('doctor_id', $sourceDoctorId)->count(),
    'excluded_dates' => DB::table('excluded_dates')->where('doctor_id', $sourceDoctorId)->count(),
    'folders' => Folder::where('doctor_id', $sourceDoctorId)->count(),
    'placeholders' => Placeholder::where('doctor_id', $sourceDoctorId)->count(),
    // attributes table doesn't have doctor_id directly; count via join with placeholders
    'attributes' => DB::table('attributes')
        ->join('placeholders', 'attributes.placeholder_id', '=', 'placeholders.id')
        ->where('placeholders.doctor_id', $sourceDoctorId)
        ->count(),
    'templates' => Template::where('doctor_id', $sourceDoctorId)->count(),
    'medication_favorites' => MedicationDoctorFavorat::where('doctor_id', $sourceDoctorId)->count(),
];

foreach ($counts as $type => $count) {
    $icon = $count > 0 ? '✓' : '○';
    echo sprintf("%s %-25s: %d\n", $icon, ucwords(str_replace('_', ' ', $type)), $count);
}

echo str_repeat("-", 80) . "\n";

$totalConfigs = array_sum($counts);
if ($totalConfigs == 0) {
    echo "\n⚠ Warning: Source doctor has no configurations to duplicate!\n";
    echo "The duplication will still work, but there won't be much to copy.\n";
}

echo "\nTotal configurations: {$totalConfigs}\n\n";

// Get new doctor details (from CLI if provided)
if ($isDryRun) {
    // dry-run: we won't create any users/doctors; details optional
    $name = $cliName;
    $email = $cliEmail;
    $password = $cliPassword;
    $phone = $cliPhone;
} else {
    echo "Enter new doctor details:\n";
    echo str_repeat("-", 80) . "\n";

    if ($cliName && $cliEmail && $cliPassword) {
        $name = $cliName;
        $email = $cliEmail;
        $password = $cliPassword;
        $phone = $cliPhone;
    } else {
        echo "Name: ";
        $name = trim(fgets($handle));

        echo "Email: ";
        $email = trim(fgets($handle));

        echo "Password: ";
        $password = trim(fgets($handle));

        echo "Phone (optional): ";
        $phone = trim(fgets($handle));
    }

    if (empty($name) || empty($email) || empty($password)) {
        die("\n❌ Error: Name, email, and password are required!\n");
    }
}

// Check if email exists
if (User::where('email', $email)->exists()) {
    die("\n❌ Error: Email '{$email}' already exists!\n");
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "STARTING DUPLICATION\n";
echo str_repeat("=", 80) . "\n\n";

// If dry-run, print planned actions and exit before making DB changes
if (!empty($isDryRun)) {
    echo "Dry-run: no database changes will be made. Planned actions:\n";
    echo "  - Create user with name: " . ($name ?? '(not provided)') . "\n";
    echo "  - Create doctor based on source doctor ID: {$sourceDoctorId}\n";
    echo "  - Copy schedules: {$counts['schedules']}\n";
    echo "  - Copy folders: {$counts['folders']}\n";
    echo "  - Copy placeholders and attributes: {$counts['placeholders']} placeholders and (attributes via placeholders)\n";
    echo "  - Copy templates: {$counts['templates']}\n";
    echo "  - Copy medication favorites: {$counts['medication_favorites']}\n";
    echo "\nDry-run complete. Exiting.\n";
    exit(0);
}

DB::beginTransaction();

try {
    // Create new user
    echo "1. Creating new user... ";
    $newUser = User::create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt($password),
        'phone' => $phone ?: null,
        'role' => 'doctor',
        'is_active' => true,
    ]);
    echo "✓ User created (ID: {$newUser->id})\n";

    // Create new doctor
    echo "2. Creating new doctor... ";
    $newDoctor = Doctor::create([
        'user_id' => $newUser->id,
        'specialization_id' => $sourceDoctor->specialization_id,
        'time_slot' => $sourceDoctor->time_slot,
        'frequency' => $sourceDoctor->frequency,
        'patients_based_on_time' => $sourceDoctor->patients_based_on_time,
        'allowed_appointment_today' => $sourceDoctor->allowed_appointment_today ?? true,
        'is_active' => $sourceDoctor->is_active ?? true,
        'include_time' => $sourceDoctor->include_time ?? false,
        'appointment_booking_window' => $sourceDoctor->appointment_booking_window,
    ]);
    echo "✓ Doctor created (ID: {$newDoctor->id})\n";

    // Copy schedules
    if ($counts['schedules'] > 0) {
        echo "3. Copying schedules... ";
        $schedules = DB::table('schedules')->where('doctor_id', $sourceDoctorId)->get();
        foreach ($schedules as $schedule) {
            DB::table('schedules')->insert([
                'doctor_id' => $newDoctor->id,
                'day_of_week' => $schedule->day_of_week,
                'date' => $schedule->date,
                'shift_period' => $schedule->shift_period,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'is_active' => $schedule->is_active,
                'number_of_patients_per_day' => $schedule->number_of_patients_per_day,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        echo "✓ {$counts['schedules']} schedules copied\n";
    } else {
        echo "3. Skipping schedules (none found)\n";
    }

    // Copy folders
    if ($counts['folders'] > 0) {
        echo "4. Copying folders... ";
        $folders = Folder::where('doctor_id', $sourceDoctorId)->get();
        $folderMap = [];

        // Detect which columns exist on folders table to avoid inserting unknown fields
        $folderColumns = DB::getSchemaBuilder()->getColumnListing('folders');

        foreach ($folders as $folder) {
            $newFolderData = [
                'name' => $folder->name,
                'description' => $folder->description,
                'doctor_id' => $newDoctor->id,
            ];

            if (in_array('specialization_id', $folderColumns)) {
                $newFolderData['specialization_id'] = $folder->specialization_id;
            }

            $newFolder = Folder::create($newFolderData);
            $folderMap[$folder->id] = $newFolder->id;
        }
        echo "✓ {$counts['folders']} folders copied\n";
    } else {
        echo "4. Skipping folders (none found)\n";
        $folderMap = [];
    }

    // Copy placeholders and attributes
    if ($counts['placeholders'] > 0) {
        echo "5. Copying placeholders... ";
        $placeholders = Placeholder::with('attributes')->where('doctor_id', $sourceDoctorId)->get();
        $placeholderMap = [];
        $attributeMap = [];
        $totalAttributes = 0;
        
        // Detect placeholder and attribute columns to avoid inserting missing fields
        $placeholderColumns = DB::getSchemaBuilder()->getColumnListing('placeholders');
        $attributeColumns = DB::getSchemaBuilder()->getColumnListing('attributes');

        foreach ($placeholders as $placeholder) {
            $newPlaceholderData = [
                'name' => $placeholder->name,
                'description' => $placeholder->description,
                'doctor_id' => $newDoctor->id,
            ];

            if (in_array('specializations_id', $placeholderColumns)) {
                $newPlaceholderData['specializations_id'] = $placeholder->specializations_id;
            }

            $newPlaceholder = Placeholder::create($newPlaceholderData);
            $placeholderMap[$placeholder->id] = $newPlaceholder->id;
            
            // Copy attributes (only include columns that exist)
            foreach ($placeholder->attributes as $attribute) {
                $newAttributeData = [
                    'placeholder_id' => $newPlaceholder->id,
                    'name' => $attribute->name,
                    'description' => $attribute->description,
                    'value' => $attribute->value,
                    'input_type' => $attribute->input_type,
                    'is_required' => $attribute->is_required,
                ];

                if (in_array('doctor_id', $attributeColumns)) {
                    $newAttributeData['doctor_id'] = $newDoctor->id;
                }

                // Only set fields that exist on the attributes table
                $filteredAttributeData = array_intersect_key($newAttributeData, array_flip($attributeColumns));

                $newAttribute = Attribute::create($filteredAttributeData);
                $attributeMap[$attribute->id] = $newAttribute->id;
                $totalAttributes++;
            }
        }
        echo "✓ {$counts['placeholders']} placeholders and {$totalAttributes} attributes copied\n";
    } else {
        echo "5. Skipping placeholders (none found)\n";
        $placeholderMap = [];
        $attributeMap = [];
    }

    // Copy templates
    if ($counts['templates'] > 0) {
        echo "6. Copying templates... ";
        $templates = Template::with('placeholders')->where('doctor_id', $sourceDoctorId)->get();
        $totalAssociations = 0;
        
        // detect template columns to avoid inserting unknown fields
        $templateColumns = DB::getSchemaBuilder()->getColumnListing('templates');

        foreach ($templates as $template) {
            $newFolderId = isset($folderMap[$template->folder_id]) 
                ? $folderMap[$template->folder_id] 
                : $template->folder_id;

            $newTemplateData = [
                'name' => $template->name,
                'description' => $template->description,
                'content' => $template->content,
                'doctor_id' => $newDoctor->id,
            ];

            if (in_array('mime_type', $templateColumns)) {
                $newTemplateData['mime_type'] = $template->mime_type;
            }
            if (in_array('folder_id', $templateColumns)) {
                $newTemplateData['folder_id'] = $newFolderId;
            }
            if (in_array('file_path', $templateColumns)) {
                $newTemplateData['file_path'] = $template->file_path;
            }

            // Only keep keys that actually exist in templates table
            $newTemplateData = array_intersect_key($newTemplateData, array_flip($templateColumns));

            $newTemplate = Template::create($newTemplateData);
            
            // Copy placeholder-template associations
            foreach ($template->placeholders as $placeholder) {
                if (isset($placeholderMap[$placeholder->id])) {
                    $oldAttributeId = $placeholder->pivot->attribute_id;
                    $newAttributeId = isset($attributeMap[$oldAttributeId]) 
                        ? $attributeMap[$oldAttributeId] 
                        : $oldAttributeId;
                        
                    PlaceholderTemplate::create([
                        'template_id' => $newTemplate->id,
                        'placeholder_id' => $placeholderMap[$placeholder->id],
                        'attribute_id' => $newAttributeId,
                    ]);
                    $totalAssociations++;
                }
            }
        }
        echo "✓ {$counts['templates']} templates with {$totalAssociations} associations copied\n";
    } else {
        echo "6. Skipping templates (none found)\n";
    }

    // Copy medication favorites
    if ($counts['medication_favorites'] > 0) {
        echo "7. Copying medication favorites... ";
        $favorites = MedicationDoctorFavorat::where('doctor_id', $sourceDoctorId)->get();
        foreach ($favorites as $favorite) {
            MedicationDoctorFavorat::create([
                'medication_id' => $favorite->medication_id,
                'doctor_id' => $newDoctor->id,
                'favorited_at' => now(),
            ]);
        }
        echo "✓ {$counts['medication_favorites']} medication favorites copied\n";
    } else {
        echo "7. Skipping medication favorites (none found)\n";
    }

    DB::commit();

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "✓✓✓ DUPLICATION SUCCESSFUL! ✓✓✓\n";
    echo str_repeat("=", 80) . "\n\n";

    // Verify the duplication
    echo "Verification:\n";
    echo str_repeat("-", 80) . "\n";

    $newCounts = [
        'schedules' => DB::table('schedules')->where('doctor_id', $newDoctor->id)->count(),
        'folders' => Folder::where('doctor_id', $newDoctor->id)->count(),
        'placeholders' => Placeholder::where('doctor_id', $newDoctor->id)->count(),
        'attributes' => DB::table('attributes')
            ->join('placeholders', 'attributes.placeholder_id', '=', 'placeholders.id')
            ->where('placeholders.doctor_id', $newDoctor->id)
            ->count(),
        'templates' => Template::where('doctor_id', $newDoctor->id)->count(),
        'medication_favorites' => MedicationDoctorFavorat::where('doctor_id', $newDoctor->id)->count(),
    ];

    foreach ($newCounts as $type => $count) {
        $originalCount = isset($counts[$type]) ? $counts[$type] : 0;
        $match = $count == $originalCount ? '✓' : '✗';
        echo sprintf("%s %-25s: %d / %d (original)\n", 
            $match, 
            ucwords(str_replace('_', ' ', $type)), 
            $count, 
            $originalCount
        );
    }

    echo str_repeat("-", 80) . "\n";
    echo "\nNew Doctor Details:\n";
    echo "  ID: {$newDoctor->id}\n";
    echo "  Name: {$name}\n";
    echo "  Email: {$email}\n";
    echo "  User ID: {$newUser->id}\n";
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n\n" . str_repeat("=", 80) . "\n";
    echo "❌ DUPLICATION FAILED!\n";
    echo str_repeat("=", 80) . "\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "Test completed successfully!\n\n";
