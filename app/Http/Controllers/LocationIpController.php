<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationIp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LocationIpController extends Controller
{
    // 地點 IP 設定：打卡時用來判斷這台機器在哪個地點
    public function index()
    {
        return Inertia::render('System/LocationIps', [
            'locations' => Location::orderBy('id', 'asc')->get(['id', 'name', 'status']),
            'ips' => LocationIp::with('location:id,name')
                ->orderBy('location_id', 'asc')
                ->orderBy('ip_address', 'asc')
                ->get()
                ->map(fn ($ip) => [
                    'id' => $ip->id,
                    'location_id' => $ip->location_id,
                    'location' => $ip->location?->name,
                    'ip_address' => $ip->ip_address,
                    'description' => $ip->description,
                    'status' => $ip->status,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        LocationIp::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, LocationIp $locationIp)
    {
        $validated = $request->validate($this->rules($locationIp->id), $this->messages());

        $locationIp->update($validated);

        return redirect()->back();
    }

    public function destroy(LocationIp $locationIp)
    {
        $locationIp->delete();

        return redirect()->back();
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'location_id' => 'required|integer|exists:locations,id',
            // 一個 IP 只能對應一個地點，否則打卡時無法判斷在哪
            'ip_address' => [
                'required',
                'string',
                'max:45',
                'ip',
                Rule::unique('location_ips', 'ip_address')->ignore($ignoreId),
            ],
            'description' => 'nullable|string|max:100',
            'status' => 'required|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'location_id.required' => '請選擇地點。',
            'location_id.exists' => '找不到這個地點。',
            'ip_address.required' => '請輸入 IP 位址。',
            'ip_address.ip' => 'IP 位址格式不正確。',
            'ip_address.unique' => '這個 IP 已經對應到其他地點了。',
            'description.max' => '說明最多 100 個字。',
        ];
    }
}
