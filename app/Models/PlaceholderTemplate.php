<?php

namespace App\Models;

use App\Models\Consultation;
use App\Models\Template;
use App\Models\Folder;
use App\Models\Placeholder;
use App\Models\Attribute;

use Illuminate\Database\Eloquent\Relations\Pivot;

use Illuminate\Database\Eloquent\Model;

class PlaceholderTemplate extends Pivot
{
    protected $table = 'placeholder_templates';
    
    public $incrementing = true;

    protected $fillable = [
        'template_id',
        'placeholder_id',
        'attribute_id'
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function placeholder()
    {
        return $this->belongsTo(Placeholder::class);
    }
    public function placeholders()
{
    return $this->belongsToMany(Placeholder::class, 'placeholder_templates')
                ->using(PlaceholderTemplate::class)
                ->withPivot('attribute_id')
                ->withTimestamps();
}

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
