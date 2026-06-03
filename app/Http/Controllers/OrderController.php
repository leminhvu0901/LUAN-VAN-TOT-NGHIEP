<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController
{
    public function index()
    {
        return view('pages.orders');
    }
}
