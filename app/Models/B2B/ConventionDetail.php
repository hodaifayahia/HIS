<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\B2B\Convention;

class ConventionDetail extends Model
{
    use HasFactory;
    
   protected $table = 'conventions_details';

    protected $fillable = [
        'convention_id',
        'head',
        'updated_by_id',
        'start_date',
        'end_date',
        'family_auth',
        'max_price',
        'min_price',
        'discount_percentage',
        'avenant_id',
        'extension_count'
    ];

    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class);
    }
     public function avenant(): BelongsTo
    {
        return $this->belongsTo(Avenant::class, 'avenant_id');
    }
    /**
     * Get the next version of these agreement details.
     */
    public function updatedByAgreementDetails(): BelongsTo
    {
        return $this->belongsTo(ConventionDetail::class, 'updated_by_id');
    }

    /**
     * Get the previous version that these agreement details updated.
     */
    public function updatesAgreementDetails(): HasOne
    {
        return $this->hasOne(ConventionDetail::class, 'updated_by_id');
    }

}
