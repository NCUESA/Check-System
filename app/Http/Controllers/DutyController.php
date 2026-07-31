<?php

namespace App\Http\Controllers;
use App\Models\Calendar;
use App\Models\CheckList;
use App\Models\Location;
use App\Models\LocationIp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
class DutyController extends Controller
{
    //

    // 嗶卡簽到。畫面上同時顯示今天所有地點的值勤清單
    public function index(Request $request)
    {
        return Inertia::render('Duty/Index', [
            'dutyList' => $this->todayDutyList(),
            // 這台機器所在的地點，讓現場知道自己被認成哪裡
            'terminal' => $this->terminalInfo($request),
        ]);
    }

    // 手機簽到
    public function phone(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $checksLocation = (bool) config('duty.phone_check_location');
        $location = LocationIp::resolve($request->ip());

        // 認地點時只看「這個地點」的班。
        // 不篩的話，同一天在兩個地點值勤的人會拿到隨便一筆，
        // 打卡狀態也會抓到別的地點那筆，畫面就會顯示成已完成。
        $duty = Calendar::with('location')
            ->where('user_id', $user?->id)
            ->where('duty_date', $today)
            ->where('status', true)
            ->when($checksLocation, fn ($query) => $query->where('location_id', $location?->id))
            ->first();

        $dutyLocation = $duty?->location;

        // 打卡紀錄也要限定同一個地點，跟 CheckController 的判斷一致。
        // 認地點但 IP 認不出來時完全不撈——否則會抓到別的地點那筆，
        // 畫面上的狀態與「上次打卡時間」都會是誤導的
        $record = ($checksLocation && !$location)
            ? null
            : CheckList::where('user_id', $user?->id)
                ->where('checkin_time', 'like', $today . '%')
                ->when($dutyLocation, fn ($query) => $query->where('checkin_location_id', $dutyLocation->id))
                ->orderByDesc('checkin_time')
                ->first();

        return Inertia::render('Duty/Phone', [
            'hasDuty' => (bool) $duty,
            'dutyLocation' => $dutyLocation?->name,
            'status' => $this->statusOf($record),
            'lastRecordTime' => $record?->checkout_time ?? $record?->checkin_time,
            'terminal' => $this->terminalInfo($request),
            'checksLocation' => $checksLocation,
            // IP 有沒有對應到地點。用來區分「這裡不能打卡」和「今天沒你的班」
            'locationKnown' => (bool) $location,
        ]);
    }

    // 今天所有地點的值勤清單，並帶上每個人的打卡狀態
    private function todayDutyList(): array
    {
        $today = now()->toDateString();

        // 先把今天的打卡紀錄撈出來，用 user_id 分組，避免在迴圈裡逐筆查
        $records = CheckList::where('checkin_time', 'like', $today . '%')
            ->orderBy('checkin_time')
            ->get()
            ->groupBy('user_id');

        return Calendar::with(['user:id,name,stu_id', 'location:id,name'])
            ->where('duty_date', $today)
            ->where('status', true)
            ->whereNotNull('user_id')
            ->get()
            ->sortBy([
                fn ($a, $b) => $a->location_id <=> $b->location_id,
                fn ($a, $b) => ($a->user->stu_id ?? '') <=> ($b->user->stu_id ?? ''),
            ])
            ->map(function ($slot) use ($records) {
                // 只認這個地點的紀錄。
                // 不能退而取「這個人的任何一筆」，否則同一天在兩個地點值勤的人，
                // 在其中一邊簽到會讓兩邊的狀態都變成「值勤中」
                $record = ($records[$slot->user_id] ?? collect())
                    ->first(fn ($r) => $r->checkin_location_id === $slot->location_id);

                return [
                    'id' => $slot->id,
                    'stu_id' => $slot->user->stu_id,
                    'name' => $slot->user->name,
                    'location' => $slot->location?->name,
                    'checkin_time' => $record?->checkin_time,
                    'checkout_time' => $record?->checkout_time,
                    'status' => $this->statusOf($record),
                ];
            })
            ->values()
            ->toArray();
    }

    // 未到 → 值勤中 → 簽退
    private function statusOf(?CheckList $record): string
    {
        if (!$record || !$record->checkin_time) {
            return 'absent';
        }
        return $record->checkout_time ? 'checked_out' : 'on_duty';
    }

    private function terminalInfo(Request $request): array
    {
        $location = LocationIp::resolve($request->ip());

        return [
            'ip' => $request->ip(),
            'location' => $location?->name,
        ];
    }
    // 值勤月曆頁面
    // 只帶出啟用中的地點，停用的地點不開放排班
    // 換月份或換地點時，前端用 Inertia partial reload 只重抓 slots
    public function calendar(Request $request)
    {
        $request->validate([
            'location_id' => 'nullable|integer|exists:locations,id',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $locations = Location::where('status', true)->orderBy('id', 'asc')->get();

        // 沒指定就用第一個啟用中的地點、以及當月
        $locationId = $request->integer('location_id') ?: $locations->first()?->id;
        $year = $request->integer('year') ?: now()->year;
        $month = $request->integer('month') ?: now()->month;

        return Inertia::render('Duty/Calendar', [
            'locations' => $locations,
            'locationId' => $locationId,
            'year' => $year,
            'month' => $month,
            'slots' => $this->monthSlots($locationId, $year, $month),
            // 管理員才需要，用來在月曆上把名額指派給特定人員
            'users' => $request->user()?->is_admin
                ? User::where('status', true)->orderBy('stu_id', 'asc')->get(['id', 'name', 'stu_id'])
                : [],
        ]);
    }

    // 管理員把某個名額指派給某個人
    // 路由已經掛了 admin middleware，這裡不再重複檢查身分
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'slot_id' => 'required|integer|exists:calendar,id',
            'user_id' => 'required|integer|exists:users,id',
        ], [
            'user_id.required' => '請先選擇要指派的人員。',
            'user_id.exists' => '找不到這位人員。',
            'slot_id.exists' => '找不到這個名額。',
        ]);

