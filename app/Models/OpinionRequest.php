<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpinionRequest extends Model
{
    protected $fillable = [
        'appointment_id',
        'sender_doctor_id',
        'patient_id',
        'reciver_doctor_id', // or 'reciver_doctor_id' if that's your column name
        'request',
        'status',
        'Reply',
    ];
public function getReplyAttribute()
{
    return $this->attributes['Reply'];
}
    public function senderDoctor()
    {
        return $this->belongsTo(Doctor::class, 'sender_doctor_id');
    }

    public function receiverDoctor()
    {
        return $this->belongsTo(Doctor::class, 'reciver_doctor_id'); // or 'reciver_doctor_id'
    }
}