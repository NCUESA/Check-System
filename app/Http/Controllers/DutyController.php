<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Inertia\Inertia;
class DutyController extends Controller
{
    //

    public function index()
    {
        //
        return Inertia::render('Duty/Index');
    }
    public function phone()
    {
        //
        return Inertia::render('Duty/Phone');
    }
    public function calendar()
    {
        //
        return Inertia::render('Duty/Calendar');
    }
}
