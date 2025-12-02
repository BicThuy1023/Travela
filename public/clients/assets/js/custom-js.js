// User Dropdown Menu Toggle
$(document).ready(function() {
    $('#userDropdown').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $dropdown = $('#dropdownMenu');
        if ($dropdown.is(':visible')) {
            $dropdown.slideUp(200);
        } else {
            $('.dropdown-menu').not($dropdown).slideUp(200);
            $dropdown.slideDown(200);
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.menu-sidebar').length) {
            $('#dropdownMenu').slideUp(200);
        }
    });

    $('#dropdownMenu').on('click', function(e) {
        e.stopPropagation();
    });

    $('#dropdownMenu a').on('click', function() {
        $('#dropdownMenu').slideUp(200);
    });

    $('#sign-up').on('click', function(e) {
        e.preventDefault();
        $('.sign-in').removeClass('show').fadeOut(300, function() {
            $('.signup').addClass('show').fadeIn(300);
        });
    });

    $('#sign-in').on('click', function(e) {
        e.preventDefault();
        $('.signup').removeClass('show').fadeOut(300, function() {
            $('.sign-in').addClass('show').fadeIn(300);
        });
    });

    $(document).on('click', '#forgot-password-link', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.sign-in').removeClass('show').fadeOut(300, function() {
            $('.forgot-password').addClass('show').fadeIn(300);
        });
    });

    $(document).on('click', '#back-to-login', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.forgot-password').removeClass('show').fadeOut(300, function() {
            $('.sign-in').addClass('show').fadeIn(300);
        });
    });

    $(document).on('submit', '#forgot-password-form', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var email = $('#email_forgot').val();
        if (!email || !email.includes('@')) {
            toastr.error('Vui lòng nhập email hợp lệ!', 'Lỗi');
            return;
        }
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Thành công');
                    $('#forgot-password-form')[0].reset();
                    setTimeout(function() {
                        $('.forgot-password').removeClass('show').fadeOut(300, function() {
                            $('.sign-in').addClass('show').fadeIn(300);
                        });
                    }, 2000);
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

    // Handle login form submit with AJAX
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        var username = $('#username_login').val();
        var password = $('#password_login').val();
        
        if (!username || !password) {
            toastr.error('Vui lòng nhập đầy đủ thông tin!', 'Lỗi');
            return;
        }
        
        var formData = {
            username: username,
            password: password,
            _token: $('input[name="_token"]').val()
        };
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Thành công');
                    setTimeout(function() {
                        if (response.redirectUrl) {
                            window.location.href = response.redirectUrl;
                        } else {
                            window.location.href = '/';
                        }
                    }, 500);
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