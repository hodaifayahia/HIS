<?php

namespace App\Models\Nursing;

use App\Models\Reception\ficheNavette;
// ficheNavette
use Illuminate\Database\Eloquent\Model;

class PatientConsumption extends Model
{
    protected $table = 'patient_consumptions';

    protected $fillable = [
        'id',
        'fiche_id',
        'product_id',
        'consumed_by',
        'product_pharmacy_id',
        'quantity',
        'fiche_navette_item_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function ficheNavette()
    {
        return $this->belongsTo(\App\Models\Reception\ficheNavette::class, 'fiche_id');
    }

    public function pharmacy()
    {
        return $this->belongsTo(\App\Models\PharmacyProduct::class, 'product_pharmacy_id');
    }

    public function ficheNavetteItem()
    {
        return $this->belongsTo(\App\Models\Reception\ficheNavetteItem::class, 'fiche_navette_item_id');
    }
}
