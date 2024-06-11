<?php

namespace App\Http\Controllers;

class AntragController extends Controller
{
    public function index(int $site){
        return view("antrag.$site");
    }
}
