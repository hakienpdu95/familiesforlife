<?php
// app/Http/Controllers/Backend/SettingController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()   { return view('backend.settings.index'); }
    public function create()  { return view('backend.settings.create'); }
    public function store(Request $request) { /* validate & store */ return redirect()->route('backend.settings.index'); }
    public function show($id) { return view('backend.settings.show'); }
    public function edit($id) { return view('backend.settings.edit'); }
    public function update(Request $request, $id) { /* update */ return redirect()->route('backend.settings.index'); }
    public function destroy($id) { /* delete */ return redirect()->route('backend.settings.index'); }
}
