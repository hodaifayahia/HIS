<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;

class ModalityAvailableMonth extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'modality_available_months';
    protected $fillable = [
        'modality_id',
        'month',
        'year',
        'is_available'
    ];
    /**
     * Get the modality that owns the available month.
     */
    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }
}
