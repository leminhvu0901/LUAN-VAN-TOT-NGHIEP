<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'customer');

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

        // Lọc theo hạng thành viên
        if ($request->filled('membership') && $request->input('membership') !== 'all') {
            $query->where('membership_level', $request->input('membership'));
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

        if ($request->get('fetch_all_ids')) {
            return response()->json(['ids' => (clone $query)->pluck('id')]);
        }

        $customers = $query->paginate(10)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('backend.customers.partials.table', compact('customers'))->render()
            ]);
        }

        // Các thống kê cho thẻ phía trên
        $totalCustomers = User::where('role', 'customer')->count();
        
        $membershipCounts = User::where('role', 'customer')
            ->select('membership_level', DB::raw('count(*) as total'))
            ->groupBy('membership_level')
            ->pluck('total', 'membership_level')->toArray();

        $diamondCount = $membershipCounts['diamond'] ?? 0;
        $goldCount = $membershipCounts['gold'] ?? 0;
        $silverCount = $membershipCounts['silver'] ?? 0;
        $newCount = $membershipCounts['new'] ?? 0;
        
        $inactiveCount = User::where('role', 'customer')->where('is_active', 0)->count();

        return view('backend.customers.index', compact(
            'customers', 
            'totalCustomers', 
            'diamondCount', 
            'goldCount', 
            'silverCount', 
            'newCount', 
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
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

        $request->validate([
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
            $data['avatar'] = $filename;
        }

        User::create($data);

        return redirect()->route('admin.customers.index')->with('success', 'Thêm khách hàng thành công!');
    }

    /**
     * Hiển thị chi tiết khách hàng.
     */
    public function show($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        
        // Lấy số lượng đơn hàng (giả sử model User có qh orders, nếu chưa có thì dùng Query Builder)
        $totalOrders = DB::table('orders')->where('user_id', $id)->count();
        $totalSpent = DB::table('orders')->where('user_id', $id)->where('status', '!=', 'cancelled')->sum('total_amount');
        
        // Lấy 5 đơn hàng gần nhất
        $recentOrders = DB::table('orders')
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('backend.customers.show', compact('customer', 'totalOrders', 'totalSpent', 'recentOrders'));
    }

    /**
     * Thay đổi trạng thái tài khoản.
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $customer = User::where('role', 'customer')->findOrFail($id);
            $customer->is_active = $request->input('is_active');
            
            // Cập nhật lý do khóa tài khoản
            if ($customer->is_active == 0) {
                $customer->lock_reason = $request->input('lock_reason');
            } else {
                $customer->lock_reason = null; // Reset lý do khi mở khóa
            }
            
            $customer->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa nhiều khách hàng hoặc Khóa nhiều.
     * Để an toàn cho dữ liệu lịch sử đơn hàng, mặc định đổi trạng thái is_active = 0.
     * Nếu user muốn xóa thật, có thể delete() nhưng sẽ bị lỗi foreign key nếu có Order.
     * Ở đây dùng delete() và catch lỗi để báo lại cho admin.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['success' => false, 'message' => 'Không có khách hàng nào được chọn!']);
        }

        try {
            // Xóa cứng (Hard Delete)
            $deletedCount = 0;
            $failedCount = 0;
            
            $customers = User::whereIn('id', $ids)->where('role', 'customer')->get();
            
            foreach ($customers as $customer) {
                try {
                    $customer->delete();
                    $deletedCount++;
                } catch (\Illuminate\Database\QueryException $e) {
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
                return response()->json([
                    'success' => true, 
                    'message' => "Đã xóa {$deletedCount} KH. Có {$failedCount} KH có lịch sử (đơn hàng/đánh giá) nên được chuyển sang KHÓA."
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa thành công các khách hàng đã chọn!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        return view('backend.customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
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

        $request->validate([
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
                $oldPath = public_path('images/avatars/' . $customer->avatar);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/avatars'), $filename);
            $data['avatar'] = $filename;
        }

        $customer->update($data);

        return redirect()->route('admin.customers.index')->with('success', 'Cập nhật khách hàng thành công!');
    }
    
    public function destroy($id)
    {
        try {
            $customer = User::where('role', 'customer')->findOrFail($id);
            try {
                $customer->delete();
                if (request()->ajax()) {
                    return response()->json(['success' => true, 'message' => 'Xóa khách hàng thành công!']);
                }
                return redirect()->back()->with('success', 'Xóa khách hàng thành công!');
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == "23000") {
                    $customer->is_active = 0;
                    $customer->save();
                    if (request()->ajax()) {
                        return response()->json(['success' => true, 'message' => 'Khách hàng có lịch sử giao dịch nên tài khoản chỉ bị khóa!']);
                    }
                    return redirect()->back()->with('warning', 'Khách hàng có lịch sử giao dịch nên tài khoản chỉ bị khóa!');
                }
                throw $e;
            }
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
