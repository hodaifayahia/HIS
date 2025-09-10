<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use doctor and tempalte placeholder relation
use App\Models\Doctor;
use App\Models\PlaceholderTemplate;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Template extends Model
{
    protected $fillable = [
        'name',
        'description',
        'file_path',
        'content',
        'doctor_id',
        'mime_type',
        'folder_id'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function placeholders()
    {
        return $this->belongsToMany(Placeholder::class, 'placeholder_templates')
                    ->withPivot('attribute_id')
                    ->using(PlaceholderTemplate::class)
                    ->withTimestamps();
    }

    public function specializations()
    {
        return $this->belongsTo(Specialization::class);
    }
}
