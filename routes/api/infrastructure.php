<?php

use App\Http\Controllers\INFRASTRUCTURE\BedController;
use App\Http\Controllers\INFRASTRUCTURE\InfrastructureDashboardController;
use App\Http\Controllers\INFRASTRUCTURE\PavilionController;
use App\Http\Controllers\INFRASTRUCTURE\RoomController;
use App\Http\Controllers\INFRASTRUCTURE\RoomTypeController;
use Illuminate\Support\Facades\Route;

// Infrastructure Dashboard
Route::get('/dashboard/infrastructure/stats', [InfrastructureDashboardController::class, 'stats']);
Route::get('/infrastructure/recent-activity', [InfrastructureDashboardController::class, 'recentActivity']);

// Pavilions, Rooms, Beds
Route::apiResource('/pavilions', PavilionController::class);
Route::get('/pavilions/{pavilionId}/services', [PavilionController::class, 'PavilionServices']);
Route::apiResource('/room-types', RoomTypeController::class);
Route::apiResource('/rooms', RoomController::class);
Route::apiResource('/beds', BedController::class);
Route::get('/beds/availablerooms', [BedController::class, 'getAvailableRooms']);