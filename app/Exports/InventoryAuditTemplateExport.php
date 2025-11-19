<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryAuditProductTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        // Return empty collection with sample data
        return collect([
            [
                1, // product_id
                'Sample Product Name',
                1, // stockage_id
                'Main Storage',
                100, // theoretical_quantity
                '', // actual_quantity (to be filled)
                '' // notes (optional)
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Stockage ID',
            'Stockage Name',
            'Theoretical Quantity',
            'Actual Quantity',
            'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 15,
            'D' => 30,
            'E' => 20,
            'F' => 20,
            'G' => 40,
        ];
    }
}
