<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransportBus;
use App\Models\TransportBusLocation;

class TransportController extends Controller
{
    public function index()
    {
        $buses = TransportBus::with('latestLocation')->orderBy('name')->get();
        return view('transport.index', compact('buses'));
    }

    public function updateLocation(Request $request, int $busId)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        TransportBusLocation::create([
            'bus_id' => $busId,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => $request->recorded_at ?: now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
