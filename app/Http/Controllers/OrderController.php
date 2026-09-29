<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('orders.index');
    }

    public function create(): View
    {
        return view('orders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('orders.index');
    }

    public function show(string $order): View
    {
        return view('orders.show', compact('order'));
    }

    public function edit(string $order): View
    {
        return view('orders.edit', compact('order'));
    }

    public function update(Request $request, string $order): RedirectResponse
    {
        return redirect()->route('orders.index');
    }

    public function destroy(string $order): RedirectResponse
    {
        return redirect()->route('orders.index');
    }
}
