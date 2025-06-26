<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use Inertia\Inertia;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $people = Person::orderBy('stu_id')->get();
        return Inertia::render("Person", [
            "people" => $people
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonRequest $request)
    {
        //
        $data = $request->validated();
        $person = Person::create($data);
        return redirect()->back()->with(['person' => $person]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonRequest $request, Person $person)
    {
        //
        $data = $request->validated();
        $person->update($data);
        return redirect()->back()->with(['person' => $person]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Person $person)
    {
        //
        $person->delete();
        return redirect()->back();
    }
}
