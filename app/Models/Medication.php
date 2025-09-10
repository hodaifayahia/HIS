<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;


class Medication extends Model
{
    use SoftDeletes;
    // Constants for timestamps (corrected syntax)
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $table = 'medications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $fillable = [
        'designation',
        'nom_commercial',
        'type_medicament',
        'forme',
        'boite_de',
        'code_pch'
    ];

    // IMPORTANT: Remove or comment out the 'created_at' and 'updated_at'
    // entries from the $casts array. Custom accessors will handle parsing.
    // If these are left, Laravel will attempt to cast the JSON string directly,
    // which will cause an error before the accessor can process it.
    protected $casts = [
        // 'created_at' => 'datetime:Y-m-d H:i:s', // Remove this line
        // 'updated_at' => 'datetime:Y-m-d H:i:s'  // Remove this line
    ];

    /**
     * Get the created_at attribute as a Carbon instance.
     * This accessor handles the {"$date":"..."} string format from the database.
     *
     * @param  string|null  $value The raw value from the database (e.g., '{"$date":"2025-01-04T15:36:24.536Z"}')
     * @return \Carbon\Carbon|null
     */
    public function getCreatedAtAttribute($value)
    {
        // Check if the value is a string, which indicates it might be the JSON format
        if (is_string($value)) {
            // Attempt to decode the JSON string
            $decoded = json_decode($value, true);

            // Check if decoding was successful and if it contains the '$date' key
            if (is_array($decoded) && isset($decoded['$date'])) {
                // If it's the {"$date":"..."} format, parse the inner date string
                return Carbon::parse($decoded['$date']);
            }
        }

        // If the value is not a string, or not in the {"$date":"..."} format,
        // try to parse it as a standard date string or return null if empty.
        // This handles cases where dates might be stored in a standard format
        // or are already Carbon instances (e.g., after initial creation/update).
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Get the updated_at attribute as a Carbon instance.
     * This accessor handles the {"$date":"..."} string format from the database.
     *
     * @param  string|null  $value The raw value from the database
     * @return \Carbon\Carbon|null
     */
    public function getUpdatedAtAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && isset($decoded['$date'])) {
                return Carbon::parse($decoded['$date']);
            }
        }
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Set the created_at attribute.
     * This mutator ensures that when you save a Medication model, the 'created_at'
     * attribute is stored in a standard database datetime format.
     *
     * @param mixed $value
     * @return void
     */
    public function setCreatedAtAttribute($value)
    {
        // Ensure that the value is always a Carbon instance before saving to the database
        // If $value is null or empty, it will default to the current timestamp.
        $this->attributes['created_at'] = $value ? Carbon::parse($value) : now();
    }

    /**
     * Set the updated_at attribute.
     * This mutator ensures that when you save a Medication model, the 'updated_at'
     * attribute is stored in a standard database datetime format.
     *
     * @param mixed $value
     * @return void
     */
    public function setUpdatedAtAttribute($value)
    {
        // Ensure that the value is always a Carbon instance before saving to the database
        // If $value is null or empty, it will default to the current timestamp.
        $this->attributes['updated_at'] = $value ? Carbon::parse($value) : now();
    }
}
