<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockageTool extends Model
{
    protected $fillable = [
        'stockage_id',
        'tool_type',
        'tool_number',
        'block',
        'shelf_level',
        'location_code',
    ];

    protected $casts = [
        'tool_number' => 'integer',
        'shelf_level' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tool) {
            $tool->location_code = $tool->generateLocationCode();
        });

        static::updating(function ($tool) {
            // Only regenerate if relevant fields changed
            if ($tool->isDirty(['tool_type', 'tool_number', 'block', 'shelf_level']) ||
                $tool->stockage->isDirty(['location_code']) ||
                $tool->stockage->service->isDirty(['service_abv'])) {
                $tool->location_code = $tool->generateLocationCode();
            }
        });
    }

    /**
     * Get the stockage that owns this tool.
     */
    public function stockage(): BelongsTo
    {
        return $this->belongsTo(Stockage::class);
    }

    /**
     * Get the tool type label.
     */
    public function getToolTypeLabelAttribute(): string
    {
        return match ($this->tool_type) {
            'RY' => 'Rayonnage',
            'AR' => 'Armoire',
            'CF' => 'Coffre',
            'FR' => 'Frigo',
            'CS' => 'Caisson',
            'CH' => 'Chariot',
            'PL' => 'Palette',
            default => $this->tool_type
        };
    }

    /**
     * Get the block label (only for Rayonnage).
     */
    public function getBlockLabelAttribute(): ?string
    {
        if ($this->tool_type !== 'RY' || ! $this->block) {
            return null;
        }

        return $this->block;
    }

    /**
     * Check if this tool type requires block and shelf level.
     */
    public function requiresBlockAndShelf(): bool
    {
        return $this->tool_type === 'RY';
    }

    /**
     * Generate location code based on the format rules.
     */
    public function generateLocationCode(): string
    {
        $serviceAbv = $this->stockage->service->service_abv;
        $stockageLocationCode = $this->stockage->location_code;

        $base = $serviceAbv.$stockageLocationCode.'-'.$this->tool_type.$this->tool_number;

        if ($this->tool_type === 'RY' && $this->block && $this->shelf_level) {
            $base .= '-'.$this->block.$this->shelf_level;
        }

        return $base;
    }
}
