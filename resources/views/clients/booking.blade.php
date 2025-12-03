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
                    <input type="checkbox" id="agreeTerms" name="agree_terms" required>
                    <label for="agreeTerms">Tôi đã đọc và đồng ý với <a href="#" target="_blank">Điều khoản thanh
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

                <button type="submit" class="booking-btn btn-submit-booking" id="btn-submit-booking">Xác Nhận</button>

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

    /**
     * Xử lý hiển thị/ẩn nút MoMo payment và nút Xác Nhận
     */
    const btnMomoPayment = document.getElementById('btn-momo-payment');
    const btnSubmitBooking = document.getElementById('btn-submit-booking');
    const paymentRadiosForMomo = document.querySelectorAll('input[name="payment"]');
    
    paymentRadiosForMomo.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'momo-payment') {
                // Hiển thị nút MoMo, ẩn nút Xác Nhận
                if (btnMomoPayment) {
                    btnMomoPayment.style.display = 'block';
                }
                if (btnSubmitBooking) {
                    btnSubmitBooking.style.display = 'none';
                }
            } else {
                // Ẩn nút MoMo, hiển thị nút Xác Nhận
                if (btnMomoPayment) {
                    btnMomoPayment.style.display = 'none';
                }
                if (btnSubmitBooking) {
                    btnSubmitBooking.style.display = 'block';
                }
            }
        });
    });

    // Kiểm tra radio mặc định
    if (checkedPayment) {
        if (checkedPayment.value === 'momo-payment') {
            if (btnMomoPayment) {
                btnMomoPayment.style.display = 'block';
            }
            if (btnSubmitBooking) {
                btnSubmitBooking.style.display = 'none';
            }
        } else {
            if (btnMomoPayment) {
                btnMomoPayment.style.display = 'none';
            }
            if (btnSubmitBooking) {
                btnSubmitBooking.style.display = 'block';
            }
        }
    }

    /**
     * Xử lý click nút MoMo payment
     */
    if (btnMomoPayment) {
        btnMomoPayment.addEventListener('click', function(e) {
            e.preventDefault();
            
            const urlMomo = this.getAttribute('data-urlmomo');
            const tourIdInput = document.getElementById('tourId');
            const totalPriceInput = document.getElementById('totalPrice');
            const fullNameInput = document.getElementById('username'); // ID là 'username' nhưng name là 'fullName'
            const emailInput = document.getElementById('email');
            const telInput = document.getElementById('tel');
            const addressInput = document.getElementById('address');
            const numAdultsInput = document.getElementById('numAdults');
            const numChildrenInput = document.getElementById('numChildren');
            
            if (!tourIdInput || !totalPriceInput || !fullNameInput || !emailInput || !telInput) {
                alert('Vui lòng điền đầy đủ thông tin!');
                return false;
            }
            
            const tourId = tourIdInput.value;
            const totalPrice = totalPriceInput.value;
            const fullName = fullNameInput.value;
            const email = emailInput.value;
            const tel = telInput.value;
            const address = addressInput ? addressInput.value : '';
            const numAdults = numAdultsInput ? numAdultsInput.value : '1';
            const numChildren = numChildrenInput ? numChildrenInput.value : '0';

            // Validate form
            if (!fullName || fullName.trim() === '') {
                alert('Vui lòng điền họ và tên');
                return false;
            }
            
            if (!email || email.trim() === '') {
                alert('Vui lòng điền email');
                return false;
            }
            
            // Validate email format
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Email không đúng định dạng');
                return false;
            }
            
            if (!tel || tel.trim() === '') {
                alert('Vui lòng điền số điện thoại');
                return false;
            }
            
            // Validate phone format (10-11 digits)
            const phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(tel.trim())) {
                alert('Số điện thoại phải có 10-11 chữ số');
                return false;
            }

            if (!totalPrice || totalPrice <= 0) {
                alert('Giá tour không hợp lệ');
                return false;
            }

            // Kiểm tra checkbox "Đồng ý điều khoản"
            const agreeCheckbox = document.getElementById('agreeTerms');
            if (!agreeCheckbox || !agreeCheckbox.checked) {
                alert('Vui lòng đọc và đồng ý với Điều khoản thanh toán trước khi tiếp tục.');
                return false;
            }

            // Disable button để tránh double click
            this.disabled = true;
            this.textContent = 'Đang xử lý...';
            
            // Thu thập thông tin form để lưu vào session
            const formData = {
                full_name: fullName,
                email: email,
                phone: tel,
                address: address || '',
                numAdults: numAdults,
                numChildren: numChildren
            };
            
            // Gửi request đến createMomoPayment
            const requestData = {
                amount: totalPrice,
                tourId: tourId,
                form_data: JSON.stringify(formData),
                _token: document.querySelector('input[name="_token"]')?.value
            };
            
            console.log('MoMo Payment Request:', requestData);
            
            fetch(urlMomo, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('MoMo Payment Response Status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('MoMo Payment Response Data:', data);
                
                // Kiểm tra success flag và payUrl
                if (data && data.success === true && data.payUrl) {
                    // Thành công - chuyển hướng đến URL thanh toán MoMo
                    console.log('Redirecting to MoMo:', data.payUrl);
                    window.location.href = data.payUrl;
                } else {
                    // Lỗi - hiển thị thông báo chi tiết
                    btnMomoPayment.disabled = false;
                    btnMomoPayment.innerHTML = 'Thanh toán với Momo <img src="{{ asset('clients/assets/images/booking/icon-thanh-toan-momo.png') }}" alt="" style="width: 10%">';
                    
                    let errorMsg = "Không thể tạo thanh toán MoMo. Vui lòng thử lại.";
                    if (data && data.error) {
                        errorMsg = data.error;
                    } else if (data && data.message) {
                        errorMsg = data.message;
                    }
                    
                    console.error('MoMo Payment Error:', data);
                    alert(errorMsg);
                }
            })
            .catch(error => {
                btnMomoPayment.disabled = false;
                btnMomoPayment.innerHTML = 'Thanh toán với Momo <img src="{{ asset('clients/assets/images/booking/icon-thanh-toan-momo.png') }}" alt="" style="width: 10%">';
                console.error('MoMo Payment Exception:', error);
                alert("Có lỗi xảy ra khi kết nối đến MoMo. Vui lòng kiểm tra console để xem chi tiết.");
            });
            
            return false;
        });
    }
})();
</script>

@include('clients.blocks.footer')