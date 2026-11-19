<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Location;

class LocationController extends Controller
{
    // View
    // The page of edit Location information
    public function index()
    {
        return Inertia::render('System/Locations',[
            'locations' => Location::orderBy('id', 'asc')->get()
        ]);
    }


    // API

    // Get all locations
    public function getAllLocations()
    {
        $locations = Location::all();
        return response()->json($locations);
    }

    // Get usable locations (status = true)
    public function getUsableLocations()
    {
        $locations = Location::where('status', true)->get();
        return response()->json($locations);
    }

    // Get unusable locations (status = false)
    public function getUnusableLocations()
    {          
        $locations = Location::where('status', false)->get();
        return response()->json($locations);
    }

    // Store the new location information
    // @param Request $request
    //        Required fields: name, capacity, allowance, hours, status
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:0',
            'allowance' => 'required|integer|min:0',
            'hours' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        $location = Location::create([
            'name' => $request->input('name'),
            'capacity' => $request->input('capacity'),
            'allowance' => $request->input('allowance'),
            'hours' => $request->input('hours'),
            'status' => $request->input('status'),
        ]);

        return redirect()->back()->with('location', $location);
    }

    // Update the location information
    // @param Request $request
    //        Required fields: name, capacity, allowance, hours, status
    // @param Location $location
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:0',
            'allowance' => 'required|integer|min:0',
            'hours' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        $location->update([
            'name' => $request->input('name'),
            'capacity' => $request->input('capacity'),
            'allowance' => $request->input('allowance'),
            'hours' => $request->input('hours'),
            'status' => $request->input('status'),
        ]);

        return redirect()->back()->with('location', $location);
    }

    // Stop the location from being used in the system without deleting it
    // @param Request $request
    // @param Location $location
    //        Required fields: status (boolean)
    public function ignore(Request $request, Location $location)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);
        $location->update([
            'status' => $request->input('status'),
        ]);
        return redirect()->back()->with('location', $location);
    }
}