<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonReception extends Model
{
    protected $fillable = [
        'bonReceptionCode',
        'bon_commend_id',
        'bon_commend_date',
        'bon_livraison_numero',
        'bon_livraison_date',
        'facture_numero',
        'facture_date',
        'fournisseur_id',
        'bon_entree_id',
        'received_by',
        
        'created_by',
        'date_reception',
        'nombre_colis',
        'observation',
        'status',
        'is_confirmed',
        'confirmed_at',
        'confirmed_by',
        'attachments',
    ];

    protected $casts = [
        'bon_commend_date' => 'date',
        'bon_livraison_date' => 'date',
        'facture_date' => 'date',
        'date_reception' => 'date',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bonReception) {
            if (! $bonReception->bonReceptionCode) {
                $bonReception->bonReceptionCode = 'BR-'.str_pad(self::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relationships
    public function bonCommend(): BelongsTo
    {
        return $this->belongsTo(BonCommend::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BonReceptionItem::class);
    }
    public function bonEntree(): BelongsTo
{
    return $this->belongsTo(BonEntree::class, 'bon_entree_id');
}
    public function bonEntrees(): HasMany
    {
        return $this->hasMany(BonEntree::class, 'bon_reception_id');
    }

    public function bonRetour(): BelongsTo
    {
        return $this->belongsTo(BonRetour::class, 'bon_retour_id');
    }

    // Computed attributes
    public function getTotalItemsAttribute()
    {
        return $this->items->count();
    }

    public function getReceivedItemsAttribute()
    {
        return $this->items->where('status', 'received')->count();
    }

    public function getSurplusItemsAttribute()
    {
        return $this->items->where('quantity_surplus', '>', 0)->count();
    }

    public function getShortageItemsAttribute()
    {
        return $this->items->where('quantity_shortage', '>', 0)->count();
    }
}
