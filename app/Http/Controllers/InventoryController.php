<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.index');
    }

    public function create(): View
    {
        return view('inventory.create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('inventory.index');
    }

    public function edit(string $inventory): View
    {
        return view('inventory.edit', compact('inventory'));
    }

    public function update(Request $request, string $inventory): RedirectResponse
    {
        return redirect()->route('inventory.index');
    }
}
