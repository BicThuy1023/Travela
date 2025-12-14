{{-- resources/views/clients/build_tour_checkout.blade.php --}}

@include('clients.blocks.header')
@include('clients.blocks.banner', ['title' => $title ?? 'Đặt tour theo yêu cầu'])

<section class="container" style="margin-top:50px; margin-bottom: 100px">

    <form action="{{ route('custom-tours.checkout.submit', ['id' => $customTourId]) }}" method="POST"
        class="booking-container">

        @csrf

        {{-- ======================== CỘT TRÁI ======================== --}}
        <div class="booking-info">
            <h2 class="booking-header">Thông tin liên hệ</h2>

            {{-- Flash message --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">
                    <strong>Lỗi thanh toán:</strong> {{ session('error') }}
                    @if (session('momo_result_code'))
                        <br><small class="mt-1 d-block">
                            Mã lỗi: {{ session('momo_result_code') }}
                            @if (session('momo_message'))
                                | Chi tiết: {{ session('momo_message') }}
                            @endif
                        </small>
                    @endif
                    @if (stripos(session('error'), 'từ chối') !== false || stripos(session('error'), 'issuer') !== false)
                        <div class="mt-2 p-2 bg-light rounded">
                            <strong>Gợi ý xử lý:</strong>
                            <ul class="mb-0 mt-1" style="font-size: 13px;">
                                <li>Kiểm tra lại thông tin thẻ (số thẻ, ngày hết hạn, tên chủ thẻ)</li>
                                <li>Đảm bảo thẻ còn hiệu lực và chưa bị khóa</li>
                                <li>Kiểm tra số dư tài khoản có đủ để thanh toán</li>
                                <li>Liên hệ ngân hàng phát hành thẻ để kiểm tra</li>
                                <li>Thử lại với thẻ khác hoặc phương thức thanh toán khác</li>
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-info">
                    <strong>Thông báo:</strong> {{ session('info') }}
                    @if (session('momo_status') == 'pending')
                        <br><small class="mt-2 d-block">
                            <strong>Lưu ý:</strong> Nếu tài khoản ngân hàng của bạn đã bị trừ tiền nhưng giao dịch không thành công, 
                            tiền sẽ được hoàn lại vào ví MoMo trong tối đa 48 giờ (trừ thứ 7, chủ nhật, ngày lễ).
                        </small>
                    @endif
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="booking__infor">
                <div class="form-group">
                    <label for="username">Họ và tên*</label>
                    <input type="text" id="username" name="full_name" required placeholder="Nhập họ và tên"
                        value="{{ old('full_name', $user->fullName ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" required placeholder="sample@gmail.com"
                        value="{{ old('email', $user->email ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại*</label>
                    <input type="text" id="phone" name="phone" required placeholder="Nhập số điện thoại liên hệ"
                        value="{{ old('phone', $user->phoneNumber ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="address">Địa chỉ (không bắt buộc)</label>
                    <input type="text" id="address" name="address" placeholder="Ví dụ: Thủ Dầu Một, Bình Dương"
                        value="{{ old('address', $user->address ?? '') }}">
                </div>
            </div>

            {{-- Ghi chú --}}
            <div class="booking-notes">
                <h2 class="booking-header">Ghi chú cho tư vấn viên</h2>
                <textarea name="note" rows="4"
                    placeholder="Ví dụ: muốn thêm 1 bữa hải sản, yêu cầu ăn chay...">{{ old('note') }}</textarea>

                <p class="mt-3" style="font-size: 13px; color:#4b5563;">
                    Bằng cách nhấp chuột vào nút <strong>“Đặt tour ngay”</strong>, khách hàng đồng ý điều khoản dịch vụ.
                </p>

                <div class="privacy-checkbox">
                    <input type="checkbox" id="agree" name="agree" required>
                    <label for="agree">
                        Tôi đã đọc và đồng ý với
                        <a href="#" target="_blank">Điều khoản đặt dịch vụ</a>
                    </label>
                </div>
            </div>

            {{-- Thanh toán --}}
            <h2 class="booking-header">Phương thức thanh toán</h2>

            <label class="payment-option">
                <input type="radio" name="payment" value="office-payment">
                <img src="{{ asset('clients/assets/images/contact/icon.png') }}" alt="Office Payment">
                Thanh toán tại văn phòng
            </label>

            <label class="payment-option">
                <input type="radio" name="payment" value="paypal-payment">
                <img src="{{ asset('clients/assets/images/booking/cong-thanh-toan-paypal.jpg') }}" alt="PayPal">
                Thanh toán bằng PayPal
            </label>

            <label class="payment-option">
                <input type="radio" name="payment" value="momo-payment">
                <img src="{{ asset('clients/assets/images/booking/thanh-toan-momo.jpg') }}" alt="MoMo">
                Thanh toán bằng MoMo
            </label>

        </div>

        {{-- ======================== CỘT PHẢI ======================== --}}
        <div class="booking-summary">
            <div class="summary-section">

                {{-- THÔNG TIN TOUR --}}
                <div>
                    <p>Mã phương án: {{ $chosenTour['code'] ?? 'Tùy chọn' }}</p>

                    <h5 class="widget-title">{{ $chosenTour['title'] ?? 'Tour theo yêu cầu đã chọn' }}</h5>

                    <p>Điểm đến: {{ $chosenTour['destination'] ?? 'Đang cập nhật' }}</p>

                    <p>
                        Ngày khởi hành:
                        <strong>{{ $chosenTour['start_date'] ?? 'Đang thỏa thuận' }}</strong>
                    </p>

                    <p>
                        Ngày kết thúc:
                        <strong>{{ $chosenTour['end_date'] ?? 'Đang thỏa thuận' }}</strong>
                    </p>

                    <p>
                        Số khách:
                        @php
                            $totalPeopleChosen = $chosenTour['total_people']
                                ?? (($chosenTour['adults'] ?? 0) + ($chosenTour['children'] ?? 0));
                        @endphp
                        <strong>{{ $totalPeopleChosen }} khách</strong>
                    </p>
                </div>

                {{-- ===================== GIÁ ===================== --}}
                @php
                    // Lấy priceSummary nếu controller không truyền
                    if (!isset($priceSummary) || empty($priceSummary)) {
                        $priceSummary = $chosenTour['price_breakdown'] ?? [];
                    }

                    // Số khách
                    $adults = (int) ($chosenTour['adults'] ?? ($requestData['adults'] ?? 0));
                    $children = (int) ($chosenTour['children'] ?? ($requestData['children'] ?? 0));
                    $totalPeople = max($adults + $children, 1);

                    // Giá / người lớn & trẻ em (đã giảm đoàn)
                    // BẮT BUỘC lấy từ breakdown trước để đồng nhất với các trang khác
                    $adultPrice = (isset($priceSummary['adult_price']) && $priceSummary['adult_price'] !== null && $priceSummary['adult_price'] !== '')
                        ? (int) $priceSummary['adult_price']
                        : (int) ($chosenTour['price_per_adult'] ?? ($chosenTour['price_per_person'] ?? 0));

                    $childPrice = (isset($priceSummary['child_price']) && $priceSummary['child_price'] !== null && $priceSummary['child_price'] !== '')
                        ? (int) $priceSummary['child_price']
                        : (int) ($chosenTour['price_per_child'] ?? (
                            // fallback: 75% giá người lớn nếu không có
                            (int) round($adultPrice * ($priceSummary['child_factor'] ?? 0.75) / 1000) * 1000
                        ));

                    // Tổng giá sau giảm (tổng cộng tour)
                    // BẮT BUỘC lấy final_total_price từ breakdown (đã được tính sẵn trong controller)
                    // Đây là giá chính xác nhất, không tính lại để tránh sai lệch (đồng nhất với result và detail)
                    $totalPrice = (isset($priceSummary['final_total_price']) && $priceSummary['final_total_price'] !== null && $priceSummary['final_total_price'] !== '')
                        ? (int) $priceSummary['final_total_price']
                        : (int) ($chosenTour['total_price'] ?? 0);

                    // Tổng giá CHƯA giảm đoàn (backend đã tính sẵn)
                    $undiscountedTotal = (int) ($priceSummary['undiscounted_total'] ?? 0);

                    // Tổng tiền ưu đãi tour đoàn cho cả đoàn
                    $discountAmount = (int) (
                        $priceSummary['discount_amount_total']
                        ?? ($undiscountedTotal > 0 ? max(0, $undiscountedTotal - $totalPrice) : 0)
                    );

                    // % giảm / khách để hiển thị text
                    $groupDiscountPercent = (int) ($priceSummary['group_discount_percent'] ?? 0);
                @endphp


                <div class="order-summary">

                    <div class="summary-item">
                        <span>Người lớn:</span>
                        <div>
                            <span>{{ $adults }} x</span>
                            <span class="summary-price-text">
                                {{ number_format($adultPrice, 0, ',', '.') }} VNĐ
                            </span>
                        </div>
                    </div>

                    <div class="summary-item">
                        <span>Trẻ em:</span>
                        <div>
                            <span>{{ $children }} x</span>
                            <span class="summary-price-text">
                                {{ number_format($childPrice, 0, ',', '.') }} VNĐ
                            </span>
                        </div>
                    </div>

                    @if($discountAmount > 0 && $groupDiscountPercent > 0)
                        <div class="summary-item">
                            <span>Giảm giá (ưu đãi tour đoàn):</span>
                            <div>
                                <span class="summary-price-text">
                                    - {{ number_format($discountAmount, 0, ',', '.') }} VNĐ
                                </span>
                            </div>
                        </div>
                    @endif

                    @php
                        $optionalActivitiesTotal = (int) ($priceSummary['optional_activities_total'] ?? ($chosenTour['optional_activities_total'] ?? 0));
                    @endphp

                    @if($optionalActivitiesTotal > 0)
                        <div class="summary-item">
                            <span>Hoạt động tự túc đã chọn:</span>
                            <div>
                                <span class="summary-price-text">
                                    + {{ number_format($optionalActivitiesTotal, 0, ',', '.') }} VNĐ
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="summary-item summary-total-line">
                        <span>Tổng cộng:</span>
                        <span class="summary-price-text">
                            {{ number_format($totalPrice, 0, ',', '.') }} VNĐ
                        </span>
                        <input type="hidden" name="totalPrice" value="{{ $totalPrice }}">
                        <input type="hidden" name="customTourId" value="{{ $customTourId }}">
                    </div>

                    @if(($priceSummary['group_discount_percent'] ?? 0) > 0)
                        <p class="text-muted small mt-1">
                            Đã áp dụng ưu đãi tour đoàn: giảm {{ $priceSummary['group_discount_percent'] }}% / khách.
                        </p>
                    @endif

                </div>

                <button type="submit" class="booking-btn btn-submit-booking" id="btn-submit-booking">
                    Đặt tour ngay
                </button>

                <button id="btn-momo-payment" class="booking-btn" style="display: none;"
                    data-urlmomo="{{ route('createMomoPayment') }}"
                    data-custom-tour-id="{{ $customTourId }}">
                    Thanh toán với Momo <img
                        src="{{ asset('clients/assets/images/booking/icon-thanh-toan-momo.png') }}" alt=""
                        style="width: 10%">
                </button>


            </div>
        </div>
    </form>
</section>

<script>
// Đảm bảo jQuery đã được load trước khi chạy script
(function() {
    function initMomoPayment() {
        // Kiểm tra jQuery đã load chưa
        if (typeof jQuery === 'undefined') {
            setTimeout(initMomoPayment, 100);
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            // Unbind event cũ từ custom-js.js để tránh conflict
            $("#btn-momo-payment").off('click');
            
            // Ẩn/hiện nút MoMo khi chọn phương thức thanh toán
            $('input[name="payment"]').off('change').on('change', function() {
                var paymentMethod = $(this).val();
                if (paymentMethod === "momo-payment") {
                    $("#btn-momo-payment").show();
                    $("#btn-submit-booking").hide();
                } else {
                    $("#btn-momo-payment").hide();
                    $("#btn-submit-booking").show();
                }
            });

            // Xử lý click nút MoMo payment
            $("#btn-momo-payment").on('click', function(e) {
                e.preventDefault();
                var urlMomo = $(this).data("urlmomo");
                var customTourId = $(this).data("custom-tour-id");
                var totalPrice = $('input[name="totalPrice"]').val();

                // Validate form - sử dụng selector đúng với form checkout custom tour
                var isValid = true;
                var username = $('#username').val();
                var email = $('#email').val();
                var phone = $('#phone').val();
                
                if (!username || username.trim() === '') {
                    isValid = false;
                    alert('Vui lòng điền họ và tên');
                    return false;
                }
                
                if (!email || email.trim() === '') {
                    isValid = false;
                    alert('Vui lòng điền email');
                    return false;
                }
                
                // Validate email format
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    isValid = false;
                    alert('Email không đúng định dạng');
                    return false;
                }
                
                if (!phone || phone.trim() === '') {
                    isValid = false;
                    alert('Vui lòng điền số điện thoại');
                    return false;
                }
                
                // Validate phone format (10-11 digits)
                var phonePattern = /^[0-9]{10,11}$/;
                if (!phonePattern.test(phone.trim())) {
                    isValid = false;
                    alert('Số điện thoại phải có 10-11 chữ số');
                    return false;
                }

                if (!$('#agree').is(':checked')) {
                    isValid = false;
                    alert('Vui lòng đồng ý với điều khoản dịch vụ');
                    return false;
                }

                if (!totalPrice || totalPrice <= 0) {
                    isValid = false;
                    alert('Giá tour không hợp lệ');
                    return false;
                }

                if (isValid) {
                    // Disable button để tránh double click
                    $(this).prop('disabled', true).text('Đang xử lý...');
                    
                    // Thu thập thông tin form để lưu vào session
                    var formData = {
                        full_name: username,
                        email: email,
                        phone: phone,
                        address: $('#address').val() || '',
                        note: $('textarea[name="note"]').val() || ''
                    };
                    
                    $.ajax({
                        url: urlMomo,
                        method: "POST",
                        data: {
                            amount: totalPrice,
                            customTourId: customTourId,
                            form_data: JSON.stringify(formData), // Gửi thông tin form để lưu vào session
                            _token: $('input[name="_token"]').val(),
                        },
                        success: function(response) {
                            if (response && response.payUrl) {
                                // Chuyển hướng đến URL thanh toán MoMo
                                window.location.href = response.payUrl;
                            } else {
                                $("#btn-momo-payment").prop('disabled', false).html('Thanh toán với Momo <img src="{{ asset('clients/assets/images/booking/icon-thanh-toan-momo.png') }}" alt="" style="width: 10%">');
                                alert("Không thể tạo thanh toán MoMo. Vui lòng thử lại.");
                            }
                        },
                        error: function(xhr) {
                            $("#btn-momo-payment").prop('disabled', false).html('Thanh toán với Momo <img src="{{ asset('clients/assets/images/booking/icon-thanh-toan-momo.png') }}" alt="" style="width: 10%">');
                            var errorMsg = "Có lỗi xảy ra khi kết nối đến MoMo.";
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMsg = xhr.responseJSON.error;
                            }
                            alert(errorMsg);
                        },
                    });
                }
                
                return false;
            });
        });
        
        // Xử lý nút TEST thanh toán thành công
        $('#btn-test-payment-success').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Bạn có chắc muốn giả lập thanh toán thành công? (TEST MODE)\n\nBooking sẽ được tự động lưu và hiển thị trong "Tour đã đặt".')) {
                return false;
            }
            
            // Validate form trước
            var isValid = true;
            var username = $('#username').val();
            var email = $('#email').val();
            var phone = $('#phone').val();
            
            if (!username || username.trim() === '') {
                alert('Vui lòng điền họ và tên');
                return false;
            }
            
            if (!email || email.trim() === '') {
                alert('Vui lòng điền email');
                return false;
            }
            
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Email không đúng định dạng');
                return false;
            }
            
            if (!phone || phone.trim() === '') {
                alert('Vui lòng điền số điện thoại');
                return false;
            }
            
            var phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(phone.trim())) {
                alert('Số điện thoại phải có 10-11 chữ số');
                return false;
            }
            
            if (!$('#agree').is(':checked')) {
                alert('Vui lòng đồng ý với điều khoản dịch vụ');
                return false;
            }
            
            // Disable button để tránh double click
            $(this).prop('disabled', true).text('Đang xử lý...');
            
            // Lưu thông tin form vào session trước khi test
            var formData = {
                full_name: username,
                email: email,
                phone: phone,
                address: $('#address').val() || '',
                note: $('textarea[name="note"]').val() || ''
            };
            
            var customTourId = $('#btn-momo-payment').data('custom-tour-id');
            var totalPrice = $('input[name="totalPrice"]').val();
            
            // Gửi AJAX để lưu form data vào session
            $.ajax({
                url: '{{ route("createMomoPayment") }}',
                method: "POST",
                data: {
                    amount: totalPrice,
                    customTourId: customTourId,
                    form_data: JSON.stringify(formData),
                    _token: $('input[name="_token"]').val(),
                    test_mode: true // Flag để chỉ lưu session, không tạo payment
                },
                success: function(response) {
                    // Sau khi lưu session thành công, redirect đến route test
                    window.location.href = '{{ route("test.momo.success", ["customTourId" => ":customTourId"]) }}'.replace(':customTourId', customTourId);
                },
                error: function(xhr) {
                    // Nếu lỗi, vẫn thử redirect đến route test (có thể session đã được lưu)
                    console.log('Error saving form data:', xhr);
                    window.location.href = '{{ route("test.momo.success", ["customTourId" => ":customTourId"]) }}'.replace(':customTourId', customTourId);
                }
            });
            
            return false;
        });
    }
    
    // Khởi tạo khi DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMomoPayment);
    } else {
        initMomoPayment();
    }
})();
</script>

@include('clients.blocks.footer')