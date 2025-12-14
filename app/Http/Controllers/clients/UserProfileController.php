<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\clients\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserProfileController extends Controller
{

    public function __construct()
    {
        parent::__construct(); // Gọi constructor của Controller để khởi tạo $user
    }

    public function index()
    {
        $title = 'Thông tin cá nhân';
        $userId = $this->getUserId();
        $user = $this->user->getUser($userId);
        // dd( $userId );
        return view('clients.user-profile', compact('title', 'user'));
    }

    public function update(Request $req)
    {
        try {
            $req->validate([
                'fullName' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
            ]);

            $fullName = $req->fullName;
            $address = $req->address ?? '';
            $email = $req->email;
            $phone = $req->phone;

            $dataUpdate = [
                'fullName' => $fullName,
                'address' => $address,
                'email' => $email,
                'phoneNumber' => $phone
            ];

            $userId = $this->getUserId();
            if (!$userId) {
                return response()->json(['error' => true, 'message' => 'Không tìm thấy thông tin người dùng!'], 401);
            }

            $update = $this->user->updateUser($userId, $dataUpdate);
            if ($update === false || $update === 0) {
                return response()->json(['error' => true, 'message' => 'Bạn chưa thay đổi thông tin nào, vui lòng kiểm tra lại!']);
            }
            return response()->json(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
        } catch (Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
    public function changePassword(Request $req)
    {
        $userId = $this->getUserId();
        $user = $this->user->getUser($userId);

        if (md5($req->oldPass) === $user->password) {
            $update = $this->user->updateUser($userId, ['password' => md5($req->newPass)]);
            if (!$update) {
                return response()->json(['error' => true, 'message' => 'Mật khẩu mới trùng với mật khẩu cũ!']);
            } else {
                return response()->json(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);

            }
        } else {
            return response()->json(['error' => true, 'message' => 'Mật khẩu cũ không chính xác.'], 500);
        }
    }

    public function changeAvatar(Request $req)
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return response()->json(['error' => true, 'message' => 'Không tìm thấy thông tin người dùng!'], 401);
            }

            $req->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
            ]);

            // Lấy tệp ảnh
            $avatar = $req->file('avatar');
            if (!$avatar) {
                return response()->json(['error' => true, 'message' => 'Không tìm thấy file ảnh!'], 400);
            }

            // Tạo tên mới cho tệp ảnh
            $filename = time() . '.' . $avatar->getClientOriginalExtension(); // Tên tệp mới theo thời gian

            $user = $this->user->getUser($userId);
            if ($user && $user->avatar) {
                // Đường dẫn đến ảnh cũ
                $oldAvatarPath = public_path('admin/assets/images/user-profile/' . $user->avatar);

                // Kiểm tra tệp cũ có tồn tại và xóa nếu có
                if (file_exists($oldAvatarPath)) {
                    @unlink($oldAvatarPath);
                }
            }

            // Đảm bảo thư mục tồn tại
            $uploadPath = public_path('admin/assets/images/user-profile');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Di chuyển ảnh vào thư mục public/admin/assets/images/user-profile/
            $avatar->move($uploadPath, $filename);
            $update = $this->user->updateUser($userId, ['avatar' => $filename]);
            $req->session()->put('avatar', $filename);
            
            if ($update === false || $update === 0) {
                return response()->json(['error' => true, 'message' => 'Có vấn đề khi cập nhật ảnh!']);
            }
            return response()->json(['success' => true, 'message' => 'Cập nhật ảnh thành công!']);
        } catch (ValidationException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            Log::error('Error updating avatar: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

}
