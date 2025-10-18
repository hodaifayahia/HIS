<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureProformaAttachment extends Model
{
    protected $fillable = [
        'facture_proforma_id',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size'
    ];

    public function factureProforma()
    {
        return $this->belongsTo(FactureProforma::class);
    }
}
