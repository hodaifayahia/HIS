<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class Surgical extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'procedure_name',
        'description',
        'surgery_date',
        'patient_id',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'patient_id',
        'pivot',
        'patient',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
