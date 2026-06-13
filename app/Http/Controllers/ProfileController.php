<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController
{
    public function index()
    {
        $addresses = \Illuminate\Support\Facades\DB::table('user_addresses')
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
        return view('pages.profile', compact('addresses'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'unique:users,phone,' . \Illuminate\Support\Facades\Auth::id()],
            'address' => 'nullable|string|max:255',
            'cropped_avatar' => 'nullable|string',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
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
                        $filename = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $type;
                        file_put_contents(public_path('images/avatars/' . $filename), $decodedData);
                        $updateData['avatar'] = $filename;
                    }
                }
            }
        }

        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', \Illuminate\Support\Facades\Auth::id())
            ->update($updateData);

        // Mặc định email không thay đổi qua form này vì liên quan đến login
        return redirect()->back()->with('success', 'Cập nhật thông tin thành công!');
    }

    public function toggleFavorite(Request $request)
    {
        $productId = $request->input('product_id');
        $userId = \Illuminate\Support\Facades\Auth::id();

        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is missing']);
        }

        $exists = \Illuminate\Support\Facades\DB::table('favorites')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        $status = '';
        if ($exists) {
            \Illuminate\Support\Facades\DB::table('favorites')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
            $status = 'removed';
        } else {
            \Illuminate\Support\Facades\DB::table('favorites')->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => now(),
            ]);
            $status = 'added';
        }

        // Fetch updated favorites
        $favorites = \Illuminate\Support\Facades\DB::table('favorites')
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', $userId)
            ->select('products.*', 'favorites.id as favorite_id')
            ->get();

        return response()->json([
            'success' => true,
            'status' => $status,
            'items' => $favorites,
            'count' => count($favorites)
        ]);
    }

    public function storeAddress(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'specific_address' => 'required|string|max:500',
            'type' => 'required|in:home,office',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ], [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ]);

        $userId = \Illuminate\Support\Facades\Auth::id();
        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            \Illuminate\Support\Facades\DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => false]);
        } else {
            $count = \Illuminate\Support\Facades\DB::table('user_addresses')->where('user_id', $userId)->count();
            if ($count === 0) {
                $isDefault = true;
            }
        }

        $id = \Illuminate\Support\Facades\DB::table('user_addresses')->insertGetId([
            'user_id' => $userId,
            'fullname' => $request->input('fullname'),
            'phone' => $request->input('phone'),
            'province' => $request->input('province'),
            'district' => $request->input('district'),
            'ward' => $request->input('ward'),
            'specific_address' => $request->input('specific_address'),
            'type' => $request->input('type'),
            'is_default' => $isDefault,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateAddress(Request $request, $id)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'specific_address' => 'required|string|max:500',
            'type' => 'required|in:home,office',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ], [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ]);

        $userId = \Illuminate\Support\Facades\Auth::id();
        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            \Illuminate\Support\Facades\DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => false]);
        }

        \Illuminate\Support\Facades\DB::table('user_addresses')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update([
                'fullname' => $request->input('fullname'),
                'phone' => $request->input('phone'),
                'province' => $request->input('province'),
                'district' => $request->input('district'),
                'ward' => $request->input('ward'),
                'specific_address' => $request->input('specific_address'),
                'type' => $request->input('type'),
                'is_default' => $isDefault,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'id' => intval($id)]);
    }

    public function deleteAddress($id)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        \Illuminate\Support\Facades\DB::table('user_addresses')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        // If no default address remains, set the first one as default
        $hasDefault = \Illuminate\Support\Facades\DB::table('user_addresses')->where('user_id', $userId)->where('is_default', true)->exists();
        if (!$hasDefault) {
            $first = \Illuminate\Support\Facades\DB::table('user_addresses')->where('user_id', $userId)->first();
            if ($first) {
                \Illuminate\Support\Facades\DB::table('user_addresses')->where('id', $first->id)->update(['is_default' => true]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function setDefaultAddress($id)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        \Illuminate\Support\Facades\DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => false]);
        \Illuminate\Support\Facades\DB::table('user_addresses')->where('id', $id)->where('user_id', $userId)->update(['is_default' => true]);

        return response()->json(['success' => true]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.string' => 'Mật khẩu phải là chuỗi ký tự.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($request->input('current_password'), $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.'])
                ->withInput();
        }

        if ($request->input('current_password') === $request->input('new_password')) {
            return redirect()->back()
                ->withErrors(['new_password' => 'Mật khẩu mới phải khác mật khẩu hiện tại.'])
                ->withInput();
        }

        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => \Illuminate\Support\Facades\Hash::make($request->input('new_password')),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Đổi mật khẩu thành công!');
    }
}
