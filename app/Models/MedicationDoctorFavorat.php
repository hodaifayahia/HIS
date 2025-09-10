<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationDoctorFavorat extends Model
{
    use HasFactory;
    
    protected $table = 'medication_doctor_favorats';
    protected $fillable = [
        'medication_id',
        'doctor_id',
        'favorited_at',
    ];

    protected $casts = [
        'favorited_at' => 'datetime'
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}