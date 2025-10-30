<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when attempting to create a package from items with different doctor IDs
 * without explicit doctor selection
 */
class MultiDoctorException extends Exception
{
    public array $conflictingDoctorIds = [];

    public function __construct(string $message = "", array $data = [])
    {
        parent::__construct($message);
        $this->conflictingDoctorIds = $data['conflicting_doctor_ids'] ?? [];
    }

    /**
     * Render the exception into an HTTP response
     */
    public function render()
    {
        return response()->json([
            'error' => 'multiple_doctors',
            'message' => $this->message,
            'conflicting_doctor_ids' => $this->conflictingDoctorIds,
        ], 422);
    }
}