        DB::transaction(function () use ($validated) {
            $slot = Calendar::whereKey($validated['slot_id'])->lockForUpdate()->first();
            $date = $slot->duty_date->format('n/j');

            if (!$slot->status) {
                $this->reject("{$date} 的這個名額已停用。");
            }

            // 鎖之後重新確認，避免兩個管理員同時指派同一個名額
            if ($slot->user_id !== null) {
                $this->reject("{$date} 的這個名額剛剛已經有人了，請重新整理後再試。");
            }

            $duplicated = Calendar::where('location_id', $slot->location_id)
                ->where('duty_date', $slot->duty_date->toDateString())
                ->where('user_id', $validated['user_id'])
                ->exists();

            if ($duplicated) {
                $this->reject("這個人在 {$date} 的這個地點已經有班了。");
            }

            $slot->update(['user_id' => $validated['user_id']]);
        });

        return redirect()->back();
    }

    // 學生認領值勤名額，一次可以認領多個
    // 整批包在同一個交易裡：只要有一天不合格就全部退回，不會排一半
    public function fill(Request $request)
    {
        $validated = $request->validate([
            'slot_ids' => 'required|array|min:1',
            'slot_ids.*' => 'integer|exists:calendar,id',
        ]);

        $user = $request->user();
        $isAdmin = (bool) $user->is_admin;
        $today = now()->startOfDay();

        DB::transaction(function () use ($validated, $user, $isAdmin, $today) {
            // lockForUpdate 鎖住這幾列到交易結束
            // 不鎖的話，兩個人同時搶同一個空名額會後者蓋掉前者
            $slots = Calendar::with('period')
                ->whereIn('id', $validated['slot_ids'])
                ->lockForUpdate()
                ->get();

            foreach ($slots as $slot) {
                $date = $slot->duty_date->format('n/j');

                if (!$slot->status) {
                    $this->reject("{$date} 沒有開放值勤。");
                }

                // 管理員不受填寫期間限制
                if (!$isAdmin && $slot->isLockedForStudents()) {
                    $this->reject("{$date} 的填寫期間已經截止，請聯絡管理員。");
                }

                if ($slot->duty_date->startOfDay()->lt($today)) {
                    $this->reject("{$date} 已經過去了，不能排班。");
                }

                // 鎖之後重新確認，這裡才是真正擋住搶名額的地方
                if ($slot->user_id !== null) {
                    $this->reject("{$date} 的名額剛剛被別人排走了，請重新整理後再試。");
                }

                // 同一天同一地點不能重複排。連同這次迴圈已經寫進去的一起檢查
                $duplicated = Calendar::where('location_id', $slot->location_id)
                    ->where('duty_date', $slot->duty_date->toDateString())
                    ->where('user_id', $user->id)
                    ->exists();

                if ($duplicated) {
                    $this->reject("{$date} 你已經排過班了。");
                }

                $slot->update(['user_id' => $user->id]);
            }
        });

        return redirect()->back();
    }

    // 取消排班
    // 取消是把名額退回（user_id 設回 null），不是刪掉那一列，
    // 因為名額是管理員開的，退掉之後要留給別人接手
    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'slot_ids' => 'required|array|min:1',
            'slot_ids.*' => 'integer|exists:calendar,id',
        ]);

        $user = $request->user();
        $isAdmin = (bool) $user->is_admin;

        DB::transaction(function () use ($validated, $user, $isAdmin) {
            $slots = Calendar::with('period')
                ->whereIn('id', $validated['slot_ids'])
                ->lockForUpdate()
                ->get();

            foreach ($slots as $slot) {
                $date = $slot->duty_date->format('n/j');

                if ($slot->user_id === null) {
                    $this->reject("{$date} 本來就沒有人排班。");
                }

                // 一般使用者只能退自己的班，管理員可以退任何人的
                if (!$isAdmin && $slot->user_id !== $user->id) {
                    $this->reject("{$date} 不是你的班，不能取消。");
                }

                if (!$isAdmin && $slot->isLockedForStudents()) {
                    $this->reject("{$date} 的填寫期間已經截止，請聯絡管理員。");
                }

                $slot->update(['user_id' => null]);
            }
        });

        return redirect()->back();
    }

    // 讓驗證錯誤回到前端的 slot_ids 欄位，前端用 toastErrors 顯示
    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['slot_ids' => $message]);
    }

    // 取出某地點某月份的所有名額，以日期字串分組
    // 回傳格式：{ '2026-07-05': [{ id, status, user }, ...], ... }
    private function monthSlots(?int $locationId, int $year, int $month): array
    {
        if (!$locationId) {
            return [];
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return Calendar::with(['user:id,name', 'period'])
            ->where('location_id', $locationId)
            ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy(fn ($slot) => $slot->duty_date->toDateString())
            ->map(fn ($slots) => $slots->map(fn ($slot) => [
                'id' => $slot->id,
                'status' => $slot->status,
                'locked' => $slot->isLockedForStudents(),
                'user' => $slot->user ? ['id' => $slot->user->id, 'name' => $slot->user->name] : null,
            ])->values())
            ->toArray();
    }
}
