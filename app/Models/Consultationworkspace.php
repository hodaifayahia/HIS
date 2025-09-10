<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// No need to directly import ConsultationworkspaceList or Consultation here if they are in the same namespace
// use App\Models\ConsultationworkspaceList; // Not strictly needed here
// use App\Models\Consultation; // Not strictly needed here if in same namespace, but good for clarity

class Consultationworkspace extends Model
{
    protected $fillable = [
        'name',
        'doctor_id',
        'last_accessed',
        'is_archived',
        'description',
    ];

    // This is correct, but ensure 'workspaces_count' is meant to be the count of consultations
    protected $appends = ['workspaces_count'];

    public function getWorkspacesCountAttribute()
    {
        // Ensure the 'consultations' relationship is loaded, or eager load it when querying
        // If not loaded, this will trigger a separate query for each workspace, which can be inefficient (N+1 problem)
        // Best practice: use ->withCount('consultations') when fetching Consultationworkspace records
        return $this->consultations->count();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // Removed the problematic 'workspaces()' relationship as it's typically handled by belongsToMany
    // If you explicitly need to interact with the pivot table as its own model,
    // you would define a different relationship, e.g., hasMany(ConsultationworkspaceList::class, 'consultation_workspace_id').
    // However, for a many-to-many, `belongsToMany` is generally preferred.

    public function consultations(): BelongsToMany
    {
        // Corrected belongsToMany definition:
        // 1. Consultation::class: The related model
        // 2. 'consultation_workspace_lists': The actual pivot table name
        // 3. 'consultation_workspace_id': The foreign key of THIS model (Consultationworkspace) on the pivot table
        // 4. 'consultation_id': The foreign key of the RELATED model (Consultation) on the pivot table
        return $this->belongsToMany(
            Consultation::class,
            'consultationworkspace_lists', // Your pivot table name
            'consultation_workspace_id',    // FK of Consultationworkspace in pivot table
            'consultation_id'               // FK of Consultation in pivot table
        );
        // If your pivot table has extra columns like 'notes', you'd add ->withPivot('notes')
        // ->withPivot('notes');
    }
}