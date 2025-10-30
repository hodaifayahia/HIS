<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryAuditProductExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $stockageId;

    public function __construct($stockageId = null)
    {
        $this->stockageId = $stockageId;
    }

    public function collection()
    {
        $query = Product::query()
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'stockages.id as stockage_id',
                'stockages.name as stockage_name',
                DB::raw('COALESCE(SUM(inventory.quantity), 0) as theoretical_quantity')
            )
            ->leftJoin('inventory', 'products.id', '=', 'inventory.product_id')
            ->leftJoin('stockages', 'inventory.stockage_id', '=', 'stockages.id')
            ->groupBy('products.id', 'products.name', 'stockages.id', 'stockages.name');

        if ($this->stockageId) {
            $query->where('stockages.id', $this->stockageId);
        }

        $products = $query->orderBy('products.name')->get();

        return $products->map(function ($product) {
            return [
                $product->product_id,
                $product->product_name,
                $product->stockage_id,
                $product->stockage_name,
                $product->theoretical_quantity,
                '', // actual_quantity to be filled
                '' // notes
            ];
        });
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
