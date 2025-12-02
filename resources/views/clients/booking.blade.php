@include('clients.blocks.header')
@include('clients.blocks.banner')

<section class="container" style="margin-top:50px; margin-bottom: 100px">
    {{-- <h1 class="text-center booking-header">Tổng Quan Về Chuyến Đi</h1> --}}

    <form action="{{ route('create-booking') }}" method="post" class="booking-container">
        @csrf
        <!-- Contact Information -->
        <div class="booking-info">
            <h2 class="booking-header">Thông Tin Liên Lạc</h2>
            <div class="booking__infor">
                <div class="form-group">
                    <label for="username">Họ và tên*</label>
                    <input type="text" id="username" placeholder="Nhập Họ và tên" name="fullName" required
                        value="{{ old('fullName', $user->fullName ?? '') }}">
                    <span class="error-message" id="usernameError"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" placeholder="sample@gmail.com" name="email" required
                        value="{{ old('email', $user->email ?? '') }}">
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="tel">Số điện thoại*</label>
                    <input type="number" id="tel" placeholder="Nhập số điện thoại liên hệ" name="tel" required
                        value="{{ old('tel', $user->phoneNumber ?? '') }}">
                    <span class="error-message" id="telError"></span>
                </div>

                <div class="form-group">
                    <label for="address">Địa chỉ*</label>
                    <input type="text" id="address" placeholder="Nhập địa chỉ liên hệ" name="address" required
                        value="{{ old('address', $user->address ?? '') }}">
                    <span class="error-message" id="addressError"></span>
                </div>

            </div>


            <!-- Passenger Details -->
            <h2 class="booking-header">Hành Khách</h2>

            {{-- Hidden inputs để JS đọc giá --}}
            <input type="hidden" id="adultPrice" value="{{ $adultPrice }}">
            <input type="hidden" id="childPrice" value="{{ $childPrice }}">

            <div class="booking__quantity">
                <div class="form-group quantity-selector">
                    <label>Người lớn</label>
                    <div class="input__quanlity">
                        <button type="button" class="quantity-btn" data-action="decrease" data-target="numAdults">-</button>
                        <input type="number" class="quantity-input" value="{{ $adults }}" min="1" id="numAdults" name="numAdults">
                        <button type="button" class="quantity-btn" data-action="increase" data-target="numAdults">+</button>
                    </div>
                </div>

                <div class="form-group quantity-selector">
                    <label>Trẻ em</label>
                    <div class="input__quanlity">
                        <button type="button" class="quantity-btn" data-action="decrease" data-target="numChildren">-</button>
                        <input type="number" class="quantity-input" value="{{ $children }}" min="0" id="numChildren" name="numChildren">
                        <button type="button" class="quantity-btn" data-action="increase" data-target="numChildren">+</button>
                    </div>
                </div>
            </div>
            <!-- Privacy Agreement Section -->
            <div class="privacy-section">
                <p>Bằng cách nhấp chuột vào nút "ĐỒNG Ý" dưới đây, Khách hàng đồng ý rằng các điều kiện điều khoản
                    này sẽ được áp dụng. Vui lòng đọc kỹ điều kiện điều khoản trước khi lựa chọn sử dụng dịch vụ của
                    Asia Travel.</p>
                <div class="privacy-checkbox">
                    <input type="checkbox" id="agree" name="agree" required>
                    <label for="agree">Tôi đã đọc và đồng ý với <a href="#" target="_blank">Điều khoản thanh
                            toán</a></label>
                </div>
            </div>
            <!-- Payment Method -->
            <h2 class="booking-header">Phương Thức Thanh Toán</h2>

            <label class="payment-option">
                <input type="radio" name="payment" value="office-payment" required>
                <img src="{{ asset('clients/assets/images/contact/icon.png') }}" alt="Office Payment">
                Thanh toán tại văn phòng
            </label>

            <label class="payment-option">
                <input type="radio" name="payment" value="paypal-payment" required>
                <img src="{{ asset('clients/assets/images/booking/cong-thanh-toan-paypal.jpg') }}" alt="PayPal">
                Thanh toán bằng PayPal
            </label>

            <label class="payment-option">
                <input type="radio" name="payment" value="momo-payment" required>
                <img src="{{ asset('clients/assets/images/booking/thanh-toan-momo.jpg') }}" alt="MoMo">
                Thanh toán bằng Momo
                @if (!is_null($transIdMomo))
                    <input type="hidden" name="transactionIdMomo" value="{{ $transIdMomo }}">
                @endif
            </label>

            {{-- Hidden input để lưu phương thức thanh toán được chọn --}}
            <input type="hidden" name="payment_hidden" id="payment_hidden">
        </div>

        <!-- Order Summary -->
        <div class="booking-summary">
            <div class="summary-section">
                <div>
                    <p>Mã tour : {{ $tour->tourId }}</p>
                    <input type="hidden" name="tourId" id="tourId" value="{{ $tour->tourId }}">
                    <h5 class="widget-title">{{ $tour->title }}</h5>
                    <p>Ngày khởi hành : {{ date('d-m-Y', strtotime($tour->startDate)) }}</p>
                    <p>Ngày kết thúc : {{ date('d-m-Y', strtotime($tour->endDate)) }}</p>
                    <p class="quantityAvailable">Số chỗ còn nhận : {{ $tour->quantity }}</p>
                </div>

                <div class="order-summary">
                    <div class="summary-item">
                        <span>Người lớn:</span>
                        <div>
                            <span class="quantity__adults">{{ $adults }}</span>
                            <span> × </span>
                            <span class="adult-price-display">{{ number_format($adultPrice, 0, ',', '.') }} VNĐ</span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <span>Trẻ em:</span>
                        <div>
                            <span class="quantity__children">{{ $children }}</span>
                            <span> × </span>
                            <span class="child-price-display">{{ number_format($childPrice, 0, ',', '.') }} VNĐ</span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <span>Giảm giá:</span>
                        <div>
                            <span class="discount-amount">0 VNĐ</span>
                        </div>
                    </div>
                    <div class="summary-item total-price">
                        <span>Tổng cộng:</span>
                        <span id="totalPriceDisplay">{{ number_format($totalPrice, 0, ',', '.') }} VNĐ</span>
                        <input type="hidden" id="totalPrice" name="totalPrice" value="{{ $totalPrice }}">
                    </div>
                </div>
                <div class="order-coupon">
                    <input type="text" placeholder="Mã giảm giá" style="width: 65%;">
                    <button style="width: 30%" class="booking-btn btn-coupon">Áp dụng</button>
                </div>

                <div id="paypal-button-container"></div>

                <button type="submit" class="booking-btn btn-submit-booking">Xác Nhận</button>

                <button id="btn-momo-payment" class="booking-btn" style="display: none;"
                    data-urlmomo="{{ route('createMomoPayment') }}">Thanh toán với Momo <img
                        src="{{ asset('clients/assets/images/booking/icon-thanh-toan-momo.png') }}" alt=""
                        style="width: 10%"></button>

            </div>
        </div>
    </form>
