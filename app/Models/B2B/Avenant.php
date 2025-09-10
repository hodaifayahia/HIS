<?php

namespace App\Models\B2B;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avenant extends Model
{
    use HasFactory;

    protected $table = 'avenants'; // Matches your migration table name

    protected $fillable = [
        'convention_id', // Changed from contract_id
        'description',
        'activation_at', // Changed from activate_at/activation_datetime
        'status',
        'creator_id',
        'approver_id',
        'inactive_at', // Added from your migration
        'head',        // Added from your migration
        'updated_by_id', // This is for the User who updated, as per your migration
    ];

    protected $casts = [
        'activation_at' => 'datetime',
        'inactive_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the convention that owns the avenant.
     */
    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class, 'convention_id'); // Changed to Convention
    }

    /**
     * Get the user who created the avenant.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the user who approved the avenant.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the user who last updated the avenant.
     */
    public function lastUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Get the prestation prices for this avenant.
     */
    public function prestationPrices(): HasMany
    {
        return $this->hasMany(PrestationPrice::class, 'avenant_id');
    }

    /**
     * Get the agreement details for this avenant.
     */
    public function agreementDetails(): HasMany
    {
        return $this->hasMany(AgreementDetails::class, 'avenant_id');
    }
}