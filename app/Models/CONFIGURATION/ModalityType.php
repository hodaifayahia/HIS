<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;

class ModalityType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_url'
    ];

    
}
