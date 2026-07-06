<?php
// app/Http/Controllers/Backend/CustomerController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()   { return view('backend.customers.index'); }
    public function create()  { return view('backend.customers.create'); }
    public function store(Request $request) { /* validate & store */ return redirect()->route('backend.customers.index'); }
    public function show($id) { return view('backend.customers.show'); }
    public function edit($id) { return view('backend.customers.edit'); }
    public function update(Request $request, $id) { /* update */ return redirect()->route('backend.customers.index'); }
    public function destroy($id) { /* delete */ return redirect()->route('backend.customers.index'); }
}
