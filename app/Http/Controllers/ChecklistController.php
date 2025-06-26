<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistRequest;
use App\Http\Requests\UpdateChecklistRequest;
use App\Models\CheckList;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class ChecklistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $query = CheckList::query()->with('person');
        $message = Session::get('message');

        $request = request();
        $name = $request->query('name');
        $sid = $request->query('sid');
        $year = $request->query('year');
        $month = $request->query('month');
        $checkin_at = $request->query('checkin_loc');
        $checkout_at = $request->query('checkout_loc');

        if ($name) {
            $query = $query->whereRelation('person', 'name', '=', $name);
        }
        if ($sid) {
            $query = $query->whereRelation('person', 'stu_id', '=', $sid);
        }
        if ($year) {
            $query = $query->whereYear('checkin_time', '=', $year);
        }
        if ($month) {
            $query = $query->whereMonth('checkin_time', '=', $month);
        }
        if ($checkin_at === "jinde") {
            $query = $query->where('checkin_ip', '=', "10.21.44.148");
        }
        elseif ($checkin_at === "baosan") {
            $query = $query->where('checkin_ip', '=', "10.21.44.35");
        }
        if ($checkout_at === "jinde") {
            $query = $query->where('checkout_ip', '=', "10.21.44.148");
        }
        elseif ($checkout_at === "baosan") {
            $query = $query->where('checkout_ip', '=', "10.21.44.35");
        }
        $checklists = $query->orderByDesc('checkin_time')->orderByDesc('checkout_time')->get();
        return Inertia::render("Checklist", [
            'checklists' => $checklists
        ]);
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
        $checkinTime = $data['checkin_time'];
        $checkoutTime = $data['checkout_time'];
        $checkinAt = $data['checkin_at'];
        $checkoutAt = $data['checkout_at'];
        $person = Person::where('stu_id', '=', $sid)->where('status', '=', 1)->first();
        if (!$person) {
            return redirect()->back()->withErrors(['message' => "Person with stu_id " . $sid . " not found."]);
        }
        $data['person_id'] = $person->id;
        $data['checkin_time'] = Carbon::parse($checkinTime)->timezone('Asia/Taipei')->format('Y-m-d H:i');
        $data['checkin_operation'] = 1;
        $data['checkout_time'] = Carbon::parse($checkoutTime)->timezone('Asia/Taipei')->format('Y-m-d H:i');
        $data['checkout_operation'] = 1;

        if ($checkinAt === "jinde") {
            $data['checkin_ip'] = "10.21.44.148";
        }
        elseif ($checkinAt === "baosan") {
            $data['checkin_ip'] = "10.21.44.35";
        }
        else {
            $data['checkout_ip'] = "0.0.0.0";
        }

        if ($checkoutAt === "jinde") {
            $data['checkout_ip'] = "10.21.44.148";
        }
        elseif ($checkoutAt === "baosan") {
            $data['checkout_ip'] = "10.21.44.35";
        }
        else {
            $data['checkout_ip'] = "0.0.0.0";
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
    public function update(UpdateChecklistRequest $request, CheckList $checkList)
    {
        //
        $data = $request->validated();
        $sid = $data['sid'];
        $checkinTime = $data['checkin_time'];
        $checkoutTime = $data['checkout_time'];
        $checkinAt = $data['checkin_at'];
        $checkoutAt = $data['checkout_at'];
        $person = Person::where('stu_id', '=', $sid)->where('status', '=', 1)->first();
        if (!$person) {
            return redirect()->back()->withErrors(['message' => "Person with stu_id " . $sid . " not found."]);
        }
        $data['person_id'] = $person->id;
        $data['checkin_time'] = Carbon::parse($checkinTime)->format('Y-m-d H:i');
        $data['checkin_operation'] = 1;
        $data['checkout_time'] = Carbon::parse($checkoutTime)->format('Y-m-d H:i');
        $data['checkout_operation'] = 1;

        if ($checkinAt === "jinde") {
            $data['checkin_ip'] = "10.21.44.148";
        }
        elseif ($checkinAt === "baosan") {
            $data['checkin_ip'] = "10.21.44.35";
        }
        else {
            $data['checkout_ip'] = "0.0.0.0";
        }

        if ($checkoutAt === "jinde") {
            $data['checkout_ip'] = "10.21.44.148";
        }
        elseif ($checkoutAt === "baosan") {
            $data['checkout_ip'] = "10.21.44.35";
        }
        else {
            $data['checkout_ip'] = "0.0.0.0";
        }

        $checkList->update($data);
        return redirect()->back()->with(['checklist' => $checkList]);
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
