<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $fillable = [
        'name', // Added or renamed for consistency with front-end
        'description', // Added for consistency with front-end
        'file_path',
        'convention_id', // Assuming this is still relevant
    ];
}
