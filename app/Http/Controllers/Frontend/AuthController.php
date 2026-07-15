<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{
    /**
     * Xử lý yêu cầu Đăng ký tài khoản mới từ phía người dùng.
     */
    public function postRegister(Request $request)
    {
        // 1. Kiểm tra tính hợp lệ của dữ liệu đầu vào (Validation)
        $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
                'regex:/[\pL]/u' // Họ tên phải chứa ít nhất 1 chữ cái (chấp nhận cả chữ Tiếng Việt có dấu)
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                // Sử dụng hàm ẩn (Closure function) để kiểm tra kỹ định dạng email đặc thù
                function ($attribute, $value, $fail) {
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) && preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu)$/i', $value);
                    if (!$isEmail) {
                        $fail('Vui lòng nhập đúng định dạng email hợp lệ (vd: @gmail.com).');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed', // Bắt buộc trường password_confirmation phải khớp
                'regex:/[a-z]/', // Phải có ít nhất 1 chữ thường
                'regex:/[A-Z]/', // Phải có ít nhất 1 chữ hoa
                'regex:/[0-9]/', // Phải có ít nhất 1 chữ số
                'regex:/[@$!%*#?&]/', // Phải có ít nhất 1 ký tự đặc biệt
            ],
        ], [
            // Thiết lập các thông báo lỗi hiển thị bằng Tiếng Việt
            'full_name.required' => 'Vui lòng nhập họ tên',
            'full_name.regex' => 'Họ và tên phải chứa ít nhất một chữ cái',
            'email.required' => 'Vui lòng nhập email',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự có ký tự in hoa và ký tự đặc biệt',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.regex' => 'Mật khẩu phải bao gồm ít nhất 1 chữ in hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt (@$!%*#?&)',
        ]);

        $email = $request->input('email');

        // 2. Kiểm tra xem Email đăng ký đã tồn tại trong Database chưa
        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['register_error' => 'Email đã được sử dụng.'])->withInput();
        }

        // 3. Tạo mã OTP gồm 4 số ngẫu nhiên
        $otp = rand(1000, 9999);
        
        // 4. Lưu dữ liệu đăng ký tạm thời vào Session (Chưa lưu ngay vào DB)
        session([
            'register_data' => [
                'name' => $request->input('full_name'),
                'email' => $email,
                'phone' => null,
                'password' => Hash::make($request->input('password')), // Mã hóa mật khẩu an toàn bằng bcrypt
                'role' => 'customer',
                'is_active' => 1, // Mặc định kích hoạt luôn sau khi nhập đúng OTP
            ],
            'verify_email' => $email,
            'verify_otp' => $otp,
            'verify_otp_time' => now() // Ghi lại mốc thời gian tạo mã để kiểm tra hết hạn
        ]);
        
        // Ghi log mã OTP vào file log của hệ thống để tiện cho việc kiểm thử/debug
        \Illuminate\Support\Facades\Log::info("OTP for register {$email} is: {$otp}");

        // 5. Gửi email chứa mã OTP xác nhận tài khoản
        \Illuminate\Support\Facades\Mail::raw("Mã xác minh OTP của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
            $message->to($email)->subject('Mã xác minh tài khoản');
        });

        // Đưa người dùng quay lại và đính kèm cờ show_otp để giao diện hiển thị Popup nhập mã OTP
        return back()->with('show_otp', true);
    }

    /**
     * Lấy giao diện hiển thị Popup nhập mã OTP khi chuyển trang.
     */
    public function getVerifyOtp(Request $request)
    {
        // Nếu Session không lưu email cần xác thực -> đẩy về trang chủ
        if (!$request->session()->has('verify_email')) {
            return redirect('/');
        }

        return back()->with('show_otp', true);
    }

    /**
     * Nhận và xác thực mã OTP do người dùng nhập vào.
     */
    public function postVerifyOtp(Request $request)
    {
        // 1. Kiểm tra định dạng dữ liệu OTP (gồm một mảng các chữ số)
        $request->validate([
            'otp' => 'required|array',
            'otp.*' => 'required|numeric'
        ]);

        // Ghép các ký tự số lẻ trong mảng thành một chuỗi OTP hoàn chỉnh
        $enteredOtp = implode('', $request->input('otp'));
        
        // Lấy thông tin OTP và dữ liệu đăng ký tạm thời từ Session ra
        $sessionOtp = $request->session()->get('verify_otp');
        $sessionTime = $request->session()->get('verify_otp_time');
        $email = $request->session()->get('verify_email');
        $registerData = $request->session()->get('register_data');

        // 2. So khớp mã OTP người dùng nhập với mã đã gửi
        if ($enteredOtp == $sessionOtp) {
            // Kiểm tra mã OTP xem đã quá hạn 60 giây hay chưa
            if (now()->diffInSeconds($sessionTime) > 60) {
                return back()->withErrors(['otp_error' => 'Mã OTP đã hết hạn (quá 60 giây). Vui lòng nhấn Gửi lại để nhận mã mới.']);
            }
            
            // TH1: Nếu đây là quá trình xác thực phục vụ việc Quên mật khẩu
            if ($request->session()->get('is_forgot_password')) {
                // Đánh dấu người dùng được phép chuyển sang trang đặt lại mật khẩu mới
                $request->session()->put('can_reset_password', true);
                return redirect()->route('reset.password.get');
            }

            // TH2: Nếu đây là quá trình xác thực Đăng ký tài khoản mới
            if ($registerData) {
                // Tạo mới bản ghi User thực sự vào trong Database
                $user = User::create($registerData);
            } else {
                $user = User::where('email', $email)->first();
            }
            
            if ($user) {
                // Tự động đăng nhập luôn cho User vừa được tạo
                Auth::login($user);
                
                // Dọn dẹp sạch các khóa session tạm thời để tránh rác bộ nhớ
                $request->session()->forget(['register_data', 'verify_email', 'verify_otp', 'verify_otp_time']);
                $request->session()->put('login_method', 'email');
                return redirect('/');
            }
        }

        // Nếu mã OTP không khớp, quay lại và báo lỗi
        return back()->withErrors(['otp_error' => 'Mã OTP không chính xác. Vui lòng thử lại.']);
    }

    /**
     * Gửi lại mã OTP mới trong trường hợp mã cũ bị hết hạn hoặc không nhận được.
     */
    public function resendOtp(Request $request)
    {
        // Nếu không tồn tại email cần xác nhận trong session -> đẩy ra ngoài trang chủ
        if (!$request->session()->has('verify_email')) {
            return redirect('/');
        }

        $email = $request->session()->get('verify_email');
        $otp = rand(1000, 9999); // Tạo lại mã ngẫu nhiên mới
        
        // Ghi đè OTP mới kèm mốc thời gian hiện tại vào session
        session([
            'verify_otp' => $otp,
            'verify_otp_time' => now()
        ]);
        
        \Illuminate\Support\Facades\Log::info("OTP for resend {$email} is: {$otp}");

        // Gửi lại thư điện tử mới chứa mã OTP vừa cập nhật
        \Illuminate\Support\Facades\Mail::raw("Mã xác minh OTP mới của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
            $message->to($email)->subject('Mã xác minh tài khoản (Gửi lại)');
        });

        return back();
    }

    /**
     * Hủy và dọn dẹp các khóa session liên quan đến OTP khi người dùng không muốn thay đổi mật khẩu/đăng ký nữa.
     */
    public function cancelOtp(Request $request)
    {
        $request->session()->forget([
            'register_data',
            'verify_email',
            'verify_otp',
            'verify_otp_time',
            'is_forgot_password',
            'can_reset_password'
        ]);
        return response()->json(['status' => 'success']);
    }

    /**
     * Xử lý yêu cầu Đăng nhập bằng Email & Mật khẩu thông thường.
     */
    public function postLogin(Request $request)
    {
        // 1. Kiểm tra tính bắt buộc của Email và Mật khẩu
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // 2. Thực hiện đăng nhập bằng facade Auth (Tự kiểm tra email và giải mã khớp bcrypt password)
        // Tham số thứ 2 `$request->filled('remember')` hỗ trợ chức năng "Ghi nhớ đăng nhập" qua Cookie
        if (Auth::attempt(['email' => $email, 'password' => $password], $request->filled('remember'))) {
            $user = Auth::user();

            // Đăng nhập thành công: Làm mới ID Session để chống tấn công cố định phiên (Session Fixation)
            $request->session()->regenerate();
            $request->session()->put('login_method', 'email');

            // Nếu tài khoản có vai trò là quản trị viên -> đưa thẳng vào trang tổng quan
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            // Người dùng thường thì quay về trang chủ
            return redirect('/');
        }

        // Đăng nhập thất bại: Quay lại trang trước, đính kèm thông báo lỗi và giữ lại email cũ đã nhập nháp
        return back()->withErrors([
            'login_error' => 'Thông tin đăng nhập không chính xác.',
        ])->withInput($request->only('email'));
    }

    /**
     * Đăng xuất tài khoản, xóa phiên làm việc và mã xác thực hiện tại.
     */
    public function logout(Request $request)
    {
        Auth::logout(); // Hủy trạng thái đăng nhập trong ứng dụng
        
        $request->session()->invalidate(); // Hủy bỏ và xóa sạch tất cả dữ liệu lưu trong session hiện tại
        $request->session()->regenerateToken(); // Khởi tạo lại token bảo mật CSRF mới
        
        return redirect('/');
    }

    /**
     * Chuyển hướng người dùng sang trang xác thực tài khoản của Google (OAuth2).
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Tiếp nhận phản hồi (callback) chứa thông tin từ máy chủ Google gửi về sau khi xác thực thành công.
     */
    public function handleGoogleCallback()
    {
        try {
            // Tắt xác thực chứng chỉ SSL tạm thời trên môi trường local phát triển để tránh lỗi cURL
            $guzzle = new \GuzzleHttp\Client(['verify' => false]);
            $googleUser = Socialite::driver('google')->setHttpClient($guzzle)->user();
            
            // Tìm kiếm xem tài khoản Email của Google này đã đăng ký trên hệ thống của ta chưa
            $user = User::where('email', $googleUser->getEmail())->first();

            // TH1: Nếu là tài khoản hoàn toàn mới chưa từng đăng nhập
            if (!$user) {
                // Tạo mới tài khoản cho khách bằng thông tin lấy từ Google
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(24)), // Tạo một mật khẩu ngẫu nhiên dài 24 ký tự
                    'role' => 'customer',
                    'is_active' => 1,
                    'google_id' => $googleUser->getId(), // Lưu trữ ID định danh của Google
                ]);
            } else {
                // TH2: Nếu tài khoản đã tồn tại bằng email đăng ký thường, thực hiện đồng bộ thêm thông tin Google ID và ảnh đại diện
                if (!$user->google_id || !$user->avatar) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $user->avatar ?: $googleUser->getAvatar()
                    ]);
                }
            }

            // Đăng nhập trực tiếp cho người dùng
            Auth::login($user);
            session()->put('login_method', 'google');
            
            // Phân quyền điều hướng sau khi đăng nhập Google
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect('/');
            
        } catch (\Exception $e) {
            // Ghi lỗi chi tiết vào hệ thống log để lập trình viên theo dõi
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['login_error' => 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.']);
        }
    }

    /**
     * Xử lý yêu cầu gửi mã xác nhận phục hồi mật khẩu khi người dùng bấm Quên mật khẩu.
     */
    public function postForgotPassword(Request $request)
    {
        // 1. Kiểm tra định dạng trường email nhập vào
        $request->validate([
            'recovery_contact' => 'required|email'
        ], [
            'recovery_contact.required' => 'Vui lòng nhập email.',
            'recovery_contact.email' => 'Email không đúng định dạng.'
        ]);

        $email = $request->input('recovery_contact');
        $user = User::where('email', $email)->first();

        // 2. Nếu email nhập vào không tồn tại trong hệ thống
        if (!$user) {
            // Quay về trang chủ và gửi kèm cờ show_forgot để popup Quên mật khẩu tự động bật lên và in lỗi
            return redirect('/')->with('show_forgot', true)->withErrors(['forgot_error' => 'Email không tồn tại trong hệ thống.'])->withInput();
        }

        // 3. Tạo mã OTP phục hồi mật khẩu gồm 4 chữ số
        $otp = rand(1000, 9999);
        session([
            'verify_email' => $email,
            'verify_otp' => $otp,
            'verify_otp_time' => now(),
            'is_forgot_password' => true // Đánh dấu đây là phiên xác thực quên mật khẩu
        ]);
        
        \Illuminate\Support\Facades\Log::info("OTP for forgot password {$email} is: {$otp}");

        // 4. Gửi email chứa mã OTP khôi phục mật khẩu
        \Illuminate\Support\Facades\Mail::raw("Mã xác minh khôi phục mật khẩu của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
            $message->to($email)->subject('Khôi phục mật khẩu');
        });

        // Quay lại kèm cờ show_otp để hiển thị popup nhập mã xác thực OTP
        return back()->with('show_otp', true);
    }

    /**
     * Hiển thị giao diện cho phép nhập Mật khẩu mới (chỉ chạy sau khi đã qua bước nhập OTP hợp lệ).
     */
    public function getResetPassword(Request $request)
    {
        // Nếu session không chứa cờ xác thực OTP thành công -> chặn lại không cho đổi mật khẩu
        if (!$request->session()->has('can_reset_password')) {
            return redirect('/');
        }
        return view('auth.reset-password');
    }

    /**
     * Lưu lại mật khẩu mới được thay đổi của người dùng vào Database.
     */
    public function postResetPassword(Request $request)
    {
        // Chặn nếu người dùng cố tình truy cập trực tiếp bằng POST mà chưa qua bước xác thực OTP
        if (!$request->session()->has('can_reset_password')) {
            return redirect('/');
        }

        // 1. Kiểm tra tính hợp lệ và độ mạnh của mật khẩu mới nhập vào
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự có ký tự in hoa và ký tự đặc biệt',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.regex' => 'Mật khẩu phải bao gồm ít nhất 1 chữ in hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt (@$!%*#?&)',
        ]);

        // Lấy email đã được xác minh trước đó lưu trong Session
        $email = $request->session()->get('verify_email');
        $user = User::where('email', $email)->first();

        if ($user) {
            // Cập nhật mật khẩu mới (Mã hóa bcrypt mật khẩu trước khi lưu)
            $user->password = Hash::make($request->input('password'));
            $user->save();

            // Xóa sạch toàn bộ các khóa xác thực tạm thời trong Session
            $request->session()->forget(['verify_email', 'verify_otp', 'verify_otp_time', 'is_forgot_password', 'can_reset_password']);

            // Tự động đăng nhập luôn cho người dùng sau khi đổi mật khẩu thành công
            Auth::login($user);
            $request->session()->put('login_method', 'email');
            return redirect('/');
        }

        return redirect('/');
    }
}
