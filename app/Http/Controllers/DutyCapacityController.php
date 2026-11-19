<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\DutyPeriod;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DutyCapacityController extends Controller
{
    // 單一日期、地點的名額上限，避免手滑輸入一個大數字
    private const MAX_TOTAL = 20;

    // 臨時增額：對特定日期、地點加開名額，不受值勤期間限制
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        $locations = Location::where('status', true)->orderBy('id', 'asc')->get();

        $date = $request->query('date') ?: now()->toDateString();
        $locationId = $request->integer('location_id') ?: $locations->first()?->id;

        return Inertia::render('DutyManagement/Capacity', [
            'locations' => $locations,
            // 加開時可以選擇歸屬到哪一期。不歸屬的話只有管理員能指派
            'periods' => DutyPeriod::where('status', true)
                ->orderByDesc('duty_start_date')
                ->get(['id', 'name', 'open_at', 'close_at'])
                ->map(fn ($period) => [
                    'id' => $period->id,
                    'name' => $period->name,
                    'is_open' => $period->isOpenForStudents(),
                ]),
            'date' => $date,
            'locationId' => $locationId,
            'slots' => $this->slotsOf($locationId, $date),
            // 就地指派用的人員清單。這頁本來就只有管理員進得來
            'users' => User::where('status', true)
                ->orderBy('stu_id', 'asc')
                ->get(['id', 'name', 'stu_id']),
        ]);
    }

    private function slotsOf(?int $locationId, string $date): array
    {
        if (!$locationId) {
            return [];
        }

        // period 一定要連 status / open_at / close_at 一起載入，
        // 少了它們 isOpenForStudents() 會讀到 null，所有名額都會被誤判成鎖定
        return Calendar::with(['user:id,name,stu_id', 'period:id,name,status,open_at,close_at'])
            ->where('location_id', $locationId)
            ->where('duty_date', $date)
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->id,
                'status' => $slot->status,
                'period' => $slot->period?->name,
                'locked' => $slot->isLockedForStudents(),
                'user' => $slot->user ? [
                    'id' => $slot->user->id,
                    'name' => $slot->user->name,
                    'stu_id' => $slot->user->stu_id,
                ] : null,
            ])
            ->toArray();
    }

    // 設定這一天、這個地點總共要有幾個名額
    // 比現有的多就加開，少就移除；已經有人排班的名額絕不動到
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'location_id' => 'required|integer|exists:locations,id',
            'total' => 'required|integer|min:0|max:' . self::MAX_TOTAL,
            // null 代表不歸屬任何期間，只有管理員能指派。只影響新加開的名額
            'period_id' => 'nullable|integer|exists:duty_periods,id',
        ], [
            'date.required' => '請選擇日期。',
            'date.date_format' => '日期格式不正確。',
            'location_id.required' => '請選擇地點。',
            'location_id.exists' => '找不到這個地點。',
            'total.required' => '請輸入總共要有幾個名額。',
            'total.integer' => '名額數必須是整數。',
            'total.min' => '名額數不能是負數。',
            'total.max' => '單一日期最多 ' . self::MAX_TOTAL . ' 個名額。',
            'period_id.exists' => '找不到這個值勤期間。',
        ]);

        $message = DB::transaction(function () use ($validated) {
            // 鎖住這天這個地點的名額，避免兩個管理員同時調整算出不同的差額
            $slots = Calendar::where('location_id', $validated['location_id'])
                ->where('duty_date', $validated['date'])
                ->lockForUpdate()
                ->get();

            $current = $slots->count();
            $target = $validated['total'];

            if ($target === $current) {
                return "名額數沒有變動，這天維持 {$current} 個。";
            }

            if ($target > $current) {
                return $this->addSlots($validated, $target - $current, $target);
            }

            return $this->removeSlots($slots, $current - $target, $target);
        });

        return redirect()->back()->with('message', $message);
    }

    private function addSlots(array $validated, int $amount, int $target): string
    {
        $now = now();

        Calendar::insert(array_fill(0, $amount, [
            'period_id' => $validated['period_id'] ?? null,
            'location_id' => $validated['location_id'],
            'user_id' => null,
            'status' => true,
            'duty_date' => $validated['date'],
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        return "已加開 {$amount} 個名額，這天共 {$target} 個。";
    }

    private function removeSlots($slots, int $amount, int $target): string
    {
        // 有人排班的是下限，不可能刪到低於這個數字
        $assigned = $slots->whereNotNull('user_id')->count();
        if ($target < $assigned) {
            throw ValidationException::withMessages([
                'total' => "這天已經有 {$assigned} 人排班，總數不能少於 {$assigned}。請先到「指定值勤」把人退掉。",
            ]);
        }

        // 只刪沒人的。優先刪已停用的，其次刪最後加開的（id 大的）
        $removing = $slots->whereNull('user_id')
            ->sortBy([['status', 'asc'], ['id', 'desc']])
            ->take($amount);

        Calendar::whereIn('id', $removing->pluck('id'))->delete();

        return "已移除 {$amount} 個名額，這天共 {$target} 個。";
    }

    // 停用或啟用單一名額
    // 已經有人排的不給改，要先把人退掉（到「指定值勤」處理）
    public function toggle(Request $request, Calendar $slot)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        if ($slot->user_id !== null) {
            throw ValidationException::withMessages([
                'status' => '這個名額已經有人排班，請先到「指定值勤」把人退掉。',
            ]);
        }

        $slot->update(['status' => $validated['status']]);

        return redirect()->back();
    }

    // 刪除名額。只能刪沒人排的
    public function destroy(Calendar $slot)
    {
        if ($slot->user_id !== null) {
            throw ValidationException::withMessages([
                'slot' => '這個名額已經有人排班，請先到「指定值勤」把人退掉。',
            ]);
        }

        DB::transaction(fn () => $slot->delete());

        return redirect()->back();
    }
}
