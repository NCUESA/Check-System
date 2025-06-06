<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\IPController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render("Index");
});
Route::get('/duty',function() {
    return view('fillDuty',['js_name' => 'fillDuty']);
});

Route::middleware(['ipAuth'])->group(function () {
    Route::get('/checklist', function () {
        return view('checklist', ['js_name' => 'checklist']);
    });
    
    Route::get('/person', function () {
        return Inertia::render("Person");
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
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::post('/people', [PersonController::class, 'store'])->name('people.store');
    Route::put('/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::delete('/people/{person}', [PersonController::class, 'destroy'])->name('people.delete');
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





