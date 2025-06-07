<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistRequest;
use App\Models\CheckList;
use App\Models\Person;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChecklistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $query = CheckList::with('person');

        $request = request();
        $name = $request->query('name');
        $sid = $request->query('sid');
        $year = $request->query('year');
        $month = $request->query('month');
        $checkin_at = $request->query('checkin_at');
        $checkout_at = $request->query('checkout_at');

        if ($name) {
            $query = $query->where('name', '=', $name);
        }
        if ($sid) {
            $query = $query->where('sid', '=', $name);
        }
        if ($year) {
            $query = $query->where('year', '=', $name);
        }
        if ($month) {
            $query = $query->where('month', '=', $name);
        }
        if ($checkin_at === "jinde") {
            $query = $query->where('checkin_ip', '=', "10.21.44.148");
        }
        elseif ($checkin_at === "baosan") {
            $query = $query->where('checkin_ip', '=', "10.21.44.35");
        }
        if ($checkout_at === "jinde") {
            $query = $query->where('checkin_ip', '=', "10.21.44.148");
        }
        elseif ($checkout_at === "baosan") {
            $query = $query->where('checkin_ip', '=', "10.21.44.35");
        }

        $checklists = $query->get();
        return Inertia::render("Checklist", ['checklists' => $checklists]);
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
    public function store(StoreChecklistRequest $request)
    {
        //
        $data = $request->validated();
        $sid = $data['sid'];
        $checkinAt = $data['checkin_at'];
        $checkoutAt = $data['checkout_at'];
        $person = Person::where('stu_id', '=', $sid)->first();
        if (!$person) {
            return redirect()->back()->withErrors(['message' => "Person with stu_id " . $sid . " not found."]);
        }
        $data['person_id'] = $person->id;

        if ($checkinAt === "jinde") {
            $data['checkin_ip'] = "10.21.44.148";
        }
        elseif ($checkinAt === "baosan") {
            $data['checkin_ip'] = "10.21.44.35";
        }
        if ($checkoutAt=== "jinde") {
            $data['checkout_ip'] = "10.21.44.148";
        }
        elseif ($checkoutAt === "baosan") {
            $data['checkout_ip'] = "10.21.44.35";
        }

        $checkList = CheckList::create($data);
        return redirect()->back()->with(['check_list' => $checkList]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CheckList $checkList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CheckList $checkList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CheckList $checkList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckList $checkList)
    {
        //
        $checkList->delete();
        return redirect()->back();
    }
}
