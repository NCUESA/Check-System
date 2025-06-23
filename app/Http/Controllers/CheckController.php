<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckRequest;
use App\Models\Card;
use App\Models\CheckList;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CheckRequest $request)
    {
        //
        $data = $request->validated();
        $inner_code = $data['inner_code'];
        $card = Card::where('inner_code', '=', $inner_code)->where('status', '=', 1)->first();
        if (!$card) {
            return redirect()->back()->withErrors(['message' => "Cannot find this card :("]);
        }
        $card->load('owner');
        if (!$card->owner()?->status) {
            return redirect()->back()->withErrors(['message' => "Cannot find the card owner :("]);
        }
        $now = Carbon::now();
        $checklist = CheckList::where('person_id', '=', $card->owner()->id)
        ->where('checkout_time', '=', null)
        ->whereNot('checkin_time', '=', null)
        ->orderByDesc('checkin_time')
        ->first();
        if ($checklist) {
            $checklist->update([
                'checkout_time' => $now, 
                'checkout_operation' => 1
            ]);
        }
        else {
            $checklist = CheckList::create([
                'person_id' => $card->owner()->id, 
                'checkin_time' => $now, 
                'checkin_operation' => 1
            ]);
        }
    }
}
