<?php

namespace App\Http\Controllers\Backend\Staff;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffAccountController
{
    public function index(Request $request)
    {
        $query = User::where('role', 'staff');

        // Lọc theo tìm kiếm (tên, email, sđt)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status') === 'active' ? 1 : 0;
            $query->where('is_active', $status);
        }

        // Sắp xếp
        if ($request->filled('sort')) {
            switch ($request->input('sort')) {
                case 'newest':
                    $query->latest();
                    break;
                case 'oldest':
                    $query->oldest();
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $staffs = $query->paginate(10)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('backend.staff-accounts.partials.table', compact('staffs'))->render()
            ]);
        }

        $totalStaff = User::where('role', 'staff')->count();
        $activeStaff = User::where('role', 'staff')->where('is_active', 1)->count();
        $inactiveStaff = User::where('role', 'staff')->where('is_active', 0)->count();

        return view('backend.staff-accounts.index', compact('staffs', 'totalStaff', 'activeStaff', 'inactiveStaff'));
    }

    public function create()
    {
        return view('backend.staff-accounts.create');
    }

    public function store(Request $request)
    {
        // Chuẩn hóa dữ liệu
        if ($request->has('name')) {
            $request->merge(['name' => preg_replace('/\s+/', ' ', trim($request->name))]);
        }
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        if ($request->has('phone')) {
            $request->merge(['phone' => trim($request->phone)]);
        }

        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i'],
            'password' => 'required|string|min:8|confirmed',
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users,phone'],
            'is_active' => 'required|boolean',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'email.regex' => 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'phone.regex' => 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0).',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'staff', // Bắt buộc lưu vai trò là staff
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.staff_accounts.index')->with('success', 'Thêm tài khoản nhân viên thành công!');
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $staff = User::where('role', 'staff')->findOrFail($id);
            $staff->is_active = $request->input('is_active');
            
            if ($staff->is_active == 0) {
                $staff->lock_reason = $request->input('lock_reason');
            } else {
                $staff->lock_reason = null;
            }
            
            $staff->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái tài khoản thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
