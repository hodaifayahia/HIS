<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;

class AppointmentModalityAbleForce extends Model
{
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_modality_able_forces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'modality_id',
        'user_id',
        'is_able_to_force'
    ];

    /**
     * Get the modality that owns the AppointmentModalityAbleForce.
     */
    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    /**
     * Get the user that owns the AppointmentModalityAbleForce.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
