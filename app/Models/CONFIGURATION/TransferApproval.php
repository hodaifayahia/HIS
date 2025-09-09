<?php
// app/Models/Configuration/TransferApproval.php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class TransferApproval extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'maximum',
        'is_active',
        'note',
    ];

    protected $casts = [
        'maximum' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected $appends = [
        'formatted_maximum',
        'status_text',
        'status_color'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByMaximumRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('maximum', '>=', $min);
        }
        if ($max !== null) {
            $query->where('maximum', '<=', $max);
        }
        return $query;
    }

    // Accessors
    public function getFormattedMaximumAttribute()
    {
        return number_format($this->maximum, 2) . ' DZD';
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'danger';
    }

    // Methods
    public function canApproveTransfer($amount)
    {
        return $this->is_active && $amount <= $this->maximum;
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    // Static methods
    public static function getMaximumForUser($userId)
    {
        $approval = static::byUser($userId)->active()->first();
        return $approval ? $approval->maximum : 0;
    }

    public static function canUserApprove($userId, $amount)
    {
        $approval = static::byUser($userId)->active()->first();
        return $approval && $approval->canApproveTransfer($amount);
    }
}
