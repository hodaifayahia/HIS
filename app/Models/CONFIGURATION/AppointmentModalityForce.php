<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\Modality;
use App\Models\User;

class AppointmentModalityForce extends Model
{
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_modality_forces';
    protected $fillable = [
        'modality_id',
        'start_time',
        'end_time',
        'number_of_patients',
        'user_id',
        'is_able_to_force'
    ];

    /**
     * Get the modality that owns the appointment modality force.
     */
    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    /**
     * Get the user that owns the appointment modality force.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
