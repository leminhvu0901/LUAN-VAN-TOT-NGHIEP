<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{
    // Thời gian hiệu lực của mã OTP, giây.
    private const OTP_LIFETIME_SECONDS = 60;

    // Thời hạn của quyền đặt lại mật khẩu, giây tính từ lúc
    private const RESET_PASSWORD_WINDOW_SECONDS = 60;

    // HÀM XỬ LÝ THÔNG TIN ĐĂNG KÝ TÀI KHOẢN
    public function postRegister(Request $request)
    {
        // Kiểm tra tính hợp lệ của dữ liệu đầu vào
        $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
                'regex:/[\pL]/u'
            ],
            'email' => [
                'required',
                'string',
                'max:255',
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
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
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

        // Kiểm tra xem Email đăng ký đã tồn tại chưa
        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['register_error' => 'Email đã được sử dụng.'])->withInput();
        }

        // Tạo mã OTP gồm 4 số ngẫu nhiên
        $otp = rand(1000, 9999);

        // Lưu dữ liệu đăng ký tạm thời vào Session Chưa lưu
        session([
            'register_data' => [
                'name' => $request->input('full_name'),
                'email' => $email,
                'phone' => null,
                'password' => Hash::make($request->input('password')), // Mã hóa mật khẩu an toàn bằng bcrypt
                'role' => 'customer',
                'is_active' => 1,
            ],
            'verify_email' => $email,
            'verify_otp' => $otp,
            'verify_otp_time' => now()
        ]);

        // Gửi email chứa mã OTP xác nhận tài khoản, bọc try/catch
        try {
            Mail::raw("Mã xác minh OTP của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
                $message->to($email)->subject('Mã xác minh tài khoản');
            });
        } catch (\Throwable $e) {
            Log::error('Register OTP mail send failed: ' . $e->getMessage());
            $message = 'Không thể gửi email xác minh lúc này, vui lòng thử lại sau.';
            return back()->withErrors(['register_error' => $message])->withInput();
        }

        // Đưa người dùng quay lại và đính kèm cờ show_otp để
        return back()->with('show_otp', true);
    }

    // XÁC NHẬN MÃ OTP
    public function postVerifyOtp(Request $request)
    {
        // Kiểm tra định dạng dữ liệu OTP
        $request->validate([
            'otp' => 'required|array',
            'otp.*' => 'required|numeric'
        ]);

        // Ghép các ký tự số lẻ trong mảng thành một chuỗi OTP
        $enteredOtp = implode('', $request->input('otp'));

        // Lấy thông tin OTP và dữ liệu đăng ký tạm thời từ Session ra
        $sessionOtp = $request->session()->get('verify_otp');
        $sessionTime = $request->session()->get('verify_otp_time');
        $email = $request->session()->get('verify_email');
        $registerData = $request->session()->get('register_data');

        // So khớp mã OTP người dùng nhập với mã đã gửi
        if ($enteredOtp == $sessionOtp) {
            if ($sessionTime) {
                $otpIssuedAt = Carbon::parse($sessionTime);
            } else {
                $otpIssuedAt = null;
            }
            if (!$otpIssuedAt || $otpIssuedAt->diffInSeconds(now()) > self::OTP_LIFETIME_SECONDS) {
                return $this->otpError('Mã OTP đã hết hạn. Vui lòng nhấn Gửi lại để nhận mã mới.');
            }

            // TH1: Nếu đây là Quên mật khẩu
            if ($request->session()->get('is_forgot_password')) {
                $request->session()->put('can_reset_password', true);
                $request->session()->put('can_reset_password_at', now()->toDateTimeString());
                return back()->with('show_reset_password', true);
            }

            // TH2: Nếu đây là Đăng ký tài khoản mới
            if ($registerData) {
                try {
                    $user = User::create($registerData);
                } catch (\Throwable $e) {
                    Log::error('Verify OTP - create user failed: ' . $e->getMessage());
                    $request->session()->forget(['register_data', 'verify_email', 'verify_otp', 'verify_otp_time']);
                    return $this->otpError('Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.');
                }
            } else {
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                Auth::login($user);  // Tự động đăng nhập luôn cho User vừa được tạo
                if ($registerData) {
                    $request->session()->flash('success', 'Đăng ký tài khoản thành công! Chào mừng bạn đến với Happy Tea.');
                }
                // Dọn dẹp sạch các khóa session tạm thời để tránh rác bộ nhớ
                $request->session()->forget(['register_data', 'verify_email', 'verify_otp', 'verify_otp_time']);
                $request->session()->put('login_method', 'email');
                return redirect('/');
            }
        }
        return $this->otpError('Mã OTP không chính xác. Vui lòng thử lại.');
    }

    /**
     * Trả lỗi xác thực OTP: quay lại modal OTP kèm thông báo lỗi.
     */
    private function otpError(string $message)
    {
        return back()->withErrors(['otp_error' => $message]);
    }

    // GỬI LẠI MÃ OTP
    public function resendOtp(Request $request)
    {
        // Nếu không tồn tại email cần xác nhận trong session ->
        if (!$request->session()->has('verify_email')) {
            return redirect('/');
        }
        $email = $request->session()->get('verify_email');
        $otp = rand(1000, 9999); // Tạo lại mã ngẫu nhiên mới

        session([// Ghi đè OTP mới kèm mốc thời gian hiện tại vào session
            'verify_otp' => $otp,
            'verify_otp_time' => now()
        ]);

        // Gửi lại thư điện tử mới chứa mã OTP vừa cập nhật
        try {
            Mail::raw("Mã xác minh OTP mới của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
                $message->to($email)->subject('Mã xác minh tài khoản (Gửi lại)');
            });
        } catch (\Throwable $e) {
            Log::error('Resend OTP mail send failed: ' . $e->getMessage());
            return $this->otpError('Không thể gửi lại email xác minh lúc này, vui lòng thử lại sau.');
        }

        return back();
    }

    // HỦY OTP
    public function cancelOtp(Request $request)
    {
        $request->session()->forget([
            'register_data',
            'verify_email',
            'verify_otp',
            'verify_otp_time',
            'is_forgot_password',
            'can_reset_password',
            'can_reset_password_at'
        ]);
        return redirect('/');
    }

    // XỬ LÝ THÔNG TIN ĐĂNG NHẬP
    public function postLogin(Request $request)
    {
        // Kiểm tra tính bắt buộc của Email và Mật khẩu
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // Thực hiện đăng nhập bằng facade Auth e
        if (Auth::attempt(['email' => $email, 'password' => $password], $request->filled('remember'))) {
            $user = Auth::user();

            // đăng xuất tài khoản đã xóa
            if (!$user->is_active) {
                Auth::logout();
                return $this->loginError($request, $user->lock_reason
                    ? "Tài khoản của bạn đã bị khóa: {$user->lock_reason}"
                    : 'Tài khoản của bạn đã bị khóa.');
            }

            // Đăng nhập thành công
            $request->session()->regenerate();
            $request->session()->put('login_method', 'email');

            // chuyển sang trang chuẩn
            if ($user->role === 'admin') {
                $destination = route('admin.dashboard');
            } elseif ($user->role === 'staff') {
                $destination = route($user->staff_type === 'delivery' ? 'staff.delivery.dashboard' : 'staff.reception.dashboard');
            } else {
                $destination = url('/');
            }

            return redirect($destination);
        }

        // Đăng nhập thất bại: Quay lại trang trước, đính kèm
        return $this->loginError($request, 'Thông tin đăng nhập không chính xác.');
    }

    /**
     * Trả lỗi đăng nhập: quay lại modal đăng nhập kèm thông báo lỗi và giữ lại email đã nhập.
     */
    private function loginError(Request $request, string $message)
    {
        return back()->withErrors(['login_error' => $message])->withInput($request->only('email'));
    }

   // ĐĂNG XUẤT
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken(); // Khởi tạo lại token bảo mật CSRF mới
        return redirect('/');
    }

    // CHUYỂN HƯỚNG SANG TRANG SÁC THỰC CỦA GG
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // XỬ LÝ THÔNG TIN google TRẢ VỀ
    public function handleGoogleCallback()
    {
        try {
            // Tắt xác thực chứng chỉ SSL tạm thời trên môi trường
            $guzzle = new GuzzleClient(['verify' => false]);
            $googleUser = Socialite::driver('google')->setHttpClient($guzzle)->user();

            // kiểm tra email đã tồn tại chưa
            $user = User::where('email', $googleUser->getEmail())->first();

            // TH1: Nếu là tài khoản hoàn toàn mới chưa từng đăng nhập
            if (!$user) {
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
                // TH2: Nếu tài khoản đã tồn tại bằng email đăng ký
                if (!$user->google_id || !$user->avatar) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $user->avatar ?: $googleUser->getAvatar()
                    ]);
                }
            }

            // Tài khoản đã bị khóa, is_active = 0: không cho vào
            if (!$user->is_active) {
                return redirect('/login')->withErrors([
                    'login_error' => $user->lock_reason
                        ? "Tài khoản của bạn đã bị khóa: {$user->lock_reason}"
                        : 'Tài khoản của bạn đã bị khóa.',
                ]);
            }

            // Đăng nhập trực tiếp cho người dùng
            Auth::login($user);
            session()->put('login_method', 'google');

            // Phân quyền điều hướng sau khi đăng nhập Google
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'staff') {
                return redirect()->route($user->staff_type === 'delivery' ? 'staff.delivery.dashboard' : 'staff.reception.dashboard');
            }

            return redirect('/');
        } catch (\Exception $e) {
            // Ghi lỗi chi tiết vào hệ thống log để lập trình viên theo dõi
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['login_error' => 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.']);
        }
    }

    // XỬ LÝ YÊU CẦU QUÊN MẬT KHẨU
    public function postForgotPassword(Request $request)
    {
        // Kiểm tra định dạng trường email nhập vào
        $request->validate([
            'recovery_contact' => 'required|email'
        ], [
            'recovery_contact.required' => 'Vui lòng nhập email.',
            'recovery_contact.email' => 'Email không đúng định dạng.'
        ]);

        $email = $request->input('recovery_contact');
        $user = User::where('email', $email)->first();

        // Nếu email nhập vào không tồn tại trong hệ thống
        if (!$user) {
            return redirect('/')->with('show_forgot', true)->withErrors(['forgot_error' => 'Email không tồn tại trong hệ thống.'])->withInput();
        }
        // Tạo mã OTP phục hồi mật khẩu gồm 4 chữ số
        $otp = rand(1000, 9999);
        session([
            'verify_email' => $email,
            'verify_otp' => $otp,
            'verify_otp_time' => now(),
            'is_forgot_password' => true // Đánh dấu đây là phiên xác thực quên mật khẩu
        ]);
        // Gửi email chứa mã OTP khôi phục mật khẩu
        try {
            Mail::raw("Mã xác minh khôi phục mật khẩu của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
                $message->to($email)->subject('Khôi phục mật khẩu');
            });
        } catch (\Throwable $e) {
            Log::error('Forgot password OTP mail send failed: ' . $e->getMessage());
            $message = 'Không thể gửi email xác minh lúc này, vui lòng thử lại sau.';
            return redirect('/')->with('show_forgot', true)->withErrors(['forgot_error' => $message])->withInput();
        }
        return back()->with('show_otp', true);
    }

    /**
     * Hiển thị giao diện cho phép nhập Mật khẩu mới (chỉ chạy sau khi đã qua bước nhập OTP hợp lệ).
     */
    public function getResetPassword(Request $request)
    {
        if (!$this->hasValidResetPermission($request)) {//KHONG CO QUYEN
            return redirect('/');
        }
        return redirect('/')->with('show_reset_password', true);
    }

    // KIỂM TRA QUYỂN ĐẶT LẠI MẬT KHẨU
    private function hasValidResetPermission(Request $request): bool
    {
        if (!$request->session()->has('can_reset_password')) {
            return false;
        }
        $grantedAt = $request->session()->get('can_reset_password_at');
        if (!$grantedAt) {
            return false;
        }
        // Carbon::parse: session serialize kiểu json nên giá trị
        return Carbon::parse($grantedAt)->diffInSeconds(now()) <= self::RESET_PASSWORD_WINDOW_SECONDS;
    }

    // LƯU MẬT KHẨU MỚI
    public function postResetPassword(Request $request)
    {
        if (!$this->hasValidResetPermission($request)) {// DU QUYEN KHONG
            $request->session()->forget(['can_reset_password', 'can_reset_password_at']);
            $message = 'Phiên xác thực đã hết hạn, vui lòng thực hiện lại thao tác quên mật khẩu.';
            return redirect('/')->withErrors(['reset_error' => $message]);
        }

        // Kiểm tra tính hợp lệ và độ mạnh của mật khẩu mới nhập vào
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
            // Cập nhật mật khẩu mới, Mã hóa bcrypt mật khẩu trước khi lưu
            $user->password = Hash::make($request->input('password'));
            $user->save();

            // Xóa sạch toàn bộ các khóa xác thực tạm thời trong Session
            $request->session()->forget(['verify_email', 'verify_otp', 'verify_otp_time', 'is_forgot_password', 'can_reset_password', 'can_reset_password_at']);

            // Tự động đăng nhập luôn cho người dùng sau khi đổi mật
            Auth::login($user);
            $request->session()->put('login_method', 'email');
            $request->session()->flash('success', 'Đặt lại mật khẩu thành công! Bạn đã được đăng nhập.');

            return redirect('/');
        }

        $message = 'Không tìm thấy tài khoản, vui lòng thực hiện lại thao tác quên mật khẩu.';
        return redirect('/')->withErrors(['reset_error' => $message]);
    }
}
