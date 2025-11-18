<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_group_id',
        'service_id',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'service_group_id' => 'integer',
        'service_id' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Get the service group
     */
    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    /**
     * Get the service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who added this service to the group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for ordered members
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
