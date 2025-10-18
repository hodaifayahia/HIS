<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Salle extends Model
{
    use HasFactory;

    protected $table = 'salls';

    protected $fillable = [
        'name',
        'number',
        'description',
        'defult_specialization_id',
    ];

    protected $casts = [
        'defult_specialization_id' => 'integer',
    ];

    /**
     * Get the default specialization for this salle
     */
    public function defaultSpecialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'defult_specialization_id');
    }

    /**
     * Get all specializations assigned to this salle
     */
    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(Specialization::class, 'specializations_salls', 'sall_id', 'specialization_id')
            ->withTimestamps();
    }

    /**
     * Scope to filter by active salles (you can add is_active field later if needed)
     */
    public function scopeActive($query)
    {
        return $query; // For now, return all. Add ->where('is_active', true) if you add this field
    }

    /**
     * Get the full name combining name and number
     */
    public function getFullNameAttribute(): string
    {
        return $this->name.' ('.$this->number.')';
    }
}
