@include('clients.blocks.header')

<div class="login-template">
    <div class="main">
        <!-- Reset Password Form -->
        <section class="sign-in show">
            <div class="container">
                <div class="signin-content">
                    <div class="signin-image">
                        <figure><img src="{{ asset('clients/assets/images/login/signin-image.jpg') }}"
                                alt="reset password image"></figure>
                        <a href="{{ route('login') }}" class="signup-image-link">Quay lại đăng nhập</a>
                    </div>

                    <div class="signin-form">
                        <h2 class="form-title">Đặt lại mật khẩu</h2>
                        <form action="{{ route('reset-password') }}" method="POST" class="login-form" id="reset-password-form" style="margin-top: 15px">
                            <input type="hidden" name="token" value="{{ $token }}">
                            @csrf
                            <div class="form-group">
                                <label for="password_reset"><i class="zmdi zmdi-lock"></i></label>
                                <input type="password" name="password" id="password_reset" placeholder="Mật khẩu mới" required/>
                            </div>
                            <div class="invalid-feedback" style="margin-top:-15px" id="validate_password_reset"></div>
                            <div class="form-group">
                                <label for="password_confirm_reset"><i class="zmdi zmdi-lock-outline"></i></label>
                                <input type="password" name="password_confirm" id="password_confirm_reset" placeholder="Xác nhận mật khẩu mới" required/>
                            </div>
                            <div class="invalid-feedback" style="margin-top:-15px" id="validate_password_confirm_reset"></div>
                            <div class="form-group form-button">
                                <input type="submit" name="submit_reset" id="submit_reset" class="form-submit"
                                    value="Đặt lại mật khẩu" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

@include('clients.blocks.footer')

<script>
$(document).ready(function() {
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var password = $('#password_reset').val();
        var passwordConfirm = $('#password_confirm_reset').val();
        
        // Validate
        if (password.length < 6) {
            toastr.error('Mật khẩu phải có ít nhất 6 ký tự!', 'Lỗi');
            return;
        }
        
        if (password !== passwordConfirm) {
            toastr.error('Mật khẩu xác nhận không khớp!', 'Lỗi');
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Thành công');
                    setTimeout(function() {
                        window.location.href = response.redirectUrl;
                    }, 1500);
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
});
</script>

