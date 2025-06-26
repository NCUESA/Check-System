<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\IPController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render("Index", [
        'checklist' => Session::get('checklist'), 
        'time' => Session::get('time'), 
        'status' => Session::get('status')
    ]);
})->name('home');

Route::get('/duty',function() {
    return view('fillDuty',['js_name' => 'fillDuty']);
});

/*
Route::middleware(['ipAuth'])->group(function () {
    Route::get('/checklist', function () {
        return Inertia::render("Checklist");
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
    Route::put('/check', CheckController::class)->name('check');
});
*/

Route::put('/check', CheckController::class)->name('check');

Route::middleware(['ipAuth'])->group(function () {
    // Cards
    Route::group([], function () {
        Route::get('/cards', [CardController::class,'index'])->name('cards.index');
        Route::post('/cards', [CardController::class, 'store'])->name('cards.store');
        Route::put('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
        Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
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
        Route::get('/checklist', [ChecklistController::class, 'index'])->name('checklist.index');
        Route::post('/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
        Route::put('/checklist/{checkList}', [ChecklistController::class, 'update'])->name('checklist.update');
        Route::delete('/checklist/{checkList}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
    });

    // IP
    Route::group([],function(){
        Route::get('/ip', [IPController::class, 'index'])->name('ip.index');
        Route::post('/ip', [IPController::class, 'store'])->name('ip.store');
        Route::put('/ip/{authIp}', [IPController::class, 'update'])->name('ip.update');
        Route::delete('/ip/{authIp}',[IPController::class,'destroy'])->name('ip.destroy');
    });
});

Route::get('/refresh-csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});