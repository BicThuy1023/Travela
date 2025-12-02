<?php

namespace App\Http\Controllers\clients;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\clients\Login;
use App\Models\clients\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{

    private $login;
    protected $user;

    public function __construct()
    {
        $this->login = new Login();
        $this->user = new User();
    }
    public function index()
    {
        $title = 'Đăng nhập';
        return view('clients.login', compact('title'));
    }


    public function register(Request $request)
    {
        $username_regis = $request->username_regis;
        $email = $request->email;
        $password_regis = $request->password_regis;

        $checkAccountExist = $this->login->checkUserExist($username_regis, $email);
        if ($checkAccountExist) {
            return response()->json([
                'success' => false,
                'message' => 'Tên người dùng hoặc email đã tồn tại!'
            ]);
        }

        $activation_token = Str::random(60); // Tạo token ngẫu nhiên
        // Nếu không tồn tại, thực hiện đăng ký
        $dataInsert = [
            'username'         => $username_regis,
            'email'            => $email,
            'password'         => md5($password_regis),
            'activation_token' => $activation_token
        ];

        $this->login->registerAcount($dataInsert);

        // Gửi email kích hoạt
        $this->sendActivationEmail($email, $activation_token);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản.'
        ]);
    }

    public function sendActivationEmail($email, $token)
    {
        $activation_link = route('activate.account', ['token' => $token]);

        Mail::send('clients.mail.emails_activation', ['link' => $activation_link], function ($message) use ($email) {
            $message->to($email);
            $message->subject('Kích hoạt tài khoản của bạn');
        });
    }

    public function activateAccount($token)
    {
        $user = $this->login->getUserByToken($token);
        if ($user) {
            $this->login->activateUserAccount($token);

            return redirect('/login')->with('message', 'Tài khoản của bạn đã được kích hoạt!');
        } else {
            return redirect('/login')->with('error', 'Mã kích hoạt không hợp lệ!');
        }
    }

    //Xử lý người dùng đăng nhập
    public function login(Request $request)
    {
        $username = $request->username;
        $password = $request->password;

        $data_login = [
            'username' => $username,
            'password' => md5($password)
        ];

        $user_login = $this->login->login($data_login);
        $userId = $this->user->getUserId($username);
        $user = $this->user->getUser($userId);

        if ($user_login != null) {
            $request->session()->put('username', $username);
            $request->session()->put('avatar', $user->avatar);
            toastr()->success("Đăng nhập thành công!",'Thông báo');
            
            // Lấy URL đích từ session (nếu có), nếu không thì về trang chủ
            $redirectUrl = $request->session()->pull('url.intended', route('home'));
            
            // Parse URL để lấy path (xử lý cả full URL và relative path)
            $parsedUrl = parse_url($redirectUrl);
            $path = $parsedUrl['path'] ?? $redirectUrl;
            
            // Kiểm tra và xử lý các POST-only routes
            // Nếu URL là POST-only route (như /booking/{id}), redirect về tour detail thay vì booking
            if (preg_match('#/booking/(\d+)#', $path, $matches)) {
                // Lấy tour ID từ URL booking
                $tourId = $matches[1];
                // Redirect về tour detail thay vì booking (vì booking chỉ hỗ trợ POST)
                $redirectUrl = route('tour-detail', ['id' => $tourId]);
            }
            // Kiểm tra các POST-only routes khác
            elseif (preg_match('#/(create-booking|create-momo-payment|momo-ipn|cancel-booking|confirm-booking|finish-booking|build-tour/choose|build-tour/checkout|custom-tours/checkout)#', $path)) {
                // Nếu là POST-only route, redirect về trang chủ
                $redirectUrl = route('home');
            }
            // Kiểm tra nếu URL là trang login, redirect về trang chủ
            elseif (strpos($path, '/login') !== false) {
                $redirectUrl = route('home');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công!',
                'redirectUrl' => $redirectUrl,
            ]);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin tài khoản không chính xác!',
            ]);
        }
    }

    //Xử lý đăng xuất
    public function logout(Request $request)
    {
        // Xóa session lưu trữ thông tin người dùng đã đăng nhập
        $request->session()->forget('username');
        $request->session()->forget('avatar');
        $request->session()->forget('userId');
        toastr()->success("Đăng xuất thành công!",'Thông báo');
        return redirect()->route('home');
    }

    // Xử lý quên mật khẩu
    public function forgotPassword(Request $request)
    {
        $email = $request->email_forgot;

        // Kiểm tra email có tồn tại không
        $user = DB::table('tbl_users')->where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email không tồn tại trong hệ thống!'
            ]);
        }

        // Tạo token reset password
        $reset_token = Str::random(60);
        
        // Lưu token vào database (sử dụng bảng password_resets hoặc cột trong tbl_users)
        // Kiểm tra xem có cột reset_token trong tbl_users không, nếu không thì dùng password_resets
        $resetData = [
            'email' => $email,
            'token' => $reset_token,
            'created_at' => now()
        ];

        // Xóa token cũ nếu có
        DB::table('password_resets')->where('email', $email)->delete();
        // Thêm token mới
        DB::table('password_resets')->insert($resetData);

        // Gửi email reset password
        $this->sendResetPasswordEmail($email, $reset_token);

        return response()->json([
            'success' => true,
            'message' => 'Chúng tôi đã gửi link đặt lại mật khẩu đến email của bạn. Vui lòng kiểm tra hộp thư!'
        ]);
    }

    // Gửi email reset password
    public function sendResetPasswordEmail($email, $token)
    {
        $reset_link = route('reset-password.form', ['token' => $token]);

        Mail::send('clients.mail.email_reset_password', ['link' => $reset_link], function ($message) use ($email) {
            $message->to($email);
            $message->subject('Đặt lại mật khẩu - Asia Travel');
        });
    }

    // Hiển thị form reset password
    public function showResetPasswordForm($token)
    {
        // Kiểm tra token có hợp lệ không
        $passwordReset = DB::table('password_resets')
            ->where('token', $token)
            ->where('created_at', '>', now()->subHours(1)) // Token hết hạn sau 1 giờ
            ->first();

        if (!$passwordReset) {
            return redirect()->route('login')->with('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!');
        }

        $title = 'Đặt lại mật khẩu';
        return view('clients.reset-password', compact('title', 'token'));
    }

    // Xử lý reset password
    public function resetPassword(Request $request)
    {
        $token = $request->token;
        $password = $request->password;
        $password_confirm = $request->password_confirm;

        // Kiểm tra mật khẩu khớp không
        if ($password !== $password_confirm) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu xác nhận không khớp!'
            ]);
        }

        // Kiểm tra token có hợp lệ không
        $passwordReset = DB::table('password_resets')
            ->where('token', $token)
            ->where('created_at', '>', now()->subHours(1))
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!'
            ]);
        }

        // Cập nhật mật khẩu mới
        DB::table('tbl_users')
            ->where('email', $passwordReset->email)
            ->update(['password' => md5($password)]);

        // Xóa token đã sử dụng
        DB::table('password_resets')->where('token', $token)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập lại.',
            'redirectUrl' => route('login')
        ]);
    }


}
