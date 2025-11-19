<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InventoryAuditProductImport implements ToArray, WithHeadingRow
{
    protected $importedData = [];

    public function array(array $rows)
    {
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row['product_id']) || empty($row['actual_quantity'])) {
                continue;
            }

            $this->importedData[] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => $row['product_name'] ?? '',
                'stockage_id' => (int) $row['stockage_id'],
                'stockage_name' => $row['stockage_name'] ?? '',
                'theoretical_quantity' => (float) ($row['theoretical_quantity'] ?? 0),
                'actual_quantity' => (float) $row['actual_quantity'],
                'notes' => $row['notes'] ?? ''
            ];
        }
    }

    public function getImportedData()
    {
        return $this->importedData;
    }

    public function headingRow(): int
    {
        return 1;
    }
}
