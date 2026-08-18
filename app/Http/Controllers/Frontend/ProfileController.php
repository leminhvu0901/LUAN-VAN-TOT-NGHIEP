<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Favorite;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\AdministrativeDivisionService;
use App\Services\GeoapifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController
{
    // HIỂN THỊ THÔNG TIN CÁ NHÂN
    public function index()
    {
        $userId = Auth::id();
        $addresses = UserAddress::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        // Lấy số lượng đơn hàng thực tế của user từ database
        $ordersCount = Order::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payment_method', '!=', 'vnpay')
                    ->orWhereNull('payment_method')
                    ->orWhere('payment_status', '!=', 'unpaid');
            })
            ->count();

        return view('frontend.profile', compact('addresses', 'ordersCount'));
    }

    // CẬP NHẬT THÔNG TIN TÀI KHOẢN
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
            'phone' => ['nullable', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'unique:users,phone,' . Auth::id()],
            'address' => 'nullable|string|max:255',
            'cropped_avatar' => 'nullable|string',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ và tên tối đa 30 ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'phone.unique' => 'Số điện thoại này đã được đăng ký bởi tài khoản khác.',
        ]);
        $updateData = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'updated_at' => now(),
        ];
        if ($request->filled('cropped_avatar')) {
            $base64Data = $request->input('cropped_avatar');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $type = strtolower($type[1]);
                if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    $decodedData = base64_decode($base64Data);
                    if ($decodedData !== false) {
                        $filename = time() . '_' . Str::random(10) . '.' . $type;
                        $dir = upload_dir('avatars');
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        file_put_contents($dir . '/' . $filename, $decodedData);
                        $updateData['avatar'] = upload_rel('avatars', $filename);
                    }
                }
            }
        }

        // Lưu mảng dữ liệu vào Database
        User::query()
            ->where('id', Auth::id())
            ->update($updateData);
        return redirect()->back()->with('success', 'Cập nhật thông tin thành công!');
    }

    // BẬT TẮT NÚT YÊU THÍCH SẢN PHẨM
    public function toggleFavorite(Request $request)
    {
        $productId = $request->input('product_id');
        $userId = Auth::id();

        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is missing']);
        }

        // Kiểm tra xem user đã thả tim sản phẩm này chưa
        $exists = Favorite::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        $status = '';
        if ($exists) {
            // Nếu đã thả tim rồi Bấm lần nữa là Xóa khỏi danh sách
            Favorite::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
            $status = 'removed';
        } else {
            // Nếu chưa có Thêm vào danh sách yêu thích
            Favorite::query()->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => now(),
            ]);
            $status = 'added';
        }

        // Lấy lại danh sách yêu thích MỚI NHẤT của user có join
        $favorites = Favorite::query()
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->leftJoin(
                DB::raw(
                    '(SELECT product_id, ROUND(AVG(rating), 1) as avg_rating, COUNT(id) as review_count
                      FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'
                ),
                'products.id',
                '=',
                'r.product_id'
            )
            ->where('favorites.user_id', $userId)
            ->select(
                'products.*',
                'favorites.id as favorite_id',
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'), // Nếu không có đánh giá thì 0 sao
                DB::raw('COALESCE(r.review_count, 0) as review_count')
            )
            ->get();

        return response()->json([
            'success' => true,
            'status' => $status,
            'items' => $favorites,
            'count' => count($favorites)
        ]);
    }

    // Ngưỡng confidence tối thiểu để chấp nhận kết quả
    private const GEOCODE_MIN_CONFIDENCE = 0.3;

    // KIỂM TRA NỘI DUNG
    private function addressValidationRules(): array
    {
        return [
            'fullname' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'province_code' => 'required|integer',
            'ward_code' => 'required|integer',
            'specific_address' => 'required|string|max:500',
            'type' => 'required|in:home,office',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_method' => 'nullable|in:gps,map,manual',
            'formatted_address' => 'nullable|string|max:500',
        ];
    }
    // NOI DUNG THONG BAO
    private function addressValidationMessages(): array
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'province_code.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'ward_code.required' => 'Vui lòng chọn Phường/Xã.',
        ];
    }

    // Đối chiếu province_code/ward_code khách hợp lệ với dữ liệu hành chính thật của hệ thống hay không
    private function resolveAdministrativeArea(Request $request): array
    {
        $service = app(AdministrativeDivisionService::class);
        $provinceCode = (int) $request->input('province_code');
        $wardCode = (int) $request->input('ward_code');

        $provinces = $service->provinces(); //lây ds thành phó tỉnh
        if ($provinces === null) {
            return ['error' => 'Không thể xác thực dữ liệu địa chỉ. Vui lòng thử lại.', 'field' => 'province_code'];
        }
        $province = collect($provinces)->firstWhere('code', $provinceCode);
        if (!$province) {
            return ['error' => 'Tỉnh/Thành phố không hợp lệ. Vui lòng chọn lại.', 'field' => 'province_code'];
        }

        $wards = $service->wardsOf($provinceCode);//lay ds phường xa
        if ($wards === null) {
            return ['error' => 'Không thể xác thực dữ liệu địa chỉ. Vui lòng thử lại.', 'field' => 'ward_code'];
        }
        $ward = collect($wards)->firstWhere('code', $wardCode);
        if (!$ward) {
            return ['error' => 'Phường/Xã không hợp lệ hoặc không thuộc Tỉnh/Thành phố đã chọn. Vui lòng chọn lại.', 'field' => 'ward_code'];
        }

        return ['province' => $province['name'], 'ward' => $ward['name']];
    }

    // Xác định tọa độ + phương thức + địa chỉ tham khảo
    private function resolveLocation(Request $request, array $area): array
    {
        $method = $request->input('location_method');
        $method = in_array($method, ['gps', 'map', 'manual'], true) ? $method : 'map';

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $formatted = $request->input('formatted_address');

        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            // Bỏ "Phường <số>", Geoapify hiểu sai + luôn thêm "Việt
            $query = trim($request->input('specific_address') . ', ' . $area['ward'] . ', ' . $area['province']);
            $query = preg_replace('/phường\s*\d+/iu', '', $query);
            $query = preg_replace('/,\s*,/', ',', $query);
            $query = trim(preg_replace('/,\s*$/', '', (string) $query)) . ', Việt Nam';

            $storeLat = (float) Setting::getValue('store_latitude', 10.73809);
            $storeLng = (float) Setting::getValue('store_longitude', 106.67812);
            $geo = app(GeoapifyService::class)->geocodeAddress($query, $storeLat, $storeLng);

            if (!$geo || $geo['confidence'] < self::GEOCODE_MIN_CONFIDENCE) {
                return ['error' => 'Không xác định được vị trí cho địa chỉ này. Bạn vui lòng kiểm tra lại hoặc chọn trực tiếp trên bản đồ.'];
            }

            $lat = $geo['lat'];
            $lng = $geo['lng'];
            $formatted = $formatted ?: $geo['formatted'];
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'location_method' => $method,
            'formatted_address' => $formatted,
        ];
    }

    // LƯU ĐỊA CHỈ MỚI
    public function storeAddress(Request $request)
    {
        // Kiểm tra thông tin nhập vào
        $request->validate($this->addressValidationRules(), $this->addressValidationMessages());

        // 1b. Đối chiếu tỉnh/phường với danh mục hành chính chính thức
        $area = $this->resolveAdministrativeArea($request);
        if (isset($area['error'])) {
            return $this->addressError($request, $area['field'], $area['error']);
        }

        // 1c. Xác định tọa độ geocode ở chế độ manual nếu chưa
        $location = $this->resolveLocation($request, $area);
        if (isset($location['error'])) {
            return $this->addressError($request, 'specific_address', $location['error']);
        }

        $userId = Auth::id();
        $isDefault = $request->boolean('is_default'); // User có tick chọn làm mặc định không?

        // Logic xử lý Địa chỉ Mặc định
        if ($isDefault) {
            // Nếu chọn mặc định, phải gỡ bỏ trạng thái mặc định của
            UserAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
        } else {
            // Nếu không chọn mặc định, nhưng user CHƯA CÓ địa chỉ
            $count = UserAddress::query()->where('user_id', $userId)->count();
            if ($count === 0) {
                $isDefault = true;
            }
        }

        // Thêm mới vào DB và lấy ID vừa thêm
        $id = UserAddress::query()->insertGetId([
            'user_id' => $userId,
            'fullname' => $request->input('fullname'),
            'phone' => $request->input('phone'),
            'province' => $area['province'],
            'district' => $area['ward'],
            'ward' => $area['ward'],
            'specific_address' => $request->input('specific_address'),
            'type' => $request->input('type'),
            'is_default' => $isDefault,
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'location_method' => $location['location_method'],
            'formatted_address' => $location['formatted_address'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $id]);
        }
        return redirect()->route('checkout')->with('success', 'Đã thêm địa chỉ giao hàng mới!');
    }

    // CẬP NHẬT ĐỊA CHỈ
    public function updateAddress(Request $request, $id)
    {
        // Kiểm tra dữ liệu, giống hệt hàm Store
        $request->validate($this->addressValidationRules(), $this->addressValidationMessages());

        //  Đối chiếu tỉnh/phường với danh mục hành chính
        $area = $this->resolveAdministrativeArea($request);
        if (isset($area['error'])) {
            return $this->addressError($request, $area['field'], $area['error']);
        }

        //  Xác định tọa độ geocode ở chế độ manual nếu chưa
        $location = $this->resolveLocation($request, $area);
        if (isset($location['error'])) {
            return $this->addressError($request, 'specific_address', $location['error']);
        }

        $userId = Auth::id();
        $isDefault = $request->boolean('is_default');

        // Nếu tick chọn địa chỉ này làm mặc định -> Hủy mặc
        if ($isDefault) {
            UserAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
        }

        // Update DB
        UserAddress::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update([
                'fullname' => $request->input('fullname'),
                'phone' => $request->input('phone'),
                'province' => $area['province'],
                'district' => $area['ward'],
                'ward' => $area['ward'],
                'specific_address' => $request->input('specific_address'),
                'type' => $request->input('type'),
                'is_default' => $isDefault, // Có thể bằng true, nếu user tích hoặc false, nếu user không tích và nó không phải mặc định ban đầu
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'location_method' => $location['location_method'],
                'formatted_address' => $location['formatted_address'],
                'updated_at' => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => intval($id)]);
        }
        return redirect()->route('checkout')->with('success', 'Đã cập nhật địa chỉ giao hàng!');
    }

    // Trả lỗi lưu địa chỉ đúng định dạng theo kiểu
    private function addressError(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => [$field => [$message]]], 422);
        }
        return redirect()->route('checkout')->withErrors([$field => $message])->withInput();
    }

    // XÓA ĐỊA CHỈ
    public function deleteAddress(Request $request, $id)
    {
        $userId = Auth::id();

        // Xóa địa chỉ theo id
        UserAddress::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        // Logic Thông minh: Nếu địa chỉ vừa xóa VÔ TÌNH LÀ
        $hasDefault = UserAddress::query()->where('user_id', $userId)->where('is_default', true)->exists();

        if (!$hasDefault) {
            // Lấy địa chỉ bất kỳ, cũ nhất còn sót lại trong DB để
            $first = UserAddress::query()->where('user_id', $userId)->first();
            if ($first) {
                UserAddress::query()->where('id', $first->id)->update(['is_default' => true]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('checkout')->with('success', 'Đã xóa địa chỉ giao hàng.');
    }

    // Đặt một địa chỉ đã lưu làm mặc định, chỉ tác động nếu
    public function setDefaultAddress(Request $request, $id)
    {
        $userId = Auth::id();

        if (UserAddress::query()->where('id', $id)->where('user_id', $userId)->exists()) {
            UserAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
            UserAddress::query()->where('id', $id)->update(['is_default' => true]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('checkout')->with('success', 'Đã đặt địa chỉ mặc định.');
    }

    // Đổi mật khẩu tài khoản
    public function changePassword(Request $request)
    {
        // Validate dữ liệu nhập vào Bắt buộc phải nhập Mật
        $request->validate([
            'current_password' => 'required',
            // Mật khẩu mới phải >= 6 ký tự và có field `new_password_confirmation` nhập trùng khớp
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.string' => 'Mật khẩu phải là chuỗi ký tự.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
        ]);

        $user = Auth::user();

        // Kiểm tra Mật khẩu cũ có đúng không dùng
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return $this->passwordError('current_password', 'Mật khẩu hiện tại không chính xác.');
        }

        // Mật khẩu mới không được giống hệt mật khẩu cũ
        if ($request->input('current_password') === $request->input('new_password')) {
            return $this->passwordError('new_password', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
        }

        // Mã hóa, Hash mật khẩu mới và lưu xuống Database
        User::query()
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($request->input('new_password')),
                'updated_at' => now(),
            ]);
        return redirect()->back()->with('success', 'Đổi mật khẩu thành công!')->with('active_tab', 'password');
    }

    // Trả lỗi đổi mật khẩu
    private function passwordError(string $field, string $message)
    {
        return redirect()->back()->withErrors([$field => $message])->withInput();
    }
}
