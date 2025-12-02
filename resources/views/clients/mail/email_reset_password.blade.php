<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #188d09;">Đặt lại mật khẩu - Asia Travel</h2>
        <p>Xin chào,</p>
        <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình.</p>
        <p>Vui lòng click vào link bên dưới để đặt lại mật khẩu:</p>
        <p style="margin: 20px 0;">
            <a href="{{ $link }}" style="background-color: #188d09; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Đặt lại mật khẩu
            </a>
        </p>
        <p>Hoặc copy link sau vào trình duyệt:</p>
        <p style="word-break: break-all; color: #666;">{{ $link }}</p>
        <p><strong>Lưu ý:</strong> Link này sẽ hết hạn sau 1 giờ.</p>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        <p>Trân trọng,<br>Đội ngũ Asia Travel</p>
    </div>
</body>
</html>

