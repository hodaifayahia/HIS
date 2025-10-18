<?php

namespace App\Exports\CONFIGURATION;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ModalityAppointmentsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Sample data to show format
        return [
            [
                'John',
                'Doe',
                '+1234567890',
                '1990-01-15',
                'MRI Scanner',
                '2024-01-20',
                '09:00',
                1,
                'Regular checkup',
                'scheduled'
            ],
            [
                'Jane',
                'Smith',
                '+1234567891',
                '1985-05-20',
                'CT Scanner',
                '2024-01-21',
                '14:30',
                1,
                'Follow-up scan',
                'confirmed'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'patient_first_name',
            'patient_last_name', 
            'patient_phone',
            'patient_date_of_birth',
            'modality_name',
            'appointment_date',
            'appointment_time',
            'duration_days',
            'notes',
            'status'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
