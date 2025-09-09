<?php

namespace App\Services\Bank;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankAccountTransaction;
use App\Models\BankAccountTransactionPack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkTransactionUploadService
{
    public function processUpload(
        UploadedFile $file,
        int $bankAccountId,
        ?string $description = null,
        int $userId = null
    ): array {
        try {
            DB::beginTransaction();

            // Parse the file
            $data = $this->parseFile($file);
            
            if (empty($data)) {
                throw new \Exception('No valid data found in the uploaded file.');
            }

            // Validate the data structure
            $this->validateData($data);

            // Calculate total amount
            $totalAmount = collect($data)->sum('amount');

            // Create the main transaction
            $mainTransaction = $this->createMainTransaction(
                $bankAccountId,
                $totalAmount,
                $description,
                $userId,
                count($data)
            );

            // Create transaction packs for each row
            $packs = $this->createTransactionPacks($mainTransaction->id, $data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'main_transaction' => $mainTransaction,
                    'packs_count' => count($packs),
                    'total_amount' => $totalAmount,
                    'processed_rows' => count($data)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk upload failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function parseFile(UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        $data = [];

        try {
            if ($extension === 'csv') {
                $data = $this->parseCsvFile($file);
            } else {
                $data = $this->parseExcelFile($file);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error parsing file: ' . $e->getMessage());
        }

        return $data;
    }

    private function parseCsvFile(UploadedFile $file): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not open CSV file');
        }

        // Skip header row
        $header = fgetcsv($handle);
        $rowNumber = 1; // Start from 1 since we skip header
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($row, fn($value) => !empty(trim($value))))) {
                continue;
            }
            
            if (count($row) < 4) {
                throw new \Exception("Row {$rowNumber}: File must have at least 4 columns (Name, Description, Amount, Reference)");
            }
            
            if (!empty(trim($row[0]))) {
                // Clean and validate amount
                $amountStr = trim($row[2] ?? '');
                $amount = 0;
                
                if (!empty($amountStr)) {
                    // Remove commas and convert to float
                    $cleanAmount = str_replace([',', ' '], '', $amountStr);
                    if (is_numeric($cleanAmount)) {
                        $amount = floatval($cleanAmount);
                    } else {
                        throw new \Exception("Row {$rowNumber}: Amount '{$amountStr}' is not a valid number");
                    }
                }
                
                $data[] = [
                    'name' => trim($row[0]),
                    'description' => trim($row[1] ?? ''),
                    'amount' => $amount,
                    'reference' => trim($row[3] ?? ''),
                ];
            }
        }
        
        fclose($handle);
        return $data;
    }

    private function parseExcelFile(UploadedFile $file): array
    {
        $data = [];
        
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);
            $rowNumber = 1; // Start from 1 since we skip header

            foreach ($rows as $row) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row, fn($value) => !empty(trim($value ?? ''))))) {
                    continue;
                }
                
                if (count($row) < 4) {
                    throw new \Exception("Row {$rowNumber}: File must have at least 4 columns (Name, Description, Amount, Reference)");
                }
                
                if (!empty(trim($row[0] ?? ''))) {
                    // Clean and validate amount
                    $amountStr = trim($row[2] ?? '');
                    $amount = 0;
                    
                    if (!empty($amountStr)) {
                        // Remove commas and convert to float
                        $cleanAmount = str_replace([',', ' '], '', $amountStr);
                        if (is_numeric($cleanAmount)) {
                            $amount = floatval($cleanAmount);
                        } else {
                            throw new \Exception("Row {$rowNumber}: Amount '{$amountStr}' is not a valid number");
                        }
                    }
                    
                    $data[] = [
                        'name' => trim($row[0] ?? ''),
                        'description' => trim($row[1] ?? ''),
                        'amount' => $amount,
                        'reference' => trim($row[3] ?? ''),
                    ];
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error reading Excel file: ' . $e->getMessage());
        }

        return $data;
    }

    private function validateData(array $data): void
    {
        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because we skip header and arrays are 0-indexed
            
            if (empty($row['name'])) {
                throw new \Exception("Row {$rowNumber}: Name is required");
            }
            
            // More detailed amount validation
            if (!is_numeric($row['amount']) || $row['amount'] <= 0) {
                $amountValue = $row['amount'] ?? 'empty';
                throw new \Exception("Row {$rowNumber}: Amount must be a positive number greater than 0 (current value: {$amountValue})");
            }
            
            if (strlen($row['name']) > 255) {
                throw new \Exception("Row {$rowNumber}: Name is too long (max 255 characters)");
            }
        }
    }

    private function createMainTransaction(
        int $bankAccountId,
        float $totalAmount,
        ?string $description,
        ?int $userId,
        int $packCount
    ): BankAccountTransaction {
        $bankAccount = BankAccount::findOrFail($bankAccountId);
        
        return BankAccountTransaction::create([
            'bank_account_id' => $bankAccountId,
            'accepted_by_user_id' => $userId,
            'transaction_type' => 'credit', // Assuming bulk uploads are payments received
            'amount' => $totalAmount,
            'description' => $description ?: "Bulk upload - {$packCount} transactions",
            'status' => 'pending',
            'transaction_date' => now(),
            'reference' => BankAccountTransaction::generateReference(),
            'Designation' => 'Bulk Payment Upload',
            'Payer' => 'Multiple Payers',
        ]);
    }

    private function createTransactionPacks(int $transactionId, array $data): array
    {
        $packs = [];
        
        foreach ($data as $row) {
            // Look up user_id by name
            $user = \App\Models\User::where('name', $row['name'])->first();
            
            $pack = BankAccountTransactionPack::create([
                'bank_account_transaction_id' => $transactionId,
                'user_id' => $user ? $user->id : null, // Store user_id instead of name
                'name' => $row['name'], // Keep name for reference
                'description' => $row['description'],
                'amount' => $row['amount'],
                'reference' => $row['reference'],
            ]);
            
            $packs[] = $pack;
        }
        
        return $packs;
    }

    public function generateTemplate(): array
    {
        return [
            'headers' => ['Name', 'Description', 'Amount', 'Reference'],
            'sample_data' => [
                ['John Doe', 'Payment for services', '1500.00', 'REF001'],
                ['Jane Smith', 'Consultation fee', '750.50', 'REF002'],
                ['Company ABC', 'Monthly subscription', '2000.00', 'REF003'],
            ]
        ];
    }
}
