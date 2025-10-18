<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'stockage_id',
        'quantity',
        'total_units',
        'unit',
        'batch_number',
        'serial_number',
        'purchase_price',
        'barcode',
        'expiry_date',
        'location',

    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'total_units' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockage()
    {
        return $this->belongsTo(Stockage::class);
    }

    // Generate barcode based on product code, batch number, serial number (if exists), and expiry date
    public function generateBarcode()
    {
        $productCode = $this->product->code ?? $this->product_id;
        $batchNumber = $this->batch_number ?? '';
        $expiryDate = $this->expiry_date ? $this->expiry_date->format('dmY') : '';

        if ($this->serial_number) {
            // Format: productCode-batchnumber-serialnumber-expirydate
            return $productCode.'-'.$batchNumber.'-'.$this->serial_number.'-'.$expiryDate;
        } else {
            // Format: productCode-batchnumber-expirydate
            return $productCode.'-'.$batchNumber.'-'.$expiryDate;
        }
    }

    // Auto-generate barcode when saving
    protected static function booted()
    {
        static::saving(function ($inventory) {
            if ($inventory->product) {
                $inventory->barcode = $inventory->generateBarcode();
            }
        });
    }
}
