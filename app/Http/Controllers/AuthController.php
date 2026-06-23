<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{
    public function postRegister(Request $request)
    {
        $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
                'regex:/[\pL]/u' // Phải chứa ít nhất 1 chữ cái (bao gồm tiếng Việt)
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
            'full_name.required' => 'Vui lòng nhập họ tên',
            'full_name.regex' => 'Họ và tên phải chứa ít nhất một chữ cái',
            'email.required' => 'Vui lòng nhập email',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự có ký tự in hoa và ký tự đặc biệt',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.regex' => 'Mật khẩu phải bao gồm ít nhất 1 chữ in hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt (@$!%*#?&)',
        ]);

        $email = $request->input('email');

        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['register_error' => 'Email đã được sử dụng.'])->withInput();
        }

        // Tạo OTP và lưu dữ liệu đăng ký vào session (CHƯA LƯU VÀO DB)
        $otp = rand(1000, 9999);
        session([
            'register_data' => [
                'name' => $request->input('full_name'),
                'email' => $email,
                'phone' => null,
                'password' => Hash::make($request->input('password')),
                'role' => 'customer',
                'is_active' => 1, // Khi lưu vào db sẽ tự động active luôn
            ],
            'verify_email' => $email,
            'verify_otp' => $otp,
            'verify_otp_time' => now()
        ]);
        \Illuminate\Support\Facades\Log::info("OTP for register {$email} is: {$otp}");

        // Gửi email chứa mã OTP
        \Illuminate\Support\Facades\Mail::raw("Mã xác minh OTP của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
            $message->to($email)->subject('Mã xác minh tài khoản');
        });

        return back()->with('show_otp', true);
    }

    public function getVerifyOtp(Request $request)
    {
        if (!$request->session()->has('verify_email')) {
            return redirect('/');
        }

        return back()->with('show_otp', true);
    }

    public function postVerifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|array',
            'otp.*' => 'required|numeric'
        ]);

        $enteredOtp = implode('', $request->input('otp'));
        $sessionOtp = $request->session()->get('verify_otp');
        $sessionTime = $request->session()->get('verify_otp_time');
        $email = $request->session()->get('verify_email');
        $registerData = $request->session()->get('register_data');

        if ($enteredOtp == $sessionOtp) {
            if (now()->diffInSeconds($sessionTime) > 60) {
                return back()->withErrors(['otp_error' => 'Mã OTP đã hết hạn (quá 60 giây). Vui lòng nhấn Gửi lại để nhận mã mới.']);
            }
            
            // Nếu là xác thực quên mật khẩu
            if ($request->session()->get('is_forgot_password')) {
                $request->session()->put('can_reset_password', true);
                return redirect()->route('reset.password.get');
            }

            // Chỉ khi nhập đúng OTP mới tạo User
            if ($registerData) {
                $user = User::create($registerData);
            } else {
                $user = User::where('email', $email)->first();
            }
            if ($user) {
                Auth::login($user);
                // Dọn dẹp session
                $request->session()->forget(['register_data', 'verify_email', 'verify_otp', 'verify_otp_time']);
                $request->session()->put('login_method', 'email');
                return redirect('/');
            }
        }

        return back()->withErrors(['otp_error' => 'Mã OTP không chính xác. Vui lòng thử lại.']);
    }

    public function resendOtp(Request $request)
    {
        if (!$request->session()->has('verify_email')) {
            return redirect('/');
        }

        $email = $request->session()->get('verify_email');
        $otp = rand(1000, 9999);
        session([
            'verify_otp' => $otp,
            'verify_otp_time' => now()
        ]);
        \Illuminate\Support\Facades\Log::info("OTP for resend {$email} is: {$otp}");

        \Illuminate\Support\Facades\Mail::raw("Mã xác minh OTP mới của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
            $message->to($email)->subject('Mã xác minh tài khoản (Gửi lại)');
        });

        return back();
    }

    public function postLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // Tài khoản đã có trong DB thì chắc chắn đã xác thực (is_active mặc định là 1 khi register xong OTP)
        if (Auth::attempt(['email' => $email, 'password' => $password], $request->filled('remember'))) {
            $request->session()->regenerate();
            $request->session()->put('login_method', 'email');

            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.orders.index');
            }

            return redirect('/');
        }

        return back()->withErrors([
            'login_error' => 'Thông tin đăng nhập không chính xác.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $guzzle = new \GuzzleHttp\Client(['verify' => false]);
            $googleUser = Socialite::driver('google')->setHttpClient($guzzle)->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'customer',
                    'is_active' => 1,
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                if (!$user->google_id || !$user->avatar) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $user->avatar ?: $googleUser->getAvatar()
                    ]);
                }
            }

            Auth::login($user);
            session()->put('login_method', 'google');
            
            if ($user->role === 'admin') {
                return redirect()->route('admin.orders.index');
            }

            return redirect('/');
            
        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['login_error' => 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.']);
        }
    }

    public function postForgotPassword(Request $request)
    {
        $request->validate([
            'recovery_contact' => 'required|email'
        ], [
            'recovery_contact.required' => 'Vui lòng nhập email.',
            'recovery_contact.email' => 'Email không đúng định dạng.'
        ]);

        $email = $request->input('recovery_contact');
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Không tìm thấy user, quay lại kèm lỗi để hiển thị modal lại
            return redirect('/')->with('show_forgot', true)->withErrors(['forgot_error' => 'Email không tồn tại trong hệ thống.'])->withInput();
        }

        $otp = rand(1000, 9999);
        session([
            'verify_email' => $email,
            'verify_otp' => $otp,
            'verify_otp_time' => now(),
            'is_forgot_password' => true
        ]);
        \Illuminate\Support\Facades\Log::info("OTP for forgot password {$email} is: {$otp}");

        \Illuminate\Support\Facades\Mail::raw("Mã xác minh khôi phục mật khẩu của bạn là: $otp. Mã này sẽ hết hạn trong 60 giây.", function ($message) use ($email) {
            $message->to($email)->subject('Khôi phục mật khẩu');
        });

        return back()->with('show_otp', true);
    }

    public function getResetPassword(Request $request)
    {
        if (!$request->session()->has('can_reset_password')) {
            return redirect('/');
        }
        return view('auth.reset-password');
    }

    public function postResetPassword(Request $request)
    {
        if (!$request->session()->has('can_reset_password')) {
            return redirect('/');
        }

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

        $email = $request->session()->get('verify_email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->password = Hash::make($request->input('password'));
            $user->save();

            // Clear session data
            $request->session()->forget(['verify_email', 'verify_otp', 'verify_otp_time', 'is_forgot_password', 'can_reset_password']);

            Auth::login($user);
            $request->session()->put('login_method', 'email');
            return redirect('/');
        }

        return redirect('/');
    }
}
