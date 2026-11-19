<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistRequest;
use App\Http\Requests\UpdateChecklistRequest;
use App\Models\CheckList;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ChecklistController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * 手動簽到退：管理員補登一筆簽到紀錄
     */
    public function manual()
    {
        return Inertia::render('DutyManagement/ManualCheck', [
            'locations' => $this->locationOptions(),
        ]);
    }

    /**
     * 簽到表總覽：查詢、匯出、編輯既有紀錄
     */
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'checkin_location_id' => 'nullable|integer|exists:locations,id',
            'checkout_location_id' => 'nullable|integer|exists:locations,id',
        ]);

        $checklists = $this->filtered($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn ($row) => $this->presentRow($row));

        return Inertia::render('DutyManagement/Checklist', [
            'checklists' => $checklists,
            'locations' => $this->locationOptions(),
            'filters' => [
                'q' => $request->query('q'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'checkin_location_id' => $request->query('checkin_location_id'),
                'checkout_location_id' => $request->query('checkout_location_id'),
            ],
            // 匯出時前端才用 partial reload 要這一份
            // 平常不載入，否則分頁就沒有意義了
            'exportRows' => Inertia::optional(
                fn () => $this->filtered($request)->get()->map(fn ($row) => $this->presentRow($row))
            ),
        ]);
    }

    /**
     * 查詢條件。分頁與匯出共用同一份，避免兩邊條件走鐘
     */
    private function filtered(Request $request): Builder
    {
        $keyword = $request->query('q');

        return CheckList::query()
            ->with([
                'user:id,name,stu_id',
                'checkinLocation:id,name',
                'checkoutLocation:id,name',
            ])
            // 姓名與學號合併成一個關鍵字
            ->when($keyword, fn (Builder $query) => $query->whereHas(
                'user',
                fn (Builder $u) => $u->where('name', 'like', "%{$keyword}%")
                    ->orWhere('stu_id', 'like', "%{$keyword}%")
            ))
            // checkin_time 是字串欄位，格式有 'Y-m-d H:i' 也有 'Y-m-d H:i:s'，
            // 兩者都能用字串大小比較，所以不轉型直接比
            ->when(
                $request->query('date_from'),
                fn (Builder $query, $from) => $query->where('checkin_time', '>=', $from)
            )
            ->when(
                $request->query('date_to'),
                fn (Builder $query, $to) => $query->where('checkin_time', '<=', $to . ' 23:59:59')
            )
            ->when(
                $request->query('checkin_location_id'),
                fn (Builder $query, $id) => $query->where('checkin_location_id', $id)
            )
            ->when(
                $request->query('checkout_location_id'),
                fn (Builder $query, $id) => $query->where('checkout_location_id', $id)
            )
            ->orderByDesc('checkin_time');
    }

    private function presentRow(CheckList $row): array
    {
        return [
            'id' => $row->id,
            'checkin_time' => $row->checkin_time,
            'checkout_time' => $row->checkout_time,
            // 0 = 刷卡（自動），1 = 手動補登
            'checkin_manual' => (bool) $row->checkin_operation,
            'checkout_manual' => (bool) $row->checkout_operation,
            'checkin_ip' => $row->checkin_ip,
            'checkout_ip' => $row->checkout_ip,
            'checkin_location_id' => $row->checkin_location_id,
            'checkout_location_id' => $row->checkout_location_id,
            'checkin_location' => $row->checkinLocation?->name,
            'checkout_location' => $row->checkoutLocation?->name,
            'user' => $row->user ? [
                'id' => $row->user->id,
                'name' => $row->user->name,
                'stu_id' => $row->user->stu_id,
            ] : null,
        ];
    }

    // 下拉選單用。停用的地點（例如「系統」）也要列出來，
    // 因為歷史紀錄還是會指到它
    private function locationOptions()
    {
        return Location::orderBy('id', 'asc')->get(['id', 'name']);
    }

    public function store(StoreChecklistRequest $request)
    {
        $data = $this->prepare($request->validated());
        CheckList::create($data);

        return redirect()->back();
    }

    public function update(UpdateChecklistRequest $request, CheckList $checkList)
    {
        $data = $this->prepare($request->validated());
        $checkList->update($data);

        return redirect()->back();
    }

    /**
     * 表單資料轉成可寫入的欄位
     */
    private function prepare(array $data): array
    {
        $user = User::where('stu_id', $data['sid'])->where('status', true)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'sid' => "查無學號 {$data['sid']} 的啟用中人員。",
            ]);
        }

        $tz = config('app.timezone');

        return [
            'user_id' => $user->id,
            'checkin_time' => Carbon::parse($data['checkin_time'])->timezone($tz)->format('Y-m-d H:i'),
            'checkout_time' => Carbon::parse($data['checkout_time'])->timezone($tz)->format('Y-m-d H:i'),
            // 手動補登，兩邊都標成 1
            'checkin_operation' => 1,
            'checkout_operation' => 1,
            'checkin_location_id' => $data['checkin_location_id'] ?? null,
            'checkout_location_id' => $data['checkout_location_id'] ?? null,
            // 手動補登沒有真實來源 IP
            'checkin_ip' => '0.0.0.0',
            'checkout_ip' => '0.0.0.0',
        ];
    }

    public function destroy(CheckList $checkList)
    {
        $checkList->delete();

        return redirect()->back();
    }
}
