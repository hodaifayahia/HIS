<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationworkspaceList extends Model
{

      protected $fillable = [
        'consultation_id',
        'consultation_workspace_id',
        'notes'
    ];



 protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    /**
     * Get the consultation workspace that owns the ConsultationworkspaceList.
     * This defines a many-to-one relationship where a ConsultationworkspaceList entry
     * belongs to a single Consultationworkspace.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consultationWorkspace()
    {
        return $this->belongsTo(Consultationworkspace::class, 'consultation_workspace_id');
    }
}
