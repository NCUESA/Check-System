<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckRequest;
use App\Models\Card;
use App\Models\CheckList;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;

class CheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CheckRequest $request)
    {
        //
        $now = Carbon::now();
        $nowStr = $now->timezone('Asia/Taipei')->format("Y-m-d H:m");

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
        $card = Card::where('inner_code', '=', $inner_code)->first();
        if (!$card) {
            return redirect()->back()
            ->with('status', false)
            ->with('time', $nowStr)
            ->withErrors(['message' => "查無此卡"]);
        }
        if (!$card->status) {
            return redirect()->back()
            ->with('status', false)
            ->with('time', $nowStr)
            ->withErrors(['message' => "此卡已被停用"]);
        }
        $card->load('owner');
        if (!$card->owner) {
            return redirect()->back()
            ->with('status', false)
            ->with('time', $nowStr)
            ->withErrors(['message' => "此卡的擁有者不存在"]);
        }
        if (!$card->owner->status) {
            return redirect()->back()
            ->with('status', false)
            ->with('time', $nowStr)
            ->withErrors(['message' => "此卡的擁有者已被停權"]);
        }
        $checklist = CheckList::where('person_id', '=', $card->owner->id)
        ->where('checkout_time', '=', null)
        ->whereNot('checkin_time', '=', null)
        ->orderByDesc('checkin_time')
        ->first();
        if ($checklist) {
            $checklist->load('person');
            $checkinTime = Carbon::parseFromLocale($checklist->checkin_time, 'zh-TW', 'Asia/Taipei');

            $checkinTimeAfter2Min = $checkinTime->addMinutes(2);
            $interval = $checkinTimeAfter2Min->diff($now, false);
            $diff = $now->diffInMinutes($checkinTime);
            if ($diff < 2 && $diff >= 0) {
                return redirect()->back()
                ->with('checklist', $checklist)
                ->with('time', $nowStr)
                ->with('status', false)
                ->withErrors(['message' => "請等待".$interval->minutes."分".$interval->seconds."秒後再簽退"]);
            }
            $checklist->update([
                'checkout_time' => $now, 
                'checkout_operation' => 0, 
                'checkout_ip' => $clientIp
            ]);
            return redirect()->back()
            ->with('checklist', $checklist)
            ->with('status', true)
            ->with('time', $nowStr);
        }
        else {
            $checklist = CheckList::create([
                'person_id' => $card->owner->id, 
                'checkin_time' => $now, 
                'checkin_operation' => 0, 
                'checkin_ip' => $clientIp
            ]);
            $checklist->load('person');
            return redirect()->back()
            ->with('checklist', $checklist)
            ->with('status', true)
            ->with('time', $nowStr);
        }
    }
}
