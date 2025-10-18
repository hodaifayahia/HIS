<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureProforma extends Model
{
    
    protected $table = 'factureproformas';
    
    protected $fillable = [
        'factureProformaCode',
        'fournisseur_id',
        'service_demand_purchasing_id',
        'created_by',
        'status',
        'pdf_content', // New field for storing PDF content
        'pdf_generated_at', // New field for storing PDF generation timestamp
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    /**
     * Get the supplier that owns the facture proforma.
     */
    public function fournisseur()
    {
        return $this->belongsTo(\App\Models\Fournisseur::class, 'fournisseur_id');
    }
    public function products()
    {
        return $this->hasMany(FactureProformaProduct::class, 'factureproforma_id');
    }

    /**
     * Get the attachments for this facture proforma.
     */
    public function attachments()
    {
        return $this->hasMany(FactureProformaAttachment::class);
    }

    /**
     * Get the service demand that this facture proforma is for.
     */
    public function serviceDemand()
    {
        return $this->belongsTo(ServiceDemendPurchcing::class, 'service_demand_purchasing_id');
    }

    /**
     * Get the user who created this facture proforma.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
