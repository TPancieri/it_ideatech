<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(): View
    {
        return view('painel.index');
    }
}
