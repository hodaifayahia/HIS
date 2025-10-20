<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModalityType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'image_url'
    ];

    
}
