<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Kiosk  $kiosk
     * @return \Illuminate\Http\Response
     */
    public function show(Kiosk $kiosk)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Kiosk  $kiosk
     * @return \Illuminate\Http\Response
     */
    public function edit(Kiosk $kiosk)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Kiosk  $kiosk
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Kiosk $kiosk)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Kiosk  $kiosk
     * @return \Illuminate\Http\Response
     */
    public function destroy(Kiosk $kiosk)
    {
        //
    }

    public function getAdvertisements($kioskId)
    {
        $advertisements = Kiosk::find($kioskId)->advertisements()
            ->where('status', 'active')
            ->get();

        return response()->json(['data' => $advertisements]);
    }

    public function ping($id)
    {
        $kiosk = Kiosk::findOrFail($id);
        $kiosk->update(['last_seen' => now()]);

        return response()->json(['message' => 'Ping received'], 200);
    }

    public function lock(Request $request, $id)
    {
        $kiosk = Kiosk::findOrFail($id);
        $kiosk->update(['locked' => $request->input('locked')]);

        return response()->json(['message' => 'Kiosk status updated']);
    }

    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $kiosk = Kiosk::findOrFail($id);
        $kiosk->update([
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        return response()->json(['message' => 'Location updated']);
    }

    public function findNearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric', // en kilomètres
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius');

        $kiosks = Kiosk::selectRaw(
            "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude]
        )
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();

        return response()->json(['data' => $kiosks]);
    }

    public function updateName(Request $request, $id)
    {
        $request->validate(['name' => 'required|string']);

        $kiosk = Kiosk::findOrFail($id);
        $kiosk->update(['name' => $request->input('name')]);

        return response()->json(['message' => 'Name updated']);
    }

    public function assignAdvertisement(Request $request, $id)
    {
        $request->validate([
            'advertisement_id' => 'required|exists:advertisements,id',
        ]);

        $kiosk = Kiosk::findOrFail($id);
        $kiosk->advertisements()->attach($request->input('advertisement_id'));

        return response()->json(['message' => 'Advertisement assigned to kiosk']);
    }
}
