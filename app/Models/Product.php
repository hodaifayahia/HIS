<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'is_clinical',
        'code_interne',
        'code_pch',
        'designation',
        'type_medicament',
        'forme',
        'boite_de',
        'nom_commercial',
        'status'
    ];

    protected $casts = [
        'is_clinical' => 'boolean',
        'code_interne' => 'integer',
        'boite_de' => 'integer'
    ];

    // Relationships
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
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
