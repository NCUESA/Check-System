<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthIpRequest;
use App\Http\Requests\UpdateAuthIpRequest;
use App\Models\AuthIp;
use Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IPController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $ips = AuthIp::orderBy('ip_address')->get();
        return Inertia::render("Ip", ['ips' => $ips]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuthIpRequest $request)
    {
        //
        $data = $request->validated();
        $ip = AuthIp::create($data);
        return redirect()->back()->with(['ip' => $ip]);
    }

    /**
     * Display the specified resource.
     */
    public function show(AuthIp $authIp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AuthIp $authIp)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuthIpRequest $request, AuthIp $authIp)
    {
        //
        $data = $request->validated();
        $authIp->update($data);
        return redirect()->back()->with('ip', $authIp);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuthIp $authIp)
    {
        //
        $authIp->delete();
        return redirect()->back();
    }
}
