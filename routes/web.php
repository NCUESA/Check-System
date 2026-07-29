<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DutyController;
use App\Http\Controllers\DutyManagementController;
use App\Http\Controllers\DutyPeriodController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\IPController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DutyCapacityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render("Index", [
        'checklist' => Session::get('checklist'), 
        'time' => Session::get('time'), 
        'status' => Session::get('status')
    ]);
})->name('home');

Route::put('/check', CheckController::class)->name('check');

// Keycloak SSO
Route::group([], function () {
    Route::get('/login/keycloak/redirect', [AuthController::class, 'redirect'])->name('login.keycloak.redirect');
    Route::get('/login/keycloak/callback', [AuthController::class, 'callback'])->name('login.keycloak.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

// Duty
Route::middleware('auth')->group(function () {
    Route::get('/duty', [DutyController::class, 'index'])->name('duty.index');
    Route::get('/duty/phone', [DutyController::class, 'phone'])->name('duty.phone');
    Route::get('/duty/calendar', [DutyController::class, 'calendar'])->name('duty.calendar');
    Route::post('/duty/check', [DutyController::class, 'checkInOrOut'])->name('duty.check');
    // 一次認領／退掉多個名額（月曆可以多選日期），所以吃 slot_ids 陣列而非單一 id
    Route::post('/duty/calendar/fill', [DutyController::class, 'fill'])->name('duty.fill');
    Route::post('/duty/calendar/cancel', [DutyController::class, 'cancel'])->name('duty.cancel');
});

// Cards
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/cards', [CardController::class,'index'])->name('cards.index');
    Route::post('/cards', [CardController::class, 'store'])->name('cards.store');
    Route::put('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
});

// Person
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::post('/people', [PersonController::class, 'store'])->name('people.store');
    Route::put('/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::delete('/people/{person}', [PersonController::class, 'destroy'])->name('people.delete');
});

// 差勤管理（原「打卡全表管理」）
// 首頁只是入口，各子功能各自一頁
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/duty-management', [DutyManagementController::class, 'index'])->name('duty-management.index');
    Route::get('/duty-management/manual', [ChecklistController::class, 'manual'])->name('duty-management.manual');
    Route::get('/duty-management/checklist', [ChecklistController::class, 'index'])->name('duty-management.checklist');
    // 值勤時間設定
    Route::get('/duty-management/periods', [DutyPeriodController::class, 'index'])->name('duty-management.periods');
    Route::post('/duty-management/periods', [DutyPeriodController::class, 'store'])->name('duty-management.periods.store');
    Route::put('/duty-management/periods/{dutyPeriod}', [DutyPeriodController::class, 'update'])->name('duty-management.periods.update');
    Route::delete('/duty-management/periods/{dutyPeriod}', [DutyPeriodController::class, 'destroy'])->name('duty-management.periods.destroy');

    // 臨時增額
    Route::get('/duty-management/capacity', [DutyCapacityController::class, 'index'])->name('duty-management.capacity');

    // 管理員把某個名額指派給某個人
    Route::post('/duty-management/assign', [DutyController::class, 'assign'])->name('duty.assign');

    // 簽到紀錄的寫入端點，手動簽到退與簽到表總覽共用
    Route::post('/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
    Route::put('/checklist/{checkList}', [ChecklistController::class, 'update'])->name('checklist.update');
    Route::delete('/checklist/{checkList}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
});

// System
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/system', [SystemController::class, 'index'])->name('system.index');

    // Admin List
    Route::get('/system/admins', [AdminController::class, 'index'])->name('system.admins.index');
    Route::put('/system/admins/{user}', [AdminController::class, 'update'])->name('system.admins.update');

    // Location and Capacity management
    Route::get('/system/locations', [LocationController::class, 'index'])->name('system.locations.index');
    Route::post('/system/locations', [LocationController::class, 'store'])->name('system.locations.store');
    Route::put('/system/locations/{location}', [LocationController::class, 'update'])->name('system.locations.update');
    Route::put('/system/locations/{location}/ignore', [LocationController::class, 'ignore'])->name('system.locations.ignore');
});

// IP
// Abandoned due to the fact that the IP authentication is no longer needed, and the IP management is not a priority for now.
// Edit by Ender, at 2026/07/23
// Route::group([],function(){
//     Route::get('/ip', [IPController::class, 'index'])->name('ip.index');
//     Route::post('/ip', [IPController::class, 'store'])->name('ip.store');
//     Route::put('/ip/{authIp}', [IPController::class, 'update'])->name('ip.update');
//     Route::delete('/ip/{authIp}',[IPController::class,'destroy'])->name('ip.destroy');
// });

// 公告的讀取（announcements.index）開放所有人，寫入僅限管理員
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});


Route::get('/refresh-csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});