<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardCollection;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\Person;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $cards = Card::with('owner')->orderBy('inner_code')->get();
        $people = Person::where('status', '=', true)->orderBy('stu_id')->get();
        return Inertia::render("Cards", ['cards' => $cards, 'people' => $people]);
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
    public function store(Request $request)
    {
        //
        $request->validate([
            'person_id' => 'required|exists:person,id', 
            'inner_code' => 'required|max:50', 
            'status' => 'required|boolean',
        ]);

        $person_id = $request->input('person_id');
        $inner_code = $request->input('inner_code');
        $status = $request->input('status');

        $card = Card::create([
            'person_id'=> $person_id,
            'inner_code'=> $inner_code,
            'status'=> $status
        ]);

        return redirect()->back()->with(['card' => $card]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Card $card)
    {
        //
        return response()->json($card);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Card $card)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
        $request->validate([
            'id' =>  'required|exists:cards,id', 
            'person_id' => 'required|exists:person,id', 
            'inner_code' => 'required|max:50', 
            'status' => 'required|boolean',
        ]);

        $id = $request->input('id');
        $card = Card::where('id', '=', $id);

        $person_id = $request->input('person_id');
        $inner_code = $request->input('inner_code');
        $status = $request->input('status');

        $res = $card->update([
            'person_id'=> $person_id,
            'inner_code'=> $inner_code,
            'status'=> $status
        ]);

        if ($res) {
            return response()->json(['success'=> true, 'data'=> "修改成功"]);
        }
        else {
            return response()->json(['success'=> false, 'data'=> '修改失敗']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
        $request->validate([
            'id' =>  'required|exists:cards,id'
        ]);

        $id = $request->input('id');
        $res = Card::where('id', '=', $id)->delete();

        if ($res) {
            return response()->json(['success'=> true, 'data'=> "刪除成功"]);
        }
        else {
            return response()->json(['success'=> false, 'data'=> '刪除失敗']);
        }

    }
}
