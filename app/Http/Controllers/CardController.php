<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $cards = Card::all();
        return response()->json(['success' => true, 'data' => $cards], 200);
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

        $res = Card::create([
            'person_id'=> $person_id,
            'inner_code'=> $inner_code,
            'status'=> $status
        ]);

        if ($res) {
            return response()->json(['success' => true, 'data' => "新增成功"]);  
        }
        else {
            return response()->json(['success' => false, 'data' => "新增失敗"]);
        }
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
    public function update(Request $request, Card $card)
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
    public function destroy(Card $card)
    {
        //
    }
}
