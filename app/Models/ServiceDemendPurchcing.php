<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceDemendPurchcing extends Model
{
    protected $table = 'service_demand_purchasings';

    protected $fillable = [
        'service_id',
        'demand_code',
        'expected_date',
        'status',
        'notes',
        'proforma_confirmed',
        'boncommend_confirmed',
        'proforma_confirmed_at',
        'boncommend_confirmed_at',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'proforma_confirmed' => 'boolean',
        'boncommend_confirmed' => 'boolean',
        'proforma_confirmed_at' => 'datetime',
        'boncommend_confirmed_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Service::class, 'service_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceDemendPurchcingItem::class, 'service_demand_purchasing_id');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->demand_code)) {
                $model->demand_code = 'DEM-'.str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
