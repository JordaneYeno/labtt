<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Models\Kiosk;

class AdvertisementController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'media_path' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $advertisement = Advertisement::create($data);

        return response()->json(['message' => 'Advertisement created!', 'data' => $advertisement], 201);
    }

    public function getActiveAds()
    {
        $now = now();
        $activeAds = Advertisement::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get();

        return response()->json(['data' => $activeAds]);
    }

    public function broadcast(Request $request)
    {
        $request->validate([
            'advertisement_id' => 'required|exists:advertisements,id',
        ]);

        $advertisementId = $request->input('advertisement_id');
        $kiosks = Kiosk::all();

        foreach ($kiosks as $kiosk) {
            $kiosk->advertisements()->attach($advertisementId);
        }

        return response()->json(['message' => 'Advertisement broadcasted to all kiosks']);
    }
}
