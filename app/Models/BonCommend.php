<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonCommend extends Model
{
    protected $table = 'bon_commends';
    
    protected $fillable = [
        'bonCommendCode',
        'fournisseur_id',
        'service_demand_purchasing_id',
        'created_by',
        'status',
        'pdf_content',
        'pdf_generated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    /**
     * Get the supplier that owns the bon commend.
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Fournisseur::class, 'fournisseur_id');
    }

    /**
     * Get the service demand that this bon commend is for.
     */
    public function serviceDemand(): BelongsTo
    {
        return $this->belongsTo(ServiceDemendPurchcing::class, 'service_demand_purchasing_id');
    }

    /**
     * Get the user who created this bon commend.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get all items for this bon commend.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BonCommendItem::class, 'bon_commend_id');
    }

    /**
     * Get the attachments for this bon commend.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(BonCommendAttachment::class);
    }

      public function products()
    {
        return $this->hasMany(\App\Models\BonCommendItem::class, 'bon_commend_id');
    }

    /**
     * Scope a query to only include draft bon commends.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include sent bon commends.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope a query to only include paid bon commends.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include cancelled bon commends.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get the total amount for this bon commend.
     */
    public function getTotalAmountAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }

    /**
     * Get the service name through the service demand relationship.
     */
    public function getServiceNameAttribute()
    {
        return $this->serviceDemand?->service?->name;
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->bonCommendCode)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->bonCommendCode = 'BC-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
