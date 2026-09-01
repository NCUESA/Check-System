<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\DutyPeriod;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DutyPeriodController extends Controller
{
    // 一次最多產生一年份，避免手滑輸入錯誤的日期範圍塞爆資料表
    private const MAX_DAYS = 366;

    // 值勤時間設定：開放某段期間的值勤填寫，過了截止時間就 Lock
    public function index()
    {
        $periods = DutyPeriod::query()
            ->withCount([
                'calendars as slot_count',
                'calendars as filled_count' => fn ($q) => $q->whereNotNull('user_id'),
            ])
            ->orderByDesc('duty_start_date')
            ->get()
            ->map(fn ($period) => [
                'id' => $period->id,
                'name' => $period->name,
                'duty_start_date' => $period->duty_start_date->toDateString(),
                'duty_end_date' => $period->duty_end_date->toDateString(),
                'open_at' => $period->open_at->format('Y-m-d\TH:i'),
                'close_at' => $period->close_at->format('Y-m-d\TH:i'),
                'status' => $period->status,
                'slot_count' => $period->slot_count,
                'filled_count' => $period->filled_count,
                // 未開放 / 開放中 / 已截止，前端直接顯示不用再算一次
                'phase' => $this->phaseOf($period),
            ]);

        return Inertia::render('DutyManagement/Periods', [
            'periods' => $periods,
            'locations' => Location::where('status', true)->orderBy('id', 'asc')->get(),
        ]);
    }

    // 建立期間，並依各地點的 capacity 產生名額
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duty_start_date' => 'required|date',
            'duty_end_date' => 'required|date|after_or_equal:duty_start_date',
            'open_at' => 'required|date',
            'close_at' => 'required|date|after:open_at',
            // 前端逐日勾選，不再只能選星期
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date_format:Y-m-d',
            'location_ids' => 'required|array|min:1',
            'location_ids.*' => 'integer|exists:locations,id',
            'status' => 'required|boolean',
        ], [
            'name.required' => '請填寫期間名稱。',
            'duty_start_date.required' => '請選擇值勤開始日。',
            'duty_end_date.required' => '請選擇值勤結束日。',
            'duty_end_date.after_or_equal' => '值勤結束日不能早於開始日。',
            'open_at.required' => '請選擇開放填寫的時間。',
            'close_at.required' => '請選擇填寫截止的時間。',
            'close_at.after' => '截止時間必須晚於開放時間。',
            'dates.required' => '請至少勾選一個值勤日期。',
            'dates.*.date_format' => '值勤日期的格式不正確。',
            'location_ids.required' => '請至少選擇一個開放的地點。',
        ]);

        // 去重排序後再檢查，避免前端送重複的日期
        $dates = array_values(array_unique($validated['dates']));
        sort($dates);

        if (count($dates) > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'dates' => '一次最多只能開 ' . self::MAX_DAYS . ' 天。',
            ]);
        }

        // 勾選的日期一定要落在值勤日期範圍內。Y-m-d 可以直接比字串大小
        foreach ($dates as $date) {
            if ($date < $validated['duty_start_date'] || $date > $validated['duty_end_date']) {
                throw ValidationException::withMessages([
                    'dates' => "勾選的 {$date} 不在值勤日期範圍內。",
                ]);
            }
        }

        $result = DB::transaction(function () use ($validated, $dates) {
            $period = DutyPeriod::create([
                'name' => $validated['name'],
                'duty_start_date' => $validated['duty_start_date'],
                'duty_end_date' => $validated['duty_end_date'],
                'open_at' => $validated['open_at'],
                'close_at' => $validated['close_at'],
                'status' => $validated['status'],
            ]);

            return $this->generateSlots($period, $dates, $validated['location_ids']);
        });

        return redirect()->back()->with('message', $this->generationMessage($result));
    }

    // 只改名稱、開放時間與狀態
    // 刻意不動值勤日期範圍：改了會跟已經產生的名額對不起來，也會影響已經排班的人
    public function update(Request $request, DutyPeriod $dutyPeriod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'open_at' => 'required|date',
            'close_at' => 'required|date|after:open_at',
            'status' => 'required|boolean',
        ], [
            'name.required' => '請填寫期間名稱。',
            'open_at.required' => '請選擇開放填寫的時間。',
            'close_at.required' => '請選擇填寫截止的時間。',
            'close_at.after' => '截止時間必須晚於開放時間。',
        ]);

        $dutyPeriod->update($validated);

        return redirect()->back();
    }

    // 已經有人排班的期間不給刪，避免一次抹掉一整個月的班表
    public function destroy(DutyPeriod $dutyPeriod)
    {
        $filled = $dutyPeriod->calendars()->whereNotNull('user_id')->count();
        if ($filled > 0) {
            throw ValidationException::withMessages([
                'period' => "這期已經有 {$filled} 筆排班，不能刪除。若要停止填寫請改成「停用」。",
            ]);
        }

        DB::transaction(function () use ($dutyPeriod) {
            $dutyPeriod->calendars()->delete();
            $dutyPeriod->delete();
        });

        return redirect()->back();
    }

    // 依「勾選的日期 × 地點 × capacity」產生名額
    // 已經有名額的 (日期, 地點) 組合會略過，不重複開
    private function generateSlots(DutyPeriod $period, array $dates, array $locationIds): array
    {
        $locations = Location::whereIn('id', $locationIds)->get();

        // 先撈出這些日期已經有名額的 (日期, 地點)，之後用來比對
        $existing = Calendar::whereIn('location_id', $locationIds)
            ->whereIn('duty_date', $dates)
            ->get(['duty_date', 'location_id'])
            ->map(fn ($slot) => $slot->duty_date->toDateString() . '|' . $slot->location_id)
            ->flip();

        $now = now();
        $rows = [];
        $skipped = 0;

        foreach ($dates as $date) {
            foreach ($locations as $location) {
                if ($location->capacity < 1) {
                    continue;
                }

                if ($existing->has($date . '|' . $location->id)) {
                    $skipped++;
                    continue;
                }

                for ($i = 0; $i < $location->capacity; $i++) {
                    $rows[] = [
                        'period_id' => $period->id,
                        'location_id' => $location->id,
                        'user_id' => null,
                        'status' => true,
                        'duty_date' => $date,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // 分批寫入，一次塞太多列有些 MySQL 設定會擋
        foreach (array_chunk($rows, 500) as $chunk) {
            Calendar::insert($chunk);
        }

        return ['created' => count($rows), 'skipped' => $skipped];
    }

    private function generationMessage(array $result): string
    {
        $message = "已產生 {$result['created']} 個名額。";
        if ($result['skipped'] > 0) {
            $message .= "有 {$result['skipped']} 個「日期 × 地點」已經有名額，已略過。";
        }
        return $message;
    }

    private function phaseOf(DutyPeriod $period): string
    {
        if (!$period->status) {
            return 'disabled';
        }
        if (now()->lt($period->open_at)) {
            return 'upcoming';
        }
        if (now()->gt($period->close_at)) {
            return 'closed';
        }
        return 'open';
    }
}
