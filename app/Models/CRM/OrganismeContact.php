<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Convenation\Organisme;

class OrganismeContact extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'organisme_id',
        'name',
        'phone',
        'email',
        'role'
    ];

    /**
     * Get the organisme that owns the contact.
     */
    public function organisme()
    {
        return $this->belongsTo(Organisme::class);
    }
}