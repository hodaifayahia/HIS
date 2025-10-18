<?php

namespace App\Http\Controllers\INFRASTRUCTURE;


use App\Http\Controllers\Controller;
use App\Models\INFRASTRUCTURE\Pavilion;
use App\Models\INFRASTRUCTURE\Room;
use App\Models\INFRASTRUCTURE\RoomType;
use App\Models\INFRASTRUCTURE\Bed;
use Illuminate\Http\Request;

class InfrastructureDashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'totalPavilions' => Pavilion::count(),
            'totalRooms' => Room::count(),
            'totalBeds' => Bed::count(),
            'occupiedBeds' => Bed::where('status', Bed::STATUS_OCCUPIED)->count(),
            'availableRooms' => Room::where('status', 'available')->count(),
            'occupiedRooms' => Room::where('status', 'occupied')->count(),
            'maintenanceRooms' => Room::where('status', 'maintenance')->count(),
        ];

        return response()->json($stats);
    }

    public function recentActivity()
    {
        // This would track actual infrastructure changes
        // For now, return mock data
        $activities = [
            [
                'id' => 1,
                'type' => 'room_status_change',
                'description' => 'Room 101 status changed to Available',
                'timestamp' => now()->subHours(1)->toISOString(),
                'user' => 'System Admin'
            ],
            [
                'id' => 2,
                'type' => 'bed_assignment',
                'description' => 'Bed A assigned to patient',
                'timestamp' => now()->subHours(2)->toISOString(),
                'user' => 'Nurse Manager'
            ],
            // Add more mock activities...
        ];

        return response()->json(['data' => $activities]);
    }
}

