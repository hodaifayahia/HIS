<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Inventory;
use App\Models\PharmacyInventory;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'is_clinical',
        'is_required_approval',
        'is_request_approval',
        'code_interne',
        'code_pch',
        'code',
        'designation',
        'type_medicament',
        'forme',
        'boite_de',
        'nom_commercial',
        'status',
    ];

    protected $casts = [
        'is_clinical' => 'boolean',
        'is_required_approval' => 'boolean',
        'is_request_approval' => 'boolean',
        'code_interne' => 'integer',
        'boite_de' => 'integer',
    ];

    // Relationships
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function inventorieswithPharmacieStock(){
        return $this->hasMany(PharmacyInventory::class);
    }

    public function stockages()
    {
        return $this->belongsToMany(Stockage::class, 'inventories');
    }

    public function serviceProductSettings()
    {
        return $this->hasMany(ServiceProductSetting::class);
    }
}
