<?php
// app/Http/Controllers/Backend/OrderController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()   { return view('backend.orders.index'); }
    public function create()  { return view('backend.orders.create'); }
    public function store(Request $request) { /* validate & store */ return redirect()->route('backend.orders.index'); }
    public function show($id) { return view('backend.orders.show'); }
    public function edit($id) { return view('backend.orders.edit'); }
    public function update(Request $request, $id) { /* update */ return redirect()->route('backend.orders.index'); }
    public function destroy($id) { /* delete */ return redirect()->route('backend.orders.index'); }
}
