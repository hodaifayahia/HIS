<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $table = 'agreements'; // Explicitly set the table name if it's not the plural form of the model name

    protected $fillable = [
        'name', // Added or renamed for consistency with front-end
        'description', // Added for consistency with front-end
        'file_path',
        'convention_id', // Assuming this is still relevant
    ];
}
