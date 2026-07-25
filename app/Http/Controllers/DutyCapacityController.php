<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DutyCapacityController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Capacity');
    }
}