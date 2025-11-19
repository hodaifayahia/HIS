<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_id',
        'type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'verified',
        'verified_by',
        'uploaded_by',
        'is_physical_uploaded',
        'is_digital_verified',
        'created_by',
    ];

    protected $casts = [
        'is_physical_uploaded' => 'boolean',
        'is_digital_verified' => 'boolean',
        'verified' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * Get the admission
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the uploader
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
