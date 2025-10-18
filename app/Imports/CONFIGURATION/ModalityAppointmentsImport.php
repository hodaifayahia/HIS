<?php

namespace App\Imports\CONFIGURATION;

use App\Models\CONFIGURATION\MoalityAppointments;
use App\Models\CONFIGURATION\Modality;
use App\Models\Patient;
use App\AppointmentSatatusEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ModalityAppointmentsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnFailure
{
    private $modalityId;
    private $skipDuplicates;
    private $validateOnly;
    private $sendNotifications;
    private $currentRow = 0; // Initialize currentRow to 0
    private $processedRowCount = 0; // To track successfully processed rows for row number in errors

    public $results = [
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function __construct($modalityId = null, $skipDuplicates = true, $validateOnly = false, $sendNotifications = false)
    {
        $this->modalityId = $modalityId;
        $this->skipDuplicates = $skipDuplicates;
        $this->validateOnly = $validateOnly;
        $this->sendNotifications = $sendNotifications;
    }

    /**
     * Define validation rules for each row.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'patient_first_name' => 'required|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'patient_phone' => 'nullable|max:20',
            'patient_date_of_birth' => [
                'nullable',
                'date_format:Y-m-d', // After pre-processing, it should be in this format
            ],
            'appointment_date' => [
                'required',
                'date_format:Y-m-d', // After pre-processing, it should be in this format
            ],
            'appointment_time' => [
                'nullable',
                'date_format:H:i', // After pre-processing, it should be in this format
            ],
            'duration_days' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|string',
            'modality_name' => 'nullable|string|max:255', // Add if modality name is in the import file
        ];
    }

    public function customValidationMessages()
    {
        return [
            'appointment_date.date_format' => 'The appointment date must be a valid date.',
            'appointment_time.date_format' => 'The appointment time must be a valid time (HH:MM).',
            'patient_date_of_birth.date_format' => 'The patient date of birth must be a valid date.',
        ];
    }

    /**
     * Prepare for each row. This is where we can increment the row counter and pre-process dates/times.
     *
     * @param array $row
     * @param int $index
     * @return array
     */
    public function prepareForValidation($data, $index)
    {
        // $index is 0-based, but Excel rows are 1-based, and with heading row, it's $index + 2
        $this->currentRow = $index + 2;

        // Convert Excel numeric dates/times to string formats expected by Carbon and validation rules
        foreach (['patient_date_of_birth', 'appointment_date'] as $dateField) {
            if (isset($data[$dateField]) && is_numeric($data[$dateField])) {
                try {
                    $data[$dateField] = ExcelDate::excelToDateTimeObject($data[$dateField])->format('Y-m-d');
                } catch (Throwable $e) {
                    // If conversion fails, keep original or set to null, validation will catch it
                    $data[$dateField] = null; 
                }
            } elseif (isset($data[$dateField]) && is_string($data[$dateField])) {
                // Try to parse common string formats if it's a string
                $parsedDate = $this->parseFlexibleDate($data[$dateField]);
                if ($parsedDate) {
                    $data[$dateField] = $parsedDate->format('Y-m-d');
                } else {
                    $data[$dateField] = null; // Let validation handle invalid string date
                }
            }
        }

        if (isset($data['appointment_time']) && is_numeric($data['appointment_time'])) {
            try {
                // Excel time is a fraction of a day
                $seconds = round($data['appointment_time'] * 86400); // 86400 seconds in a day
                $data['appointment_time'] = gmdate('H:i', $seconds);
            } catch (Throwable $e) {
                $data['appointment_time'] = null;
            }
        } elseif (isset($data['appointment_time']) && is_string($data['appointment_time'])) {
            $parsedTime = $this->parseFlexibleTime($data['appointment_time']);
            if ($parsedTime) {
                $data['appointment_time'] = $parsedTime->format('H:i');
            } else {
                $data['appointment_time'] = null;
            }
        }
        
        return $data;
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // If validateOnly is true, we just increment successful count for valid rows
        if ($this->validateOnly) {
            $this->results['successful']++;
            return null;
        }

        try {
            // Dates and times should already be in 'Y-m-d' and 'H:i' format due to prepareForValidation
            $patientDateOfBirth = $row['patient_date_of_birth'] ?? null;
            $appointmentDate = $row['appointment_date'];
            $appointmentTime = $row['appointment_time'] ?? null;

            // Find or create patient
            $patient = Patient::firstOrCreate(
                [
                    'phone' => $row['patient_phone'] ?? null // Use null for phone if not provided to avoid issues with firstOrCreate
                ],
                [
                    'Firstname' => $row['patient_first_name'],
                    'Lastname' => $row['patient_last_name'],
                    'dateOfBirth' => $patientDateOfBirth,
                ]
            );

            // If patient was found (not recently created) and details differ, update them
            if ($patient->wasRecentlyCreated === false) {
                if ($patient->Firstname !== $row['patient_first_name'] ||
                    $patient->Lastname !== $row['patient_last_name'] ||
                    ($patientDateOfBirth && $patient->dateOfBirth !== $patientDateOfBirth)) {
                    $patient->update([
                        'Firstname' => $row['patient_first_name'],
                        'Lastname' => $row['patient_last_name'],
                        'dateOfBirth' => $patientDateOfBirth,
                    ]);
                }
            }

            // Find modality
            $modality = null;
            if ($this->modalityId) {
                $modality = Modality::find($this->modalityId);
            } elseif (!empty($row['modality_name'])) {
                $modality = Modality::where('name', 'like', '%' . $row['modality_name'] . '%')->first();
            }

            if (!$modality) {
                $this->results['failed']++;
                $this->results['errors'][] = [
                    'row' => $this->currentRow,
                    'message' => 'Modality not found: ' . ($row['modality_name'] ?? 'N/A') . '. Please ensure a valid modality name is provided or a modality ID is set.'
                ];
                return null;
            }

            // Check for duplicates
            if ($this->skipDuplicates) {
                $existingAppointment = MoalityAppointments::where('patient_id', $patient->id)
                    ->where('modality_id', $modality->id)
                    ->whereDate('appointment_date', $appointmentDate)
                    ->where('appointment_time', $appointmentTime)
                    ->first();

                if ($existingAppointment) {
                    $this->results['skipped']++;
                    return null;
                }
            }

            // Parse status from the row, defaulting to SCHEDULED if not found or invalid
            $status = AppointmentSatatusEnum::SCHEDULED->value;
            if (!empty($row['status'])) {
                $statusMap = [
                    'scheduled' => AppointmentSatatusEnum::SCHEDULED->value,
                    'confirmed' => AppointmentSatatusEnum::CONFIRMED->value,
                    'pending' => AppointmentSatatusEnum::PENDING->value,
                    'canceled' => AppointmentSatatusEnum::CANCELED->value,
                ];
                if (array_key_exists(strtolower($row['status']), $statusMap)) {
                    $status = $statusMap[strtolower($row['status'])];
                }
            }

            $this->results['successful']++;

            return new MoalityAppointments([
                'patient_id' => $patient->id,
                'modality_id' => $modality->id,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'duration_days' => $row['duration_days'] ?? 1,
                'notes' => $row['notes'] ?? null,
                'status' => $status,
                'created_by' => Auth::id(),
            ]);

        } catch (Throwable $e) {
            $this->results['failed']++;
            $this->results['errors'][] = [
                'row' => $this->currentRow,
                'message' => 'Error processing row: ' . $e->getMessage()
            ];
            return null;
        }
    }

    /**
     * Maatwebsite/Excel calls this method if validation fails for a row.
     *
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->results['failed']++;
            $this->results['errors'][] = [
                'row' => $failure->row(), // Use the row number provided by the Failure object
                'attribute' => $failure->attribute(), // The column that caused the error
                'message' => implode(', ', $failure->errors()), // All error messages for that attribute
                'values' => $failure->values(), // The row's values
            ];
        }
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Helper method to parse various date string formats into a Carbon instance.
     * This is used if the date is not a numeric Excel date.
     */
    private function parseFlexibleDate($dateString): ?Carbon
    {
        $formats = [
            'Y-m-d', 'd/m/Y', 'Y/m/d', 'd-m-Y',
            'Ymd', // e.g., 20230101
            'm/d/Y', // e.g., 01/20/2023
            'd.m.Y', // e.g., 20.01.2023
            'Y.m.d', // e.g., 2023.01.20
        ];

        foreach ($formats as $format) {
            try {
                $carbonDate = Carbon::createFromFormat($format, $dateString);
                // Ensure the formatted date matches the original to avoid false positives (e.g., '30/02/2023' parsed as '02/03/2023')
                if ($carbonDate && $carbonDate->format($format) === $dateString) {
                    return $carbonDate;
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * Helper method to parse various time string formats into a Carbon instance.
     * This is used if the time is not a numeric Excel time.
     */
    private function parseFlexibleTime($timeString): ?Carbon
    {
        $formats = [
            'H:i:s', 'H:i', 'h:i A', 'h:iA',
            'H.i.s', 'H.i',
        ];

        foreach ($formats as $format) {
            try {
                $carbonTime = Carbon::createFromFormat($format, $timeString);
                // Ensure the formatted time matches the original to avoid false positives
                if ($carbonTime && $carbonTime->format($format) === $timeString) {
                    return $carbonTime;
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return null;
    }
}