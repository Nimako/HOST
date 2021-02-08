<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        
        return view('home');
    }

    public function aboutUs()
    {
        return view('aboutUs');
    }

    public function privacyPolicy()
    {
        return view('privacyPolicy');
    }

    public function termsCondition()
    {
        return view('termsCondition');
    }
}
