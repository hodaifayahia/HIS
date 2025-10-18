<?php

namespace App\Models;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonEntree extends Model
{
    use HasFactory;

    protected $fillable = [
        'bon_entree_code',
        'bon_reception_id',
        'fournisseur_id',
        'status',
        'total_amount',
        'service_id',
        'created_by',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'attachments' => 'array',
    ];

    // Relationships
    public function bonReception(): BelongsTo
    {
        return $this->belongsTo(BonReception::class, 'bon_reception_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BonEntreeItem::class, 'bon_entree_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByFournisseur($query, $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    public function scopeByService($query, $serviceAbv)
    {
        return $query->where('service_id', $serviceAbv);
    }

    // Accessors & Mutators
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'validated' => 'Validated',
            'transferred' => 'Transferred',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    // Methods
    public function calculateTotal()
    {
        $total = $this->items->sum(function ($item) {
            $priceWithTVA = $item->purchase_price * (1 + ($item->tva / 100));

            return $item->quantity * $priceWithTVA;
        });

        $this->update(['total_amount' => $total]);

        return $total;
    }

    public function generateCode()
    {
        $lastBonEntree = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastBonEntree ? ($lastBonEntree->id + 1) : 1;

        return 'BE-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    // Boot method for auto-generating code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bonEntree) {
            if (empty($bonEntree->bon_entree_code)) {
                $bonEntree->bon_entree_code = $bonEntree->generateCode();
            }
        });
    }
}
