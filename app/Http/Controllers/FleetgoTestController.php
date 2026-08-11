<?php

namespace App\Http\Controllers;

use App\Services\FleetgoService;

class FleetgoTestController extends Controller
{
    public function testConnection()
    {
        try {
            $fleetgo = new FleetgoService();
            $positions = $fleetgo->getLastVehiclePositions();
            
            // Sadece ilk aracı göstermek için:
            return response()->json([
                'success' => true,
                'first_vehicle' => $positions[0] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
