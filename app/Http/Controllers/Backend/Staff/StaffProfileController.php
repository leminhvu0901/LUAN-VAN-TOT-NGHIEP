<?php

namespace App\Http\Controllers\Backend\Staff;

use Illuminate\Support\Facades\Auth;

// chỉ XEM thông
class StaffProfileController
{
    public function edit()
    {
        return view('backend.staff.reception.profile', ['user' => Auth::user()]);
    }
}
