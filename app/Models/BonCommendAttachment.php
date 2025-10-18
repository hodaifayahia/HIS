<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonCommendAttachment extends Model
{
    protected $fillable = [
        'bon_commend_id',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
    ];

    public function bonCommend()
    {
        return $this->belongsTo(BonCommend::class);
    }
}
