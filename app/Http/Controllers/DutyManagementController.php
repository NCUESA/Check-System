<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DutyManagementController extends Controller
{
    // 差勤管理首頁，只負責列出各子功能的入口
    public function index()
    {
        return Inertia::render('DutyManagement/Index');
    }
}
