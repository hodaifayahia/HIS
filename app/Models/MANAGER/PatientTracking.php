<?php

namespace App\Models\MANAGER;

use App\Models\CONFIGURATION\Prestation;
use App\Models\Patient;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Salle;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientTracking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fiche_navette_item_id',
        'patient_id',
        'salle_id',
        'specialization_id',
        'prestation_id',
        'check_in_time',
        'check_out_time',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the patient associated with this tracking
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the salle where patient is tracked
     */
    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    /**
     * Get the specialization associated with this tracking
     */
    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    /**
     * Get the fiche navette item
     */
    public function ficheNavetteItem()
    {
        return $this->belongsTo(ficheNavetteItem::class, 'fiche_navette_item_id');
    }

    /**
     * Get the prestation
     */
    public function prestation()
    {
        return $this->belongsTo(Prestation::class);
    }

    /**
     * Get the user who created this tracking
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active trackings (checked in but not checked out)
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('check_in_time')
            ->whereNull('check_out_time');
    }

    /**
     * Scope to get trackings by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
