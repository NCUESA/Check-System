<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\IPController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index', ['js_name' => 'index']);
});
Route::get('/duty',function() {
    return view('fillDuty',['js_name' => 'fillDuty']);
});

Route::middleware(['ipAuth'])->group(function () {
    Route::get('/checklist', function () {
        return view('checklist', ['js_name' => 'checklist']);
    });
    
    Route::get('/person', function () {
        return view('person', ['js_name' => 'person']);
    });

    Route::get('/ip', function () {
        return view('ip', ['js_name' => 'ip']);
    });
    Route::get('/cards', function () {
        return view('cards', ['js_name'=> 'cards']);
    });
});

// Cards
Route::group([], function () {
    Route::get('/show-cards', [CardController::class,'index']);
    Route::post('/add-card', [CardController::class, 'store']);
    Route::put('/update-card', [CardController::class, 'update']);
    Route::delete('/delete-card', [CardController::class, 'destroy']);
});

// Person
Route::group([],function () {
    Route::post('/add-user', [PersonController::class, 'addUser']);
    Route::post('/show-user', [PersonController::class, 'showUserFull']);
});

// CheckList
Route::group([],function(){
    Route::post('/check', [JobController::class, 'checkInOrOut']);
    Route::post('/show-list', [JobController::class, 'showList']);
    Route::post('/show-list-condition', [JobController::class, 'showListCondition']);
    Route::post('/update-list', [JobController::class, 'manualUpdateList']);
    Route::post('/gen-month-table',[JobController::class,'genMonthTable']);
});

// IP
Route::group([],function(){
    Route::post('/show-ip', [IPController::class, 'showIP']);
    Route::post('/add-ip', [IPController::class, 'addIP']);
    Route::post('/delete-ip',[IPController::class,'deleteIP']); 
});

Route::get('/refresh-csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});





