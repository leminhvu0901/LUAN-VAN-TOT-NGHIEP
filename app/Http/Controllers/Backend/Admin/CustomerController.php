<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController
{
    //Hàm lấy danh sách khách hàng và hiển thị, lọc
    public function index(Request $request)
    {
        $query = User::where('role', 'customer'); // Khởi tạo câu truy vấn lấy danh sách tài khoản là Khách hàng (role = customer)

        // Lọc theo tìm kiếm (tên, email, sđt)
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
            $query->where('membership_level', $request->input('membership')); // Lọc theo hạng thành viên (new, silver, gold, diamond)
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

        $customers = $query->paginate(10)->appends($request->query()); // Phân trang kết quả (10 khách/trang) và giữ lại các tham số lọc trên URL

        // Các thống kê cho thẻ phía trên
        $totalCustomers = User::where('role', 'customer')->count(); // Đếm tổng số lượng khách hàng trong DB

        $membershipCounts = User::where('role', 'customer')
            ->select('membership_level', DB::raw('count(*) as total'))
            ->groupBy('membership_level')
            ->pluck('total', 'membership_level')->toArray(); // Đếm số lượng khách hàng theo từng hạng thành viên

        $diamondCount = $membershipCounts['diamond'] ?? 0;
        $goldCount = $membershipCounts['gold'] ?? 0;
        $silverCount = $membershipCounts['silver'] ?? 0;
        $newCount = $membershipCounts['new'] ?? 0;

        $inactiveCount = User::where('role', 'customer')->where('is_active', 0)->count(); // Đếm số lượng tài khoản khách hàng đang bị khóa

        return view('backend.admin.customers.index', compact( // Load giao diện trang quản lý khách hàng
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


    //Hàm xử lý lưu thông tin khách hàng mới vào Database.
    public function store(Request $request)
    {
        // 1. Name: trim khoảng trắng đầu/cuối và nhiều khoảng trắng liên tiếp
        if ($request->has('name')) {
            $request->merge(['name' => preg_replace('/\s+/', ' ', trim($request->name))]);
        }
        // 2. Email: chuyển về chữ thường
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        // 6. Address: trim khoảng trắng
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
            $file->move(public_path('images/avatars'), $filename);
            // avatar_url()/avatar_path() tự ghép tiền tố 'images/avatars/' - chỉ lưu tên file trần.
            $data['avatar'] = $filename;
        }

        User::create($data); // Lưu thông tin khách hàng mới vào Database thông qua Eloquent Model

        return redirect()->route('admin.customers.index')->with('success', 'Thêm khách hàng thành công!'); // Chuyển hướng về trang danh sách kèm thông báo
    }

    //Hàm hiển thị trang hồ sơ chi tiết của một khách hàng cụ thể.
    public function show($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id); // Tìm tài khoản khách hàng theo ID, ném lỗi 404 nếu không tồn tại

        // Lấy số lượng đơn hàng (giả sử model User có qh orders, nếu chưa có thì dùng Query Builder)
        $totalOrders = DB::table('orders')->where('user_id', $id)->count(); // Đếm tổng số đơn hàng đã đặt
        $totalSpent = DB::table('orders')->where('user_id', $id)->where('status', '!=', 'cancelled')->sum('total_amount'); // Tính tổng số tiền đã mua (trừ đơn hủy)

        // Lấy 5 đơn hàng gần nhất
        $recentOrders = DB::table('orders') // Lấy chi tiết 5 đơn hàng gần đây nhất để hiển thị ở trang cá nhân khách
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('backend.admin.customers.show', compact('customer', 'totalOrders', 'totalSpent', 'recentOrders')); // Load giao diện hồ sơ khách hàng
    }

    //Hàm bật/tắt (Khóa hoặc Mở khóa) trạng thái hoạt động của tài khoản khách hàng.
    public function toggleStatus(Request $request, $id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id); // Tìm tài khoản khách hàng theo ID
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

    //xoa hang loat khách hang dược chọn
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return redirect()->route('admin.customers.index')->with('error', 'Không có khách hàng nào được chọn!');
        }

        // Xóa cứng (Hard Delete)
        $deletedCount = 0;
        $failedCount = 0;

        $customers = User::whereIn('id', $ids)->where('role', 'customer')->get();

        foreach ($customers as $customer) {
            try {
                $customer->delete();
                $deletedCount++;
            } catch (QueryException $e) {
                // Lỗi rảng buộc khóa ngoại (ví dụ có đơn hàng)
                if ($e->getCode() == "23000") {
                    // Khóa tài khoản thay vì xóa
                    $customer->is_active = 0;
                    $customer->save();
                    $failedCount++;
                } else {
                    throw $e;
                }
            }
        }

        if ($failedCount > 0) {
            return redirect()->route('admin.customers.index')->with('success', "Đã xóa {$deletedCount} KH. Có {$failedCount} KH có lịch sử (đơn hàng/đánh giá) nên được chuyển sang KHÓA.");
        }

        return redirect()->route('admin.customers.index')->with('success', 'Đã xóa thành công các khách hàng đã chọn!');
    }

    /**
     * Hàm hiển thị giao diện form chỉnh sửa thông tin cho một khách hàng cụ thể.
     */
    public function edit($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id); // Tìm tài khoản khách hàng theo ID cần sửa đổi
        return view('backend.admin.customers.edit', compact('customer')); // Load giao diện form sửa đổi thông tin khách hàng
    }

    // Hàm xử lý cập nhật các thông tin khách hàng
    public function update(Request $request, $id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        // 1. Name: trim khoảng trắng
        if ($request->has('name')) {
            $request->merge(['name' => preg_replace('/\s+/', ' ', trim($request->name))]);
        }
        // 2. Email: chuyển về chữ thường
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        // 6. Address: trim khoảng trắng
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
            $data['password'] = $request->password; // Will be hashed automatically by User model casts
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if it's not a remote URL and not empty
            if ($customer->avatar && !str_starts_with($customer->avatar, 'http')) {
                $oldPath = avatar_path($customer->avatar);
                if ($oldPath && file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/avatars'), $filename);
            $data['avatar'] = $filename;
        }

        $customer->update($data); // Thực hiện cập nhật các thay đổi mới vào CSDL

        return redirect()->route('admin.customers.index')->with('success', 'Cập nhật khách hàng thành công!'); // Chuyển hướng về trang quản lý khách hàng
    }

    // Hàm xử lý xóa một khách hàng cụ thể ra khỏi hệ thống
    public function destroy($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id); // Tìm tài khoản khách hàng theo ID cần xóa
        try {
            $customer->delete(); // Thực hiện xóa cứng (Hard Delete) bản ghi khách hàng khỏi Database
            return redirect()->back()->with('success', 'Xóa khách hàng thành công!');
        } catch (QueryException $e) {
            if ($e->getCode() == "23000") { // Xử lý lỗi ràng buộc khóa ngoại (Foreign Key Constraint) khi khách hàng có đơn hàng/đánh giá
                $customer->is_active = 0;
                $customer->save(); // Khóa tài khoản khách hàng để tránh làm hỏng dữ liệu báo cáo đơn hàng
                return redirect()->back()->with('warning', 'Khách hàng có lịch sử giao dịch nên tài khoản chỉ bị khóa!');
            }
            throw $e;
        }
    }
}
