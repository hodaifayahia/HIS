<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAuditProduct extends Model
{
    use HasFactory;

    protected $table = 'inventory_audit_products';
    protected $fillable = [
        'inventory_audit_id',
        'product_id',
        'product_type',
        'stockage_id',
        'theoretical_quantity',
        'actual_quantity',
        'difference',
        'variance_percent',
        'notes',
        'audited_by',
        'audited_at',
        'status',
        'participant_id',
    ];

    protected $casts = [
        'theoretical_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'difference' => 'decimal:2',
        'variance_percent' => 'decimal:2',
        'audited_at' => 'datetime'
    ];

    /**
     * Get the product associated with this audit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stockage associated with this audit
     */
    public function stockage()
    {
        return $this->belongsTo(Stockage::class);
    }

    /**
     * Get the user who performed the audit
     */
    public function auditedBy()
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    /**
     * Scope to get audits with differences
     */
    public function scopeWithDifferences($query)
    {
        return $query->whereRaw('ABS(difference) > 0');
    }

    /**
     * Scope to get audits by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent audits
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('audited_at', '>=', now()->subDays($days));
    }
}
