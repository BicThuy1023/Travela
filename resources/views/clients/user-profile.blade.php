@include('clients.blocks.header')
<div class="user-profile">
    <div class="container-xl px-4 mt-4">
        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header">Ảnh đại diện</div>
                    <div class="card-body text-center">
                        <img id="avatarPreview" class="img-account-profile rounded-circle mb-2"
                            src="{{ asset('admin/assets/images/user-profile/' . $user->avatar) }}"
                            style="width:160px; height: 160px; object-fit: cover; display: block; margin: 0 auto;" alt="Ảnh đại diện {{ $user->avatar }}">

                        <div class="small font-italic text-muted mb-4">JPG hoặc PNG không lớn hơn 5 MB</div>
                        <input type="file" name="avatar" id="avatar" style="display: none" accept="image/*">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" class="__token">
                        <input type="hidden" name="" value="{{ route('change-avatar') }}" class="label_avatar">
                        <label for="avatar" class="btn btn-primary">Tải ảnh lên</label>
                    </div>
                </div>

                <div class="card mb-4 mb-xl-0">
                    <button class="btn btn-primary" id="update_password_profile">Đổi mật khẩu </button>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">Thông tin tài khoản</div>
                    <div class="card-body">
                        <form action="{{ route('update-user-profile') }}" method="POST" name="updateUser"
                            class="updateUser">
                            <div class="row gx-3 mb-3">
                                <div class="col-md-12">
                                    <label class="small mb-1" for="inputFullName">Họ và tên</label>
                                    <input class="form-control" id="inputFullName" name="fullName" type="text"
                                        placeholder="Họ và tên" value="{{ $user->fullName }}" required>
                                </div>
                            </div>
                            @csrf
                            <div class="row gx-3 mb-3">
                                <div class="col-md-12">
                                    <label class="small mb-1" for="inputLocation">Địa chỉ</label>
                                    <input class="form-control" id="inputLocation" name="address" type="text" placeholder="Địa chỉ"
                                        value="{{ $user->address }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="small mb-1" for="inputEmailAddress">Email</label>
                                <input class="form-control" id="inputEmailAddress" name="email" type="email" placeholder="Email"
                                    value="{{ $user->email }}" required>
                            </div>
                            <div class="row gx-3 mb-3">
                                <div class="col-md-6">
                                    <label class="small mb-1" for="inputPhone">Phone number</label>
                                    <input class="form-control" id="inputPhone" name="phone" type="number"
                                        placeholder="Số điện thoại" value="{{ $user->phoneNumber }}" required>
                                </div>
                            </div>

                            <button class="btn btn-primary" type="submit" id="update_profile">Lưu thông tin</button>
                        </form>
                    </div>
                </div>
                <div class="card mb-4 ">
                    <div class="card-body" id="card_change_password">
                        <div class="invalid-feedback" style="margin-top:-15px" id="validate_password"></div>
                        <form action="{{ route('change-password') }}" method="post" class="change_password_profile">
                            @csrf
                            <div class="row gx-3">
                                <div class="col-md-4">
                                    <input class="form-control" id="inputOldPass" name="oldPass" type="password"
                                        placeholder="Nhập mật khẩu cũ" value="" required>
                                </div>
                                <div class="col-md-4">
                                    <input class="form-control" id="inputNewPass" name="newPass" type="password"
                                        placeholder="Nhập mật khẩu mới" value="" required>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary" type="submit">Thay đổi</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('clients.blocks.footer')

<script>
$(document).ready(function() {
    let selectedAvatarFile = null; // Lưu file avatar đã chọn
    
    // Xử lý chọn avatar - chỉ preview, không upload
    $('#avatar').on('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            // Validate file
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('File ảnh không được lớn hơn 5MB!', 'Lỗi');
                $(this).val('');
                selectedAvatarFile = null;
                return;
            }
            
            // Lưu file để upload sau
            selectedAvatarFile = file;
            
            // Hiển thị preview bên trái
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').attr('src', e.target.result).css({
                    'width': '160px',
                    'height': '160px',
                    'object-fit': 'cover',
                    'display': 'block',
                    'margin': '0 auto'
                });
            };
            reader.readAsDataURL(file);
            
            toastr.info('Ảnh đã được chọn. Nhấn "Lưu thông tin" để cập nhật.', 'Thông báo', {timeOut: 3000});
        }
    });
    
    // Xử lý form update profile
    $('.updateUser').on('submit', function(e) {
        e.preventDefault();
        
        // Nếu có avatar mới, upload avatar trước
        if (selectedAvatarFile) {
            const avatarFormData = new FormData();
            avatarFormData.append('avatar', selectedAvatarFile);
            avatarFormData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            
            $.ajax({
                url: '{{ route("change-avatar") }}',
                type: 'POST',
                data: avatarFormData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        // Sau khi upload avatar thành công, cập nhật thông tin
                        updateUserInfo();
                    } else {
                        toastr.error(response.message, 'Lỗi');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Có lỗi xảy ra khi upload ảnh!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg, 'Lỗi');
                }
            });
        } else {
            // Không có avatar mới, chỉ cập nhật thông tin
            updateUserInfo();
        }
    });
    
    // Hàm cập nhật thông tin người dùng
    function updateUserInfo() {
        const formData = {
            fullName: $('#inputFullName').val(),
            address: $('#inputLocation').val(),
            email: $('#inputEmailAddress').val(),
            phone: $('#inputPhone').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: $('.updateUser').attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Thành công');
                    // Reset avatar file sau khi lưu thành công
                    selectedAvatarFile = null;
                    $('#avatar').val('');
                    // Redirect về trang chủ sau 1 giây
                    setTimeout(function() {
                        window.location.href = '{{ route("home") }}';
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Lỗi');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Có lỗi xảy ra! Vui lòng thử lại.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Lỗi');
            }
        });
    }
    
    // Xử lý form change password
    $('.change_password_profile').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            oldPass: $('#inputOldPass').val(),
            newPass: $('#inputNewPass').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Thành công');
                    $('#inputOldPass').val('');
                    $('#inputNewPass').val('');
                } else {
                    toastr.error(response.message, 'Lỗi');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Có lỗi xảy ra! Vui lòng thử lại.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Lỗi');
            }
        });
    });
    
    // Toggle form đổi mật khẩu
    $('#update_password_profile').on('click', function() {
        $('#card_change_password').slideToggle();
    });
});
</script>
