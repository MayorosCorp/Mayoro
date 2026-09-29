<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function generate(Request $request): RedirectResponse
    {
        return redirect()->route('reports.index');
    }
}
