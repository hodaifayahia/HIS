<?php
// app/Models/Bank/Bank.php

namespace App\Models\Bank;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'swift_code',
        'address',
        'phone',
        'email',
        'website',
        'logo_url',
        'supported_currencies',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'supported_currencies' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function banques()
    {
        return $this->hasMany(Banque::class, 'bank_name', 'name');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    public function scopeSupportsCurrency($query, string $currency)
    {
        return $query->whereJsonContains('supported_currencies', $currency);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getSupportedCurrenciesTextAttribute(): string
    {
        return $this->supported_currencies 
            ? implode(', ', $this->supported_currencies) 
            : 'Not specified';
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array($currency, $this->supported_currencies ?? []);
    }

    public function updateSortOrder(int $order): void
    {
        $this->update(['sort_order' => $order]);
    }
}
