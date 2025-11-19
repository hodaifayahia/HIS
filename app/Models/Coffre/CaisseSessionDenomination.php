<?php
// app/Models/Coffre/CaisseSessionDenomination.php

namespace App\Models\Coffre;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaisseSessionDenomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'caisse_session_id',
        'denomination_type',
        'value',
        'quantity',
        'total_amount',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'quantity' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function caisseSession()
    {
        return $this->belongsTo(CaisseSession::class);
    }

    // Accessors
    public function getFormattedValueAttribute(): string
    {
        return $this->value >= 1 
            ? number_format($this->value, 0) . ' DA'
            : number_format($this->value, 2) . ' DA';
    }

    public function getDenominationDisplayAttribute(): string
    {
        return $this->formatted_value . ' (' . ($this->denomination_type === 'coin' ? 'Pièce' : 'Billet') . ')';
    }

    // Static method to get standard Algerian denominations
    public static function getStandardDenominations(): array
    {
        return [
            // Coins (Pièces)
        
            ['value' => 0.50, 'type' => 'coin', 'label' => '0.50 DA (Pièce)'],
            ['value' => 1.00, 'type' => 'coin', 'label' => '1 DA (Pièce)'],
            ['value' => 2.00, 'type' => 'coin', 'label' => '2 DA (Pièce)'],
            ['value' => 5.00, 'type' => 'coin', 'label' => '5 DA (Pièce)'],
            
            // Notes (Billets)
            ['value' => 10.00, 'type' => 'coin', 'label' => '10 DA (Billet)'],
            ['value' => 20.00, 'type' => 'coin', 'label' => '20 DA (Billet)'],
            ['value' => 50.00, 'type' => 'note', 'label' => '50 DA (Billet)'],
            ['value' => 100.00, 'type' => 'note', 'label' => '100 DA (Billet)'],
            ['value' => 200.00, 'type' => 'note', 'label' => '200 DA (Billet)'],
            ['value' => 500.00, 'type' => 'note', 'label' => '500 DA (Billet)'],
            ['value' => 1000.00, 'type' => 'note', 'label' => '1000 DA (Billet)'],
            ['value' => 2000.00, 'type' => 'note', 'label' => '2000 DA (Billet)'],
        ];
    }
}
