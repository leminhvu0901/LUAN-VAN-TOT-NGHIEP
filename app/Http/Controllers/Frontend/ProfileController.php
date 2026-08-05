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
    /**
     * Mở trang Hồ sơ cá nhân (Profile)
     */
    public function index()
    {
        $userId = Auth::id();
        $addresses = UserAddress::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        // Lấy số lượng đơn hàng thực tế của user từ database (loại bỏ đơn VNPay chưa thanh toán bị lỗi/hủy)
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

    /**
     * Cập nhật thông tin cơ bản của User (Tên, SĐT, Địa chỉ, Avatar)
     */
    public function update(Request $request)
    {
        // 1. Validate (Kiểm tra) dữ liệu người dùng nhập vào
        $request->validate([
            'name' => 'required|string|max:30', // Bắt buộc nhập tên, tối đa 30 ký tự
            // SĐT phải đúng định dạng số điện thoại VN và không được trùng với tài khoản khác (trừ tài khoản hiện tại)
            'phone' => ['nullable', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'unique:users,phone,' . Auth::id()],
            'address' => 'nullable|string|max:255',
            'cropped_avatar' => 'nullable|string', // Chuỗi ảnh Avatar dạng Base64
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ và tên tối đa 30 ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'phone.unique' => 'Số điện thoại này đã được đăng ký bởi tài khoản khác.',
        ]);

        // 2. Gom dữ liệu cần cập nhật lại thành mảng
        $updateData = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'updated_at' => now(),
        ];

        // 3. Xử lý lưu ảnh Avatar (nếu người dùng có cắt ảnh và upload)
        if ($request->filled('cropped_avatar')) {
            $base64Data = $request->input('cropped_avatar');
            // Tách chuỗi Base64 để lấy định dạng file (png, jpg...) và phần data
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $type = strtolower($type[1]);

                // Kiểm tra định dạng hợp lệ
                if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    $decodedData = base64_decode($base64Data);
                    if ($decodedData !== false) {
                        // Đặt tên file ảnh ngẫu nhiên để tránh trùng lặp
                        $filename = time() . '_' . Str::random(10) . '.' . $type;
                        $dir = public_path('images/avatars');
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        file_put_contents($dir . '/' . $filename, $decodedData);
                        // avatar_url()/avatar_path() tự ghép tiền tố 'images/avatars/' - chỉ lưu tên file trần.
                        $updateData['avatar'] = $filename;
                    }
                }
            }
        }

        // 4. Lưu mảng dữ liệu vào Database
        User::query()
            ->where('id', Auth::id())
            ->update($updateData);

        // Mặc định email không thay đổi qua form này vì liên quan đến login
        return redirect()->back()->with('success', 'Cập nhật thông tin thành công!');
    }

    /**
     * Bật/Tắt tính năng Yêu Thích Sản Phẩm (Nút Thả Tim)
     * Trả về dữ liệu dạng JSON cho AJAX (Javascript) xử lý giao diện
     */
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
            // Nếu đã thả tim rồi -> Bấm lần nữa là Xóa khỏi danh sách
            Favorite::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
            $status = 'removed';
        } else {
            // Nếu chưa có -> Thêm vào danh sách yêu thích
            Favorite::query()->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => now(),
            ]);
            $status = 'added';
        }

        // Lấy lại danh sách yêu thích MỚI NHẤT của user (có join bảng products và reviews để lấy Số Sao)
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

        // Trả kết quả về cho Javascript để vẽ lên Ngăn kéo Yêu thích
        return response()->json([
            'success' => true,
            'status' => $status,
            'items' => $favorites,
            'count' => count($favorites)
        ]); // main.js - toggleFavorite() / main.js - removeFromWishlist()
    }

    /**
     * Thêm Địa chỉ giao hàng mới
     */
    // Ngưỡng confidence tối thiểu để chấp nhận kết quả forward-geocode ở chế độ manual. Dưới mức này
    // coi là "quá mơ hồ" -> yêu cầu khách kiểm tra lại / chọn bản đồ (đặc tả mục 4).
    private const GEOCODE_MIN_CONFIDENCE = 0.3;

    // Validate chung cho store + update. province_code/ward_code là mã hành chính khách CHỌN từ 2
    // select (không còn ô nhập tay) — tên tỉnh/phường thật sự dùng để lưu do BACKEND tự tra lại từ
    // AdministrativeDivisionService (xem resolveAdministrativeArea), không tin tên frontend gửi.
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

    private function addressValidationMessages(): array
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'province_code.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'ward_code.required' => 'Vui lòng chọn Phường/Xã.',
        ];
    }

    // Đối chiếu province_code/ward_code khách gửi với danh mục hành chính CHÍNH THỨC
    private function resolveAdministrativeArea(Request $request): array
    {
        $service = app(AdministrativeDivisionService::class);
        $provinceCode = (int) $request->input('province_code');
        $wardCode = (int) $request->input('ward_code');

        $provinces = $service->provinces();
        if ($provinces === null) {
            return ['error' => 'Không thể xác thực dữ liệu địa chỉ. Vui lòng thử lại.', 'field' => 'province_code'];
        }
        $province = collect($provinces)->firstWhere('code', $provinceCode);
        if (!$province) {
            return ['error' => 'Tỉnh/Thành phố không hợp lệ. Vui lòng chọn lại.', 'field' => 'province_code'];
        }

        $wards = $service->wardsOf($provinceCode);
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
            // Bỏ "Phường <số>" (Geoapify hiểu sai) + luôn thêm "Việt Nam" (thiếu quốc gia -> 0 kết quả).
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

    //lưu địa chỉ giao hàng mới
    public function storeAddress(Request $request)
    {
        // 1. Kiểm tra thông tin nhập vào
        $request->validate($this->addressValidationRules(), $this->addressValidationMessages());

        // 1b. Đối chiếu tỉnh/phường với danh mục hành chính chính thức (không tin tên frontend gửi).
        $area = $this->resolveAdministrativeArea($request);
        if (isset($area['error'])) {
            return $this->addressError($request, $area['field'], $area['error']);
        }

        // 1c. Xác định tọa độ (geocode ở chế độ manual nếu chưa có) — mơ hồ/không thấy thì báo lỗi, không lưu.
        $location = $this->resolveLocation($request, $area);
        if (isset($location['error'])) {
            return $this->addressError($request, 'specific_address', $location['error']);
        }

        $userId = Auth::id();
        $isDefault = $request->boolean('is_default'); // User có tick chọn làm mặc định không?

        // 2. Logic xử lý Địa chỉ Mặc định
        if ($isDefault) {
            // Nếu chọn mặc định, phải gỡ bỏ trạng thái mặc định của tất cả các địa chỉ cũ
            UserAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
        } else {
            // Nếu không chọn mặc định, nhưng user CHƯA CÓ địa chỉ nào cả -> Bắt buộc địa chỉ đầu tiên này thành mặc định
            $count = UserAddress::query()->where('user_id', $userId)->count();
            if ($count === 0) {
                $isDefault = true;
            }
        }

        // 3. Thêm mới vào DB và lấy ID vừa thêm
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

    /**
     * Cập nhật 1 Địa chỉ giao hàng đã có
     */
    public function updateAddress(Request $request, $id)
    {
        // 1. Kiểm tra dữ liệu (giống hệt hàm Store)
        $request->validate($this->addressValidationRules(), $this->addressValidationMessages());

        // 1b. Đối chiếu tỉnh/phường với danh mục hành chính chính thức (không tin tên frontend gửi).
        $area = $this->resolveAdministrativeArea($request);
        if (isset($area['error'])) {
            return $this->addressError($request, $area['field'], $area['error']);
        }

        // 1c. Xác định tọa độ (geocode ở chế độ manual nếu chưa có) — mơ hồ/không thấy thì báo lỗi, không lưu.
        $location = $this->resolveLocation($request, $area);
        if (isset($location['error'])) {
            return $this->addressError($request, 'specific_address', $location['error']);
        }

        $userId = Auth::id();
        $isDefault = $request->boolean('is_default');

        // 2. Nếu tick chọn địa chỉ này làm mặc định -> Hủy mặc định các địa chỉ khác
        if ($isDefault) {
            UserAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
        }

        // 3. Update DB
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
                'is_default' => $isDefault, // Có thể bằng true (nếu user tích) hoặc false (nếu user không tích và nó không phải mặc định ban đầu)
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

    /**
     * Trả lỗi lưu địa chỉ đúng định dạng theo kiểu request: JSON 422 cho fetch (modal địa chỉ ở trang
     * checkout submit qua AJAX nếu còn dùng), redirect-back kèm withErrors() cho form thật.
     */
    private function addressError(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => [$field => [$message]]], 422);
        }
        return redirect()->route('checkout')->withErrors([$field => $message])->withInput();
    }

    /**
     * Xóa Địa chỉ giao hàng
     */
    public function deleteAddress(Request $request, $id)
    {
        $userId = Auth::id();

        // 1. Xóa địa chỉ theo id
        UserAddress::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        // 2. Logic Thông minh: Nếu địa chỉ vừa xóa VÔ TÌNH LÀ ĐỊA CHỈ MẶC ĐỊNH
        // -> User sẽ không còn địa chỉ mặc định nào nữa.
        $hasDefault = UserAddress::query()->where('user_id', $userId)->where('is_default', true)->exists();

        if (!$hasDefault) {
            // Lấy địa chỉ bất kỳ (cũ nhất) còn sót lại trong DB để đôn lên làm mặc định thay thế
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

    /**
     * Đặt một địa chỉ làm địa chỉ mặc định (khi bấm nút "Thiết lập mặc định")
     */
    public function setDefaultAddress($id)
    {
        $userId = Auth::id();
        // Bước 1: Xóa trạng thái mặc định của tất cả địa chỉ
        UserAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
        
        // Bước 2: Set riêng địa chỉ có ID được chọn thành mặc định
        UserAddress::query()->where('id', $id)->where('user_id', $userId)->update(['is_default' => true]);

        return response()->json(['success' => true]); // Không dùng trực tiếp ở JS (hoặc dự phòng)
    }

    /**
     * Đổi mật khẩu tài khoản
     */
    public function changePassword(Request $request)
    {
        // 1. Validate dữ liệu nhập vào (Bắt buộc phải nhập Mật khẩu cũ và Mật khẩu mới)
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

        // 2. Kiểm tra Mật khẩu cũ có đúng không (dùng Hash::check vì pass lưu trong DB đã bị mã hóa)
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return $this->passwordError($request, 'current_password', 'Mật khẩu hiện tại không chính xác.');
        }

        // 3. Mật khẩu mới không được giống hệt mật khẩu cũ
        if ($request->input('current_password') === $request->input('new_password')) {
            return $this->passwordError($request, 'new_password', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
        }

        // 4. Mã hóa (Hash) mật khẩu mới và lưu xuống Database
        User::query()
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($request->input('new_password')),
                'updated_at' => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đổi mật khẩu thành công!']); // profile.js - submitProfileForm()
        }

        // Cờ riêng để Blade biết mở lại đúng tab "Đổi mật khẩu" sau khi tải lại trang — URL hash
        // (#password) không được trình duyệt gửi lên server nên không thể dựa vào đó.
        return redirect()->back()->with('success', 'Đổi mật khẩu thành công!')->with('active_tab', 'password');
    }

    /**
     * Trả lỗi đổi mật khẩu đúng định dạng theo kiểu request: JSON 422 cho fetch (form submit qua
     * AJAX, xem profile.js) để JS hiện lỗi tại chỗ không cần tải lại trang; redirect-back cổ điển
     * (kèm withInput()) cho request thường.
     */
    private function passwordError(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'errors' => [$field => [$message]]], 422); // profile.js - submitProfileForm()
        }
        return redirect()->back()->withErrors([$field => $message])->withInput();
    }
}
