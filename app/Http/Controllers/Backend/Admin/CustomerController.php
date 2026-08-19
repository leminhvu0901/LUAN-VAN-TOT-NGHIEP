<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController
{
    // Hàm lấy danh sách khách hàng và hiển thị, lọc
    public function index(Request $request)
    {
        $query = User::where('role', 'customer'); // Khởi tạo câu truy vấn lấy danh sách tài khoản là Khách hàng, role = customer

        // Lọc theo tìm kiếm, tên, email, sđt
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) { // Lọc tìm kiếm theo Tên, Email hoặc Số điện thoại
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status') === 'active' ? 1 : 0;
            $query->where('is_active', $status); // Lọc theo trạng thái Hoạt động hoặc Bị khóa
        }

        // Lọc theo hạng thành viên
        if ($request->filled('membership') && $request->input('membership') !== 'all') {
            $query->where('membership_level', $request->input('membership')); // Lọc theo hạng thành viên, new, silver, gold, diamond
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
                case 'points_desc':
                    $query->orderByDesc('points');
                    break;
                case 'points_asc':
                    $query->orderBy('points');
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $customers = $query->paginate(10)->appends($request->query()); // Phân trang kết quả, 10 khách/trang và giữ lại các tham số lọc trên URL

        // Các thống kê cho thẻ phía trên
        $totalCustomers = User::where('role', 'customer')->count(); // Đếm tổng số lượng tài khoản trong danh sách

        $membershipCounts = User::where('role', 'customer')
            ->select('membership_level', DB::raw('count(*) as total'))
            ->groupBy('membership_level')
            ->pluck('total', 'membership_level')->toArray(); // Đếm số lượng khách hàng theo từng hạng thành viên

        $diamondCount = $membershipCounts['diamond'] ?? 0;
        $goldCount = $membershipCounts['gold'] ?? 0;
        $silverCount = $membershipCounts['silver'] ?? 0;

        // Tài khoản được tạo trong vòng 1 tháng gần nhất mới được coi là "Mới đăng ký"
        $newCount = User::where('role', 'customer')
            ->where('created_at', '>=', now()->subMonths(1))
            ->count();

        $inactiveCount = User::where('role', 'customer')->where('is_active', 0)->count(); // Đếm số lượng tài khoản đang bị khóa

        return view('backend.admin.customers.index', compact(
            'customers',
            'totalCustomers',
            'diamondCount',
            'goldCount',
            'silverCount',
            'newCount',
            'inactiveCount'
        ));
    }

    // thêm tài khoẳn khách hàng
    public function create()
    {
        return view('backend.admin.customers.create'); // Load giao diện form thêm mới khách hàng
    }

    // Hàm xử lý lưu thông tin khách hàng mới vào Database.
    public function store(Request $request)
    {
        // Name: trim khoảng trắng đầu/cuối và nhiều khoảng
        if ($request->has('name')) {
            $request->merge(['name' => preg_replace('/\s+/', ' ', trim($request->name))]);
        }
        // Email: chuyển về chữ thường
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        // Address: trim khoảng trắng
        if ($request->has('address')) {
            $request->merge(['address' => trim($request->address)]);
        }

        $request->validate([ // Xác thực tính hợp lệ của dữ liệu form gửi lên
            'name' => 'required|string|min:2|max:100',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i'],
            'password' => 'required|string|min:8|confirmed',
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users,phone'],
            'address' => 'nullable|string|max:255',
            'membership_level' => 'required|in:new,silver,gold,diamond',
            'points' => 'required|integer|min:0|max:900000', // Admin có quyền cập nhật điểm
            'is_active' => 'required|boolean',
        ], [
            'email.unique' => 'Email này đã được sử dụng.',
            'email.regex' => 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'phone.regex' => 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0).',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'points.max' => 'Điểm tích lũy không được vượt quá 900.000.',
        ]);

        $data = $request->except(['avatar', 'password_confirmation']);
        $data['role'] = 'customer';

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(upload_dir('avatars'), $filename);
            $data['avatar'] = upload_rel('avatars', $filename);
        }

        User::create($data); // Lưu thông tin khách hàng mới vào Database thông qua Eloquent Model

        return redirect()->route('admin.customers.index')->with('success', 'Thêm khách hàng thành công!'); // Chuyển hướng về trang danh sách kèm thông báo
    }

    // Hàm hiển thị trang hồ sơ chi tiết của một khách hàng cụ thể.
    public function show($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id); // Tìm tài khoản theo ID, ném lỗi 404 nếu không tồn tại

        // Lấy số lượng đơn hàng qua Model Order (tự động loại trừ các đơn đã xóa mềm)
        $totalOrders = Order::where('user_id', $id)->count();
        $totalSpent = Order::where('user_id', $id)->where('status', 'completed')->where('payment_status', 'paid')->sum('final_amount');

        // Lấy 5 đơn hàng gần nhất (không lấy đơn đã xóa)
        $recentOrders = Order::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('backend.admin.customers.show', compact('customer', 'totalOrders', 'totalSpent', 'recentOrders')); // Load giao diện hồ sơ khách hàng
    }

    // Hàm bật/tắt, Khóa hoặc Mở khóa trạng thái hoạt động
    public function toggleStatus(Request $request, $id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        // Không cho phép Admin tự khóa tài khoản của chính mình
        if ((int)$customer->id === (int)auth()->id()) {
            return redirect()->back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình!');
        }

        $customer->is_active = $request->input('is_active');

        // Cập nhật lý do khóa tài khoản
        if ($customer->is_active == 0) {
            $customer->lock_reason = $request->input('lock_reason');
        } else {
            $customer->lock_reason = null; // Reset lý do khi mở khóa
        }

        $customer->save(); // Lưu thay đổi vào CSDL

        return redirect()->route('admin.customers.index')->with('success', 'Cập nhật trạng thái thành công!');
    }

    // xoa hang loat khách hang dược chọn
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return redirect()->route('admin.customers.index')->with('error', 'Không có khách hàng nào được chọn!');
        }

        // Không cho phép Admin xóa chính mình trong xóa hàng loạt
        $currentAdminId = auth()->id();
        if (in_array($currentAdminId, $ids)) {
            return redirect()->route('admin.customers.index')->with('error', 'Bạn không thể xóa tài khoản của chính mình!');
        }

        $deletedCount = 0;
        $hasOrdersCount = 0;

        $customers = User::whereIn('id', $ids)->where('role', 'customer')->get();

        foreach ($customers as $customer) {
            if ((int)$customer->id === (int)$currentAdminId) {
                continue;
            }

            // Kiểm tra xem khách hàng có đơn hàng hay không (không tính đơn đã xóa mềm)
            $orderCount = Order::where('user_id', $customer->id)->count();
            if ($orderCount > 0) {
                $hasOrdersCount++;
                continue;
            }

            try {
                $customer->delete();
                $deletedCount++;
            } catch (\Throwable $e) {
                $hasOrdersCount++;
            }
        }

        if ($deletedCount === 0 && $hasOrdersCount > 0) {
            return redirect()->route('admin.customers.index')->with('error', "Không thể xóa {$hasOrdersCount} tài khoản đã chọn vì đều đã có lịch sử đơn hàng. Vui lòng chuyển sang Khóa tài khoản!");
        }

        if ($hasOrdersCount > 0) {
            return redirect()->route('admin.customers.index')->with('warning', "Đã xóa {$deletedCount} tài khoản chưa có đơn hàng. Có {$hasOrdersCount} tài khoản đã có lịch sử đơn hàng nên không thể xóa (vui lòng Khóa nếu cần).");
        }

        return redirect()->route('admin.customers.index')->with('success', "Đã xóa thành công {$deletedCount} tài khoản!");
    }

    //Hàm hiển thị giao diện form chỉnh sửa thông tin cho một tài khoản cụ thể.
    public function edit($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id); // Tìm tài khoản theo ID cần sửa đổi
        return view('backend.admin.customers.edit', compact('customer')); // Load giao diện form sửa đổi thông tin tài khoản
    }

    // Hàm xử lý cập nhật các thông tin tài khoản
    public function update(Request $request, $id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        // Không cho phép Admin tự khóa tài khoản của chính mình
        if ((int)$customer->id === (int)auth()->id() && (int)$request->input('is_active') === 0) {
            return redirect()->back()->withInput()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình!');
        }

        // Name: trim khoảng trắng
        if ($request->has('name')) {
            $request->merge(['name' => preg_replace('/\s+/', ' ', trim($request->name))]);
        }
        // Email: chuyển về chữ thường
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        // Address: trim khoảng trắng
        if ($request->has('address')) {
            $request->merge(['address' => trim($request->address)]);
        }

        $request->validate([ // Xác thực tính hợp lệ của dữ liệu chỉnh sửa gửi lên
            'name' => 'required|string|min:2|max:100',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id, 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i'],
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users,phone,' . $id],
            'address' => 'nullable|string|max:255',
            'membership_level' => 'required|in:new,silver,gold,diamond',
            'points' => 'required|integer|min:0|max:900000', // Admin có quyền cập nhật điểm
            'is_active' => 'required|boolean',
        ], [
            'email.unique' => 'Email này đã được sử dụng.',
            'email.regex' => 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'phone.regex' => 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0).',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'points.max' => 'Điểm tích lũy không được vượt quá 900.000.',
        ]);

        $data = $request->except(['password', 'password_confirmation', 'avatar']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        if ($request->hasFile('avatar')) {
            if ($customer->avatar && !str_starts_with($customer->avatar, 'http')) {
                $oldPath = avatar_path($customer->avatar);
                if ($oldPath && file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(upload_dir('avatars'), $filename);
            $data['avatar'] = upload_rel('avatars', $filename);
        }

        $customer->update($data);

        return redirect()->route('admin.customers.index')->with('success', 'Cập nhật khách hàng thành công!');
    }

    // Hàm xử lý xóa một tài khoản cụ thể ra khỏi hệ thống
    public function destroy($id)
    {
        $user = User::where('role', 'customer')->findOrFail($id);

        // Không cho phép Admin xóa chính mình
        if ((int)$user->id === (int)auth()->id()) {
            return redirect()->back()->with('error', 'Bạn không thể xóa tài khoản của chính mình!');
        }

        // Kiểm tra xem khách hàng có đơn hàng hay không (không tính đơn đã xóa mềm)
        $orderCount = Order::where('user_id', $user->id)->count();
        if ($orderCount > 0) {
            return redirect()->back()->with('error', "Khách hàng \"{$user->name}\" đã có {$orderCount} đơn hàng trong hệ thống. Không thể xóa để bảo toàn dữ liệu doanh thu, vui lòng chuyển sang Khóa tài khoản!");
        }

        try {
            $user->delete();
            return redirect()->back()->with('success', 'Xóa tài khoản thành công!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Không thể xóa tài khoản này do có dữ liệu liên quan trong hệ thống. Vui lòng chuyển sang Khóa tài khoản!');
        }
    }
}
