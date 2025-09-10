<?php

namespace App\Services\CONFIGURATION;


use App\Imports\PrescriptionsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportService
{
    /**
     * Import prescriptions from Excel file
     */
    public function importPrescriptions(Request $request)
    {
        $import = new PrescriptionsImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->getFailures();

        if (!empty($failures)) {
            return [
                'success' => false,
                'message' => 'Some prescriptions could not be imported due to validation errors.',
                'failures' => $this->formatFailures($failures)
            ];
        }

        return [
            'success' => true,
            'message' => 'Prescriptions imported successfully!'
        ];
    }

    /**
     * Format failures for response
     */
    private function formatFailures($failures)
    {
        $errorMessages = [];
        foreach ($failures as $failure) {
            $row = $failure['row'];
            $attribute = $failure['attribute'];
            $errors = implode(', ', $failure['errors']);
            $errorMessages[] = "Row {$row}, Column '{$attribute}': {$errors}";
        }
        return $errorMessages;
    }
}
