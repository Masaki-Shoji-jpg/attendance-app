<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{

    public function staffList()
    {
        
        return view('admin/staff-attendance-list');
    }


}
