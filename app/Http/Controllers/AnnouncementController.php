<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $announcements = Announcement::query()->orderByDesc('on_top')->orderBy('created_at')->get();
        return Inertia::render('Announcements', [
            'announcements' => $announcements
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return Inertia::render("CreateAnnouncement");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnnouncementRequest $request)
    {
        //
        $data = $request->validated();
        $announcement = Announcement::create($data);
        if (!$announcement) {
            return redirect()->back()->withErrors([
                'message' => 'Cannot create the announcement.'
            ]);
        }
        return redirect()->route('announcements.index')->with('announcement', $announcement);
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement)
    {
        //
        return Inertia::render("UpdateAnnouncement", [
            'announcement' => $announcement
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        //
        $data = $request->validated();
        $success = $announcement->update($data);
        if ($success) {
            return redirect()->route('announcements.index');
        }
        else {
            return redirect()->back()->withErrors([
                'message' => 'Cannot update the announcement.'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        //
        $success = $announcement->delete();
        if ($success) {
            return redirect()->route('announcements.index');
        }
        else {
            return redirect()->back()->withErrors([
                'message' => 'Cannot delete the announcement.'
            ]);
        }
    }
}
