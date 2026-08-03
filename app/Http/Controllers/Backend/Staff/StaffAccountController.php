<?php

namespace App\Http\Controllers\Backend\Staff;

use App\Models\Order;
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

        // Lọc theo loại nhân viên (whitelist cứng, tránh nhận giá trị lạ từ query string)
        if ($request->filled('staff_type') && in_array($request->input('staff_type'), ['receptionist', 'delivery'], true)) {
            $query->where('staff_type', $request->input('staff_type'));
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
                'html' => view('backend.admin.staff-accounts.partials.table', compact('staffs'))->render()
            ]);
        }

        $totalStaff = User::where('role', 'staff')->count();
        $activeStaff = User::where('role', 'staff')->where('is_active', 1)->count();
        $inactiveStaff = User::where('role', 'staff')->where('is_active', 0)->count();

        return view('backend.admin.staff-accounts.index', compact('staffs', 'totalStaff', 'activeStaff', 'inactiveStaff'));
    }

    public function create()
    {
        return view('backend.admin.staff-accounts.create');
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
            // trim(null) trả về '' (không phải null) -> nếu để trống, phải ép lại thành null tường
            // minh, nếu không "nullable" sẽ bị vô hiệu hóa (chuỗi rỗng khác NULL với unique index,
            // gây lỗi trùng khóa nếu có nhân viên khác cũng đang để trống SĐT).
            $trimmedPhone = trim((string) $request->phone);
            $request->merge(['phone' => $trimmedPhone === '' ? null : $trimmedPhone]);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i'],
            'password' => 'required|string|min:8|confirmed',
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users,phone'],
            'is_active' => 'required|boolean',
            // Whitelist cứng — chỉ 2 giá trị hợp lệ, route này đã nằm trong group admin.* nên chỉ admin gọi tới được.
            'staff_type' => ['required', 'in:receptionist,delivery'],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'email.regex' => 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'phone.regex' => 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0).',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'staff_type.required' => 'Vui lòng chọn loại nhân viên.',
            'staff_type.in' => 'Loại nhân viên không hợp lệ.',
            'avatar.image' => 'Ảnh đại diện phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện phải có định dạng JPG, PNG, WEBP hoặc GIF.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2MB.',
        ]);

        $avatarFilename = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/avatars'), $filename);
            $avatarFilename = $filename;
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'staff', // Bắt buộc lưu vai trò là staff
            'staff_type' => $validated['staff_type'],
            'is_active' => $request->is_active,
            'avatar' => $avatarFilename,
        ]);

        return redirect()->route('admin.staff_accounts.index')->with('success', 'Thêm tài khoản nhân viên thành công!');
    }

    public function edit($id)
    {
        $staff = User::where('role', 'staff')->findOrFail($id);

        return view('backend.admin.staff-accounts.edit', compact('staff'));
    }

    public function update(Request $request, $id)
    {
        $staff = User::where('role', 'staff')->findOrFail($id);

        // Chuẩn hóa dữ liệu
        if ($request->has('name')) {
            $request->merge(['name' => preg_replace('/\s+/', ' ', trim($request->name))]);
        }
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        if ($request->has('phone')) {
            // trim(null) trả về '' (không phải null) -> nếu để trống, phải ép lại thành null tường
            // minh, nếu không "nullable" sẽ bị vô hiệu hóa (chuỗi rỗng khác NULL với unique index,
            // gây lỗi trùng khóa nếu có nhân viên khác cũng đang để trống SĐT).
            $trimmedPhone = trim((string) $request->phone);
            $request->merge(['phone' => $trimmedPhone === '' ? null : $trimmedPhone]);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $staff->id, 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i'],
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users,phone,' . $staff->id],
            'is_active' => 'required|boolean',
            'staff_type' => ['required', 'in:receptionist,delivery'],
            // Mật khẩu tùy chọn — để trống nếu không muốn đổi.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'email.regex' => 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).',
            'phone.regex' => 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0).',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'staff_type.required' => 'Vui lòng chọn loại nhân viên.',
            'staff_type.in' => 'Loại nhân viên không hợp lệ.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'avatar.image' => 'Ảnh đại diện phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện phải có định dạng JPG, PNG, WEBP hoặc GIF.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2MB.',
        ]);

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->phone = $validated['phone'] ?? null;
        $staff->staff_type = $validated['staff_type'];
        $staff->is_active = $validated['is_active'];
        if ($staff->is_active) {
            $staff->lock_reason = null;
        }
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }
        if ($request->hasFile('avatar')) {
            if ($staff->avatar && !str_starts_with($staff->avatar, 'http')) {
                $oldPath = avatar_path($staff->avatar);
                if ($oldPath && file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/avatars'), $filename);
            $staff->avatar = $filename;
        }
        $staff->save();

        return redirect()->route('admin.staff_accounts.index')->with('success', 'Cập nhật tài khoản nhân viên thành công!');
    }

    public function destroy($id)
    {
        $staff = User::where('role', 'staff')->find($id);
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài khoản nhân viên.'], 404);
        }

        // Không cho xóa nếu nhân viên đã có lịch sử hoạt động thật (tạo đơn tại quầy/được phân công
        // giao hàng/đối soát COD) — xóa sẽ khiến các cột tham chiếu bị SET NULL, mất dấu vết ai đã
        // làm gì trên các đơn đó. Trường hợp này chỉ nên khóa tài khoản (đã có sẵn nút Khóa), không xóa.
        $hasHistory = Order::withTrashed()
            ->where('user_id', $staff->id)
            ->orWhere('created_by', $staff->id)
            ->orWhere('delivery_staff_id', $staff->id)
            ->orWhere('assigned_by', $staff->id)
            ->orWhere('cod_settled_by', $staff->id)
            ->exists();

        if ($hasHistory) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa: nhân viên này đã có lịch sử hoạt động (đơn hàng/phân công/đối soát). Vui lòng khóa tài khoản thay vì xóa.',
            ], 422);
        }

        $staff->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa tài khoản nhân viên.']);
    }

    /**
     * Cập nhật quyền hạn/phân loại vai trò của nhân viên (Lễ tân / Shipper).
     * 
     * Phản hồi trả về:
     * - Trả dữ liệu JSON về cho khối lắng nghe thay đổi staff-type-select 
     *   trong file JS: [public/js/backend/admin/staff-accounts/index.js] để hiển thị thông báo thành công hoặc khôi phục giá trị cũ nếu lỗi.
     */
    public function updateType(Request $request, $id)
    {
        // Chỉ tác động tài khoản role=staff — không đụng customer/admin dù ID có trùng.
        $staff = User::where('role', 'staff')->find($id);
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài khoản nhân viên.'], 404);
        }

        $validated = $request->validate([
            // Whitelist cứng — chỉ 2 giá trị hợp lệ, không tin giá trị gửi lên nếu nằm ngoài danh sách này.
            'staff_type' => ['required', 'in:receptionist,delivery'],
        ], [
            'staff_type.required' => 'Vui lòng chọn loại nhân viên.',
            'staff_type.in' => 'Loại nhân viên không hợp lệ.',
        ]);

        $staff->staff_type = $validated['staff_type'];
        $staff->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật loại nhân viên thành công!',
            'staff_type' => $staff->staff_type,
        ]);
    }

    /**
     * Khóa hoặc kích hoạt tài khoản nhân viên.
     * 
     * Phản hồi trả về:
     * - Trả dữ liệu JSON về cho khối lắng nghe toggle-status 
     *   trong file JS: [public/js/backend/admin/staff-accounts/index.js] để cập nhật giao diện hiển thị trạng thái và thông báo kết quả.
     */
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
