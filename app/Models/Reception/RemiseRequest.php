<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Reception\RemiseRequestPrestation;
use App\Models\Reception\RemiseRequestNotification;
use App\Models\Reception\RemiseRequestApproval;
use App\Models\User;
use App\Models\Patient;

class RemiseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id', 
        'patient_id',
        'status',
        'message',
        'total_amount'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function prestations()
    {
        return $this->hasMany(RemiseRequestPrestation::class);
    }

    public function notifications()
    {
        return $this->hasMany(RemiseRequestNotification::class);
    }

    public function approvals()
    {
        return $this->hasMany(RemiseRequestApproval::class);
    }
}
