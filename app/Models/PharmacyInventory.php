<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyInventory extends Model
{
    protected $table = 'pharmacy_inventories';

    protected $fillable = [
        'pharmacy_product_id',
        'pharmacy_stockage_id',
        'quantity',
        'unit',
        'batch_number',
        'expiry_date',
        'location',
        'barcode',
        'serial_number',
        'purchase_price',
        'selling_price',
        'supplier',
        'purchase_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'expiry_date' => 'date',
        'purchase_date' => 'date',
    ];

    // Relationships
    /**
     * Get the pharmacy product that owns the inventory.
     */
    public function pharmacyProduct()
    {
        return $this->belongsTo(PharmacyProduct::class, 'pharmacy_product_id');
    }

    /**
     * Alias for pharmacyProduct() for backward compatibility
     */
    public function product()
    {
        return $this->pharmacyProduct();
    }

    /**
     * Get the pharmacy stockage that owns the inventory.
     */
    public function pharmacyStockage()
    {
        return $this->belongsTo(PharmacyStockage::class, 'pharmacy_stockage_id');
    }

    /**
     * Alias for pharmacyStockage() for backward compatibility
     */
    public function stockage()
    {
        return $this->pharmacyStockage();
    }

    // Generate barcode based on product code, batch number, serial number (if exists), and expiry date
    public function generateBarcode()
    {
        $productCode = $this->pharmacyProduct->code ?? $this->pharmacy_product_id;
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
            if ($inventory->pharmacyProduct) {
                $inventory->barcode = $inventory->generateBarcode();
            }
        });
    }
}
