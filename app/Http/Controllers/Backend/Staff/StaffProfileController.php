<?php

namespace App\Http\Controllers\Backend\Staff;

use Illuminate\Support\Facades\Auth;

// Dùng chung cho cả lễ tân và vận chuyển — chỉ XEM thông
// Nhân viên không có quyền tự sửa thông tin tài khoản; chỉ admin được sửa (qua staff-accounts).
class StaffProfileController
{
    public function edit()
    {
        return view('backend.staff.reception.profile', ['user' => Auth::user()]);
    }
}
