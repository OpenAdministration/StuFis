<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AntragController extends Controller
{
    public function index(){
        return view('antrag.index');
    }
}
