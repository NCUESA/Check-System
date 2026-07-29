<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DutyCapacityController extends Controller
{
    // 臨時增額：對特定日期加開名額，不受值勤期間限制
    // 屬於差勤管理，不是系統設定
    public function index()
    {
        return Inertia::render('DutyManagement/Capacity');
    }
}