</section>

{{-- JavaScript tính toán giá --}}
<script>
(function() {
    'use strict';

    // Lấy các elements
    const adultPriceInput = document.getElementById('adultPrice');
    const childPriceInput = document.getElementById('childPrice');
    const numAdultsInput = document.getElementById('numAdults');
    const numChildrenInput = document.getElementById('numChildren');
    const quantityAdultsSpan = document.querySelector('.quantity__adults');
    const quantityChildrenSpan = document.querySelector('.quantity__children');
    const totalPriceDisplay = document.getElementById('totalPriceDisplay');
    const totalPriceHidden = document.getElementById('totalPrice');
    const quantityButtons = document.querySelectorAll('.quantity-btn');

    // Kiểm tra elements tồn tại
    if (!adultPriceInput || !childPriceInput || !numAdultsInput || !numChildrenInput) {
        console.error('Không tìm thấy các elements cần thiết');
        return;
    }

    // Lấy giá từ hidden inputs
    const adultPrice = parseInt(adultPriceInput.value) || 0;
    const childPrice = parseInt(childPriceInput.value) || 0;

    /**
     * Format số tiền theo định dạng Việt Nam
     */
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
    }

    /**
     * Tính và cập nhật tổng tiền
     */
    function updateTotalPrice() {
        const adults = parseInt(numAdultsInput.value) || 0;
        const children = parseInt(numChildrenInput.value) || 0;

        // Tính tổng tiền
        const totalPrice = (adults * adultPrice) + (children * childPrice);

        // Cập nhật hiển thị
        if (quantityAdultsSpan) {
            quantityAdultsSpan.textContent = adults;
        }
        if (quantityChildrenSpan) {
            quantityChildrenSpan.textContent = children;
        }
        if (totalPriceDisplay) {
            totalPriceDisplay.textContent = formatPrice(totalPrice);
        }
        if (totalPriceHidden) {
            totalPriceHidden.value = totalPrice;
        }
    }

    /**
     * Xử lý nút +/-
     */
    quantityButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const target = this.getAttribute('data-target');
            const input = document.getElementById(target);

            if (!input) return;

            let currentValue = parseInt(input.value) || 0;
            const min = parseInt(input.getAttribute('min')) || 0;

            if (action === 'increase') {
                currentValue++;
            } else if (action === 'decrease') {
                currentValue = Math.max(min, currentValue - 1);
            }

            input.value = currentValue;
            updateTotalPrice();
        });
    });

    /**
     * Xử lý khi người dùng nhập trực tiếp vào input
     */
    numAdultsInput.addEventListener('input', function() {
        let value = parseInt(this.value) || 1;
        if (value < 1) value = 1;
        this.value = value;
        updateTotalPrice();
    });

    numChildrenInput.addEventListener('input', function() {
        let value = parseInt(this.value) || 0;
        if (value < 0) value = 0;
        this.value = value;
        updateTotalPrice();
    });

    /**
     * Xử lý khi blur (rời khỏi input)
     */
    numAdultsInput.addEventListener('blur', function() {
        let value = parseInt(this.value) || 1;
        if (value < 1) {
            value = 1;
            this.value = value;
        }
        updateTotalPrice();
    });

    numChildrenInput.addEventListener('blur', function() {
        let value = parseInt(this.value) || 0;
        if (value < 0) {
            value = 0;
            this.value = value;
        }
        updateTotalPrice();
    });

    // Khởi tạo giá ban đầu
    updateTotalPrice();

    /**
     * Xử lý phương thức thanh toán - set giá trị vào hidden input
     */
    const paymentRadios = document.querySelectorAll('input[name="payment"]');
    const paymentHidden = document.getElementById('payment_hidden');

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked && paymentHidden) {
                paymentHidden.value = this.value;
            }
        });
    });

    // Set giá trị mặc định nếu có radio được chọn sẵn
    const checkedPayment = document.querySelector('input[name="payment"]:checked');
    if (checkedPayment && paymentHidden) {
        paymentHidden.value = checkedPayment.value;
    }
})();
</script>

@include('clients.blocks.footer')