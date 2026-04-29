<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function getUnits($floorId)
    {
        try {
            $floor = Floor::findOrFail($floorId);
            $units = $floor->units()->active()->orderBy('unit_number')->get();

            return response()->json([
                'status' => 'success',
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load units: ' . $e->getMessage()
            ], 500);
        }
    }
}