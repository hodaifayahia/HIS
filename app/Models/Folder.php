<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Placeholder;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $table ="folders";
    protected $fillable = [
        'name',
        'description',
        'doctor_id',
        'specializations_id',
    ];

    protected $appends = ['templates_count'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getTemplatesCountAttribute()
    {
        return $this->templates()->count();
    }

    public function templates()
    {
        return $this->hasMany(Template::class, 'folder_id');
    }

    public function placeholder()
    {
        return $this->hasMany(Placeholder::class);
    }

    public function specializations()
    {
        return $this->hasMany(Specialization::class);
    }
}
