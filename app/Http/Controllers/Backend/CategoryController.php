<?php
// app/Http/Controllers/Backend/CategoryController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()   { return view('backend.categorys.index'); }
    public function create()  { return view('backend.categorys.create'); }
    public function store(Request $request) { /* validate & store */ return redirect()->route('backend.categorys.index'); }
    public function show($id) { return view('backend.categorys.show'); }
    public function edit($id) { return view('backend.categorys.edit'); }
    public function update(Request $request, $id) { /* update */ return redirect()->route('backend.categorys.index'); }
    public function destroy($id) { /* delete */ return redirect()->route('backend.categorys.index'); }
}
