<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class SystemController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Index');
    }
}
