<?php
// app/Models/UserSpecialization.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Specialization;


class UserSpecialization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialization_id',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'specialization_id' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSpecialization($query, $specializationId)
    {
        return $query->where('specialization_id', $specializationId);
    }
}
