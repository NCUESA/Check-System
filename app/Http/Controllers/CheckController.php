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
        $clientIp= '127.0.0.1';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IPV6'])) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IPV6'];
        } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER["HTTP_X_REAL_IP"])) {
            $clientIp = $_SERVER["HTTP_X_REAL_IP"];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER('HTTP_X_FORWARDED_FOR');
            $clientIps = explode(',', $clientIp);
            $clientIp = $clientIps[0];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR']))
                $clientIp = $_SERVER['REMOTE_ADDR'];
        }

        $data = $request->validated();
        $inner_code = $data['inner_code'];
        $card = Card::where('inner_code', '=', $inner_code)->where('status', '=', 1)->first();
        if (!$card) {
            return redirect()->back()->withErrors(['message' => "Cannot find this card :("]);
        }
        $card->load('owner');
        if (!$card->owner?->status) {
            return redirect()->back()->withErrors(['message' => "Cannot find the card owner :("]);
        }
        $now = Carbon::parse(Carbon::now())->format('Y-m-d H:i');
        $checklist = CheckList::where('person_id', '=', $card->owner->id)
        ->where('checkout_time', '=', null)
        ->whereNot('checkin_time', '=', null)
        ->orderByDesc('checkin_time')
        ->first();
        if ($checklist) {
            $checklist->update([
                'checkout_time' => $now, 
                'checkout_operation' => 0, 
                'checkout_at' => $clientIp
            ]);
            return redirect()->back()->with('message', "簽退成功");
        }
        else {
            $checklist = CheckList::create([
                'person_id' => $card->owner->id, 
                'checkin_time' => $now, 
                'checkin_operation' => 0, 
                'checkin_at' => $clientIp
            ]);
            return redirect()->back()->with('message', "簽到成功");
        }
    }
}
