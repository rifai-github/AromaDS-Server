<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function getRooms($unitId)
    {
        try {
            $unit = Unit::findOrFail($unitId);
            $rooms = $unit->rooms()->active()->orderBy('room_name')->get();

            return response()->json([
                'status' => 'success',
                'data' => $rooms
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load rooms: ' . $e->getMessage()
            ], 500);
        }
    }
}