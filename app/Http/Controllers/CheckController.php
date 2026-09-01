<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckRequest;
use App\Models\Calendar;
use App\Models\Card;
use App\Models\CheckList;
use App\Models\Location;
use App\Models\LocationIp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckController extends Controller
{
    /**
     * 嗶卡簽到／簽退
     */
    public function __invoke(CheckRequest $request)
    {
        $now = Carbon::now();

        $card = Card::with('owner')->where('inner_code', $request->validated()['inner_code'])->first();

        if (!$card) {
            return $this->fail($now, '查無此卡');
        }
        if (!$card->status) {
            return $this->fail($now, '此卡已被停用');
        }
        if (!$card->owner) {
            return $this->fail($now, '此卡的擁有者不存在');
        }
        if (!$card->owner->status) {
            return $this->fail($now, '此卡的擁有者已被停權');
        }

        return $this->handle($request, $card->owner, $now, (bool) config('duty.card_check_location'));
    }

    /**
     * 手機簽到／簽退。身分直接取自登入的使用者，不需要卡片
     */
    public function phone(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->status) {
            return $this->fail(Carbon::now(), '你的帳號已被停權');
        }

        return $this->handle($request, $user, Carbon::now(), (bool) config('duty.phone_check_location'));
    }

    /**
     * 嗶卡與手機共用的打卡流程
     *
     * @param bool $checkLocation 是否要驗證來源 IP 所屬的地點
     */
    private function handle(Request $request, User $user, Carbon $now, bool $checkLocation)
    {
        $clientIp = $request->ip();
        $location = LocationIp::resolve($clientIp);

        // 需要認地點時，IP 對不到任何地點就不給打
        if ($checkLocation && !$location) {
            return $this->fail($now, "這台裝置的 IP（{$clientIp}）尚未設定所屬地點，請聯絡管理員");
        }

        // 不論哪一種方式，都要當天真的有值勤才給簽
        $duty = $this->todayDuty($user, $checkLocation ? $location : null, $now);

        if (!$duty) {
            $where = $checkLocation && $location ? "在{$location->name}" : '';
            return $this->fail($now, "{$user->name} 今天{$where}沒有值勤，不能打卡");
        }

        // 打卡地點以值勤班表為準；有認地點時兩者本來就一致
        $dutyLocation = $location ?? $duty->location;

        $open = $this->openRecord($user, $dutyLocation);
        if ($open) {
            return $this->checkout($open, $now, $clientIp, $dutyLocation);
        }

        // 今天已經簽退過就不再開新的一筆，
        // 否則一個值勤名額會產生兩筆紀錄，狀態也會從「簽退」跳回「值勤中」
        if ($this->completedToday($user, $dutyLocation, $now)) {
            return $this->fail($now, "{$user->name} 今天已經完成值勤了");
        }

        return $this->checkin($user, $now, $clientIp, $dutyLocation);
    }

    private function completedToday(User $user, ?Location $location, Carbon $now): bool
    {
        return CheckList::where('user_id', $user->id)
            ->where('checkin_time', 'like', $now->toDateString() . '%')
            ->whereNotNull('checkout_time')
            ->when($location, fn ($query) => $query->where('checkin_location_id', $location->id))
            ->exists();
    }

    /**
     * 當天的值勤班表。有指定地點就限定該地點
     */
    private function todayDuty(User $user, ?Location $location, Carbon $now): ?Calendar
    {
        return Calendar::with('location')
            ->where('user_id', $user->id)
            ->where('duty_date', $now->toDateString())
            ->where('status', true)
            ->when($location, fn ($query) => $query->where('location_id', $location->id))
            ->first();
    }

    /**
     * 這個地點還沒簽退的紀錄。有的話這次就是簽退。
     *
     * 一定要限定地點：同一個人同一天可能在兩個地點都有值勤，
     * 不限定的話在寶山嗶卡會找到進德那筆還開著的紀錄，
     * 結果變成把進德簽退，而不是在寶山簽到。
     */
    private function openRecord(User $user, ?Location $location): ?CheckList
    {
        return CheckList::where('user_id', $user->id)
            ->whereNull('checkout_time')
            ->whereNotNull('checkin_time')
            ->when($location, fn ($query) => $query->where('checkin_location_id', $location->id))
            ->orderByDesc('checkin_time')
            ->first();
    }

    private function checkin(User $user, Carbon $now, string $ip, ?Location $location)
    {
        $checklist = CheckList::create([
            'user_id' => $user->id,
            'checkin_time' => $now,
            'checkin_operation' => 0,
            'checkin_ip' => $ip,
            'checkin_location_id' => $location?->id,
        ]);
        $checklist->load('user');

        return $this->ok($checklist, $now, "{$user->name} 簽到成功");
    }

    private function checkout(CheckList $checklist, Carbon $now, string $ip, ?Location $location)
    {
        $checkinTime = Carbon::parse($checklist->checkin_time);
        $buffer = (int) config('duty.check_interval_minutes');
        $readyAt = $checkinTime->copy()->addMinutes($buffer);

        // 簽到後要隔一段時間才能簽退，避免連刷兩次直接把值勤結束掉
        if ($now->lt($readyAt)) {
            $wait = $now->diff($readyAt);
            return $this->fail($now, "請等待 {$wait->i} 分 {$wait->s} 秒後再簽退", $checklist);
        }

        $checklist->update([
            'checkout_time' => $now,
            'checkout_operation' => 0,
            'checkout_ip' => $ip,
            'checkout_location_id' => $location?->id,
        ]);
        $checklist->load('user');

        return $this->ok($checklist, $now, "{$checklist->user->name} 簽退成功");
    }

    private function ok(CheckList $checklist, Carbon $now, string $message)
    {
        return redirect()->back()
            ->with('checklist', $checklist)
            ->with('status', true)
            ->with('time', $now->format('Y-m-d H:i:s'))
            ->with('message', $message);
    }

    private function fail(Carbon $now, string $message, ?CheckList $checklist = null)
    {
        return redirect()->back()
            ->with('checklist', $checklist)
            ->with('status', false)
            ->with('time', $now->format('Y-m-d H:i:s'))
            ->withErrors(['message' => $message]);
    }
}
