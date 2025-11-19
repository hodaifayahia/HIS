<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'image_url',
        'name',
        'description',
        'start_date',
        'end_date',
        'agmentation',
        'is_active',
    ];

    protected $casts = [
        'agmentation' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get all selling settings for this service
     */
    public function sellingSettings()
    {
        return $this->hasMany(SellingSettings::class, 'service_id');
    }

    /**
     * Get active selling settings for this service
     */
    public function activeSellingSettings()
    {
        return $this->hasMany(SellingSettings::class, 'service_id')->where('is_active', true);
    }

    /**
     * Get the service groups this service belongs to
     */
    public function serviceGroups()
    {
        return $this->belongsToMany(ServiceGroup::class, 'service_group_members', 'service_id', 'service_group_id')
            ->withPivot('sort_order', 'created_by', 'created_at')
            ->orderByPivot('sort_order', 'asc')
            ->withTimestamps();
    }

    /**
     * Get service group members
     */
    public function groupMemberships()
    {
        return $this->hasMany(ServiceGroupMember::class);
    }
}
