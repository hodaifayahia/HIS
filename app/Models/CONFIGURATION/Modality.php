<?php

namespace App\Models\CONFIGURATION; // Correct namespace

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // THIS IS THE CRUCIAL IMPORT: Use Laravel's Eloquent Builder
use App\Models\CONFIGURATION\ModalityType;
use App\Models\INFRASTRUCTURE\Room; // Assuming Room is still in CONFIGURATION or adjust if in INFRASTRUCTURE
use App\Models\Specialization; // Ensure this is correctly imported

class Modality extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'modalities'; // Assuming your table name is 'modalities'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'internal_code',
        'image_path',
        'modality_type_id',
        'dicom_ae_title',
        'port',
        'physical_location_id',
        'operational_status',
        'specialization_id',
        'integration_protocol',
        'connection_configuration',
        'data_retrieval_method',
        'ip_address',
        'consumption_type',
        'consumption_unit',
        'frequency',
        'time_slot_duration',
        'slot_type',
        'booking_window',
        'availability_months',
        'is_active',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'availability_months' => 'array',
        'time_slot_duration' => 'integer',
        'booking_window' => 'integer'
    ];

    /**
     * Get the modality type that owns the Modality.
     */
    public function modalityType()
    {
        return $this->belongsTo(ModalityType::class);
    }

    /**
     * Get the physical location that owns the Modality.
     */
    public function physicalLocation()
    {
        // Assuming Room is in App\Models\CONFIGURATION. If it's App\Models\INFRASTRUCTURE\Room,
        // you need to either import it as `use App\Models\INFRASTRUCTURE\Room;`
        // or use its full namespace: `return $this->belongsTo(\App\Models\INFRASTRUCTURE\Room::class, 'physical_location_id');`
        return $this->belongsTo(Room::class, 'physical_location_id');
    }


    /**
     * Get the specialization that owns the Modality.
     */
    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    /**
     * Get the AppointmentModalityForce associated with the Modality.
     */
    public function appointmentModalityForce()
    {
        return $this->hasOne(AppointmentModalityForce::class);
    }

    /**
     * Get the schedules for the modality.
     */
    public function schedules()
    {
        return $this->hasMany(ModalitySchedule::class);
    }
    /**
     * Get the available slots for the modality.
     */

    /**
     * Get the available months for the modality.
     */
    public function availableMonths()
    {
        return $this->hasMany(ModalityAvailableMonth::class);
    }

    // --- Local Scopes for Filtering ---

    /**
     * Scope a query to search for a term in various fields.
     */
    public function scopeSearch(Builder $query, ?string $searchTerm) // Changed type hint to Illuminate\Database\Eloquent\Builder
    {
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('internal_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('dicom_ae_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('ip_address', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('integration_protocol', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('data_retrieval_method', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('modalityType', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('specialization', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }
    }

    /**
     * Scope a query to filter by modality type ID.
     */
    public function scopeModalityType(Builder $query, $modalityTypeId) // Changed type hint
    {
        if ($modalityTypeId) {
            $query->where('modality_type_id', $modalityTypeId);
        }
    }

    /**
     * Scope a query to filter by specialization ID.
     */
    public function scopeSpecialization(Builder $query, $specializationId) // Changed type hint
    {
        if ($specializationId) {
            $query->where('specialization_id', $specializationId);
        }
    }

    /**
     * Scope a query to filter by operational status.
     */
    public function scopeOperationalStatus(Builder $query, ?string $status) // Changed type hint
    {
        if ($status) {
            $query->where('operational_status', $status);
        }
    }

    /**
     * Scope a query to filter by physical location ID.
     */
    public function scopePhysicalLocation(Builder $query, $locationId) // Changed type hint
    {
        if ($locationId) {
            $query->where('physical_location_id', $locationId);
        }
    }

    /**
     * Scope a query to filter by integration protocol.
     */
    public function scopeIntegrationProtocol(Builder $query, ?string $protocol) // Changed type hint
    {
        if ($protocol) {
            $query->where('integration_protocol', 'LIKE', "%{$protocol}%");
        }
    }

    /**
     * Scope a query to filter by data retrieval method.
     */
    public function scopeDataRetrievalMethod(Builder $query, ?string $method) // Changed type hint
    {
        if ($method) {
            $query->where('data_retrieval_method', 'LIKE', "%{$method}%");
        }
    }

    /**
     * Scope a query to filter by creation date range.
     */
    public function scopeCreatedAtRange(Builder $query, ?string $from, ?string $to) // Changed type hint
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
    }
}