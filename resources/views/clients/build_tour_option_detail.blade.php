{{-- resources/views/clients/build_tour_option_detail.blade.php --}}

@include('clients.blocks.header')

@php
    $adults = (int) ($requestData['adults'] ?? 1);
    $children = (int) ($requestData['children'] ?? 0);
    $totalPeople = max($adults + $children, 1);

    // BẮT BUỘC lấy breakdown từ option để đồng nhất với checkout (giá đã được tính sẵn trong controller)
    $priceBreakdown = $option['price_breakdown'] ?? [];

    // Hệ số giá trẻ em
    $childFactor = $priceBreakdown['child_factor'] ?? 0.75;

    // Giá người lớn: BẮT BUỘC lấy từ breakdown trước (đồng nhất với checkout)
    $adultPrice = (isset($priceBreakdown['adult_price']) && $priceBreakdown['adult_price'] !== null && $priceBreakdown['adult_price'] !== '')
        ? (int) $priceBreakdown['adult_price']
        : (int) ($option['price_per_adult'] ?? ($option['price_per_person'] ?? 0));

    // Giá trẻ em: BẮT BUỘC lấy từ breakdown trước (đồng nhất với checkout)
    $childPrice = (isset($priceBreakdown['child_price']) && $priceBreakdown['child_price'] !== null && $priceBreakdown['child_price'] !== '')
        ? (int) $priceBreakdown['child_price']
        : (int) ($option['price_per_child'] ?? (int) round($adultPrice * $childFactor / 1000) * 1000);

    // Tổng tiền theo cơ cấu người lớn / trẻ em (để hiển thị chi tiết)
    // BẮT BUỘC lấy từ breakdown trước
    $totalAdultsPrice = (isset($priceBreakdown['total_price_adults']) && $priceBreakdown['total_price_adults'] !== null && $priceBreakdown['total_price_adults'] !== '')
        ? (int) $priceBreakdown['total_price_adults']
        : (int) ($option['total_price_adults'] ?? ($adultPrice * $adults));

    $totalChildrenPrice = (isset($priceBreakdown['total_price_children']) && $priceBreakdown['total_price_children'] !== null && $priceBreakdown['total_price_children'] !== '')
        ? (int) $priceBreakdown['total_price_children']
        : (int) ($option['total_price_children'] ?? ($childPrice * $children));

    // Tổng giá: BẮT BUỘC lấy final_total_price từ breakdown (đã được tính sẵn trong controller)
    // Đây là giá chính xác nhất, không tính lại để tránh sai lệch (đồng nhất với checkout)
    $totalPrice = (isset($priceBreakdown['final_total_price']) && $priceBreakdown['final_total_price'] !== null && $priceBreakdown['final_total_price'] !== '')
        ? (int) $priceBreakdown['final_total_price']
        : (int) ($option['total_price'] ?? 0);

    // Tổng tạm tính (giá tour chính thức, không tính optional)
    $baseTotal = $totalPrice;

    // Hoạt động tùy chọn (nếu controller có gửi)
    $optionalItems = $priceBreakdown['optionals'] ?? [];

    // % giảm giá tour đoàn (nếu có)
    $discountPercent = (int) ($priceBreakdown['group_discount_percent'] ?? 0);
@endphp

{{-- ========== GALLERY ẢNH TOUR CUSTOM - Mỗi tỉnh thành có ảnh khác nhau ========== --}}
@php
    // Lấy tỉnh thành đầu tiên từ main_destinations
    $mainDestinations = $requestData['main_destinations'] ?? [];
    $firstDestination = !empty($mainDestinations) ? $mainDestinations[0] : '';
    
    // Mapping tỉnh thành -> tên file ảnh (ảnh đầu tiên)
    // Format: tên tỉnh thành -> tên file ảnh (không có extension)
    $destinationImageMap = [
        'hà nội' => 'hanoi',
        'hanoi' => 'hanoi',
        'hồ chí minh' => 'hochiminh',
        'ho chi minh' => 'hochiminh',
        'hochiminh' => 'hochiminh',
        'sài gòn' => 'hochiminh',
        'saigon' => 'hochiminh',
        'đà nẵng' => 'danang',
        'da nang' => 'danang',
        'danang' => 'danang',
        'hạ long' => 'halong',
        'ha long' => 'halong',
        'halong' => 'halong',
        'hội an' => 'hoian',
        'hoi an' => 'hoian',
        'hoian' => 'hoian',
        'huế' => 'hue',
        'hue' => 'hue',
        'nha trang' => 'nhatrang',
        'nhatrang' => 'nhatrang',
        'phú quốc' => 'phuquoc',
        'phu quoc' => 'phuquoc',
        'phuquoc' => 'phuquoc',
        'sapa' => 'sapa',
        'mù cang chải' => 'muongchai',
        'mu cang chai' => 'muongchai',
        'muongchai' => 'muongchai',
    ];
    
    // Chuẩn hóa tên tỉnh thành để tìm ảnh (lowercase, trim)
    $normalizedDestination = mb_strtolower(trim($firstDestination));
    
    // Tìm ảnh tương ứng với tỉnh thành
    $imagePrefix = 'custom'; // Mặc định
    if (!empty($normalizedDestination)) {
        // Tìm trong mapping (kiểm tra cả chứa và bị chứa)
        foreach ($destinationImageMap as $key => $value) {
            if (str_contains($normalizedDestination, $key) || str_contains($key, $normalizedDestination)) {
                $imagePrefix = $value;
                break;
            }
        }
    }
    
    // Kiểm tra file ảnh có tồn tại không
    $customImagePath = public_path("clients/assets/images/custom-tour/{$imagePrefix}-1.jpg");
    $firstImage = file_exists($customImagePath) 
        ? asset("clients/assets/images/custom-tour/{$imagePrefix}-1.jpg")
        : asset('clients/assets/images/custom-tour/custom-1.jpg'); // Fallback về ảnh mặc định
    
    // Tạo danh sách ảnh: ảnh đầu tiên theo tỉnh thành, 2 ảnh còn lại dùng mặc định
    $galleryImages = [
        $firstImage, // Ảnh đầu theo tỉnh thành (hoặc mặc định nếu không có)
        asset('clients/assets/images/custom-tour/custom-2.jpg'), // Ảnh 2 mặc định
        asset('clients/assets/images/custom-tour/custom-3.jpg'), // Ảnh 3 mặc định
    ];
@endphp
{{-- ========== END GALLERY ========== --}}

{{-- Banner giống trang tour-detail --}}
<section class="page-banner-two rel z-1">
    <div class="container-fluid">
        <hr class="mt-0">
        <div class="container">
            <div class="banner-inner pt-15 pb-25">
                <h2 class="page-title mb-10" data-aos="fade-left" data-aos-duration="1500" data-aos-offset="50">
                    Chi tiết tour theo yêu cầu
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-20" data-aos="fade-right" data-aos-delay="200"
                        data-aos-duration="1500" data-aos-offset="50">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('build-tour.form') }}">Thiết kế tour</a></li>
                        <li class="breadcrumb-item active">Phương án {{ $optionIndex }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<div class="tour-gallery">
    <div class="container-fluid">
        <div class="row gap-10 justify-content-center rel">
            <div class="col-lg-4 col-md-6">
                <div class="gallery-item">
                    <img src="{{ $galleryImages[0] }}" alt="Ảnh tour 1">
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="gallery-item">
                    <img src="{{ $galleryImages[1] }}" alt="Ảnh tour 2">
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="gallery-item">
                    <img src="{{ $galleryImages[2] }}" alt="Ảnh tour 3">
                </div>
            </div>
        </div>
    </div>
</div>

<section class="tour-details-page pb-100 pt-40">
    <div class="container">
        <div class="row">
            {{-- ========== CỘT TRÁI ========== --}}
            <div class="col-lg-8">
                <div class="tour-details-content">

                    {{-- Link quay lại + badge phương án --}}
                    <div class="d-flex justify-content-between align-items-center mb-15">
                        <a href="{{ route('build-tour.result') }}" class="text-muted small">
                            ← Quay lại danh sách phương án
                        </a>
                        <span class="badge badge-soft-yellow">
                            Phương án {{ $optionIndex }}
                        </span>
                    </div>

                    <h3>Khám phá Tours</h3>

                    {{-- ĐIỂM NHẤN --}}
                    <div class="mb-30">
                        <p class="mb-5">
                            <strong>Tham quan:</strong>
                            @if (!empty($option['highlights']))
                                {{ implode(', ', $option['highlights']) }}.
                            @else
                                Các điểm nổi bật trong hành trình theo lịch trình chi tiết bên dưới.
                            @endif
                        </p>
                        <p class="mb-5">
                            <strong>Lưu trú:</strong>
                            Khách sạn tiêu chuẩn {{ $option['hotel_level'] }}, vị trí thuận tiện tham quan, tiện nghi
                            thoải mái.
                        </p>
                        <p class="mb-0">
                            <strong>Hoạt động khác:</strong>
                            Lịch trình {{ strtolower($option['intensity']) }},
                            kết hợp tham quan – trải nghiệm – nghỉ ngơi hợp lý cho
                            {{ $requestData['adults'] }} người lớn
                            @if(($requestData['children'] ?? 0) > 0)
                                và {{ $requestData['children'] }} trẻ em
                            @endif
                            .
                        </p>
                    </div>

                    {{-- BAO GỒM / KHÔNG BAO GỒM --}}
                    <div class="row pb-40">
                        <div class="col-md-6">
                            <div class="tour-include-exclude mt-10">
                                <h5>Bao gồm và không bao gồm</h5>
                                <ul class="list-style-one check mt-25">
                                    <li><i class="far fa-check"></i> Dịch vụ đón và trả khách tại điểm hẹn.</li>
                                    <li><i class="far fa-check"></i> Khách sạn tiêu chuẩn {{ $option['hotel_level'] }}
                                        trong {{ $option['nights'] }} đêm.</li>
                                    <li><i class="far fa-check"></i> 1–3 bữa ăn mỗi ngày theo chương trình (sáng – trưa
                                        – tối).</li>
                                    <li><i class="far fa-check"></i> Vé tham quan các điểm có trong lịch trình.</li>
                                    <li><i class="far fa-check"></i> Xe du lịch phục vụ tham quan theo chương trình.
                                    </li>
                                    <li><i class="far fa-check"></i> Bảo hiểm du lịch cơ bản, nước uống trên xe.</li>
                                    @if ($tourType === 'group')
                                        <li><i class="far fa-check"></i> Hướng dẫn viên theo đoàn suốt hành trình.</li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="tour-include-exclude mt-30">
                                <h5>Không bao gồm</h5>
                                <ul class="list-style-one mt-25">
                                    <li><i class="far fa-times"></i> Chi phí di chuyển đến điểm tập trung ban đầu.</li>
                                    <li><i class="far fa-times"></i> Ăn uống ngoài chương trình, minibar, giặt ủi…</li>
                                    <li><i class="far fa-times"></i> Các trò chơi, trải nghiệm tự chọn không nêu trong
                                        chương trình.</li>
                                    <li><i class="far fa-times"></i> Chi phí nâng hạng phòng, phòng đơn (nếu có).</li>
                                    <li><i class="far fa-times"></i> Các chi phí cá nhân & phát sinh khác.</li>
                                    <li><i class="far fa-times"></i> Thuế VAT (nếu không ghi trong hợp đồng).</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- LỊCH TRÌNH: accordion --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Lịch trình</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mealPlanModal">
                        <i class="far fa-utensils"></i> Chỉnh sửa ăn uống
                    </button>
                </div>
                <div class="accordion-two mt-25 mb-40" id="build-tour-option-accordion">
                    @if (!empty($option['itinerary']))
                        @foreach ($option['itinerary'] as $idx => $day)
                            @php
                                $collapseId = 'customDay' . $idx;
                                $placesStr = !empty($day['places'])
                                    ? implode(', ', $day['places'])
                                    : '';
                            @endphp
                            <div class="accordion-item">
                                <h5 class="accordion-header">
                                    <button class="accordion-button {{ $idx > 0 ? 'collapsed' : '' }}" data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}">
                                        {{ $day['day'] }}
                                        @if ($placesStr)
                                            - {{ $placesStr }}
                                        @endif
                                    </button>
                                </h5>
                                <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $idx === 0 ? 'show' : '' }}"
                                    data-bs-parent="#build-tour-option-accordion">
                                    <div class="accordion-body">
                                        @php
                                            $desc = $day['description'] ?? '';
                                            $segments = preg_split(
                                                '/(Buổi sáng:|Buổi chiều:|Buổi tối:)/u',
                                                $desc,
                                                -1,
                                                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
                                            );
                                        @endphp

                                        @if (count($segments) <= 1)
                                            <p>{{ $desc }}</p>
                                        @else
                                            @php
                                                $intro = array_shift($segments);
                                            @endphp

                                            @if (trim($intro) !== '')
                                                <p>{{ $intro }}</p>
                                            @endif

                                            <ul class="mb-2 ps-4">
                                                @for ($i = 0; $i < count($segments); $i += 2)
                                                    @php
                                                        $label = $segments[$i] ?? '';
                                                        $text = $segments[$i + 1] ?? '';
                                                    @endphp
                                                    @if (trim($label . $text) !== '')
                                                        <li class="mb-1">
                                                            <strong>{{ $label }}</strong> {{ ltrim($text) }}
                                                        </li>
                                                    @endif
                                                @endfor
                                            </ul>
                                        @endif

                                        @if ($placesStr)
                                            <p class="mb-2">
                                                <strong>Điểm tham quan:</strong> {{ $placesStr }}
                                            </p>
                                        @endif
                                        
                                        {{-- Hiển thị mô tả ăn uống --}}
                                        @php
                                            $mealPlan = $option['meal_plan'] ?? [];
                                            $dayId = $idx + 1;
                                            $totalDays = count($option['itinerary']);
                                            $dayMeals = $mealPlan[$dayId] ?? [];
                                            $mealService = app(\App\Services\MealService::class);
                                            $standardMeals = $mealService->getStandardMealsForDay($dayId, $totalDays);
                                        @endphp
                                        
                                        <div class="meal-plan-info mt-3 pt-3 border-top">
                                            <strong class="d-block mb-2">
                                                <i class="far fa-utensils"></i> Chế độ ăn uống:
                                            </strong>
                                            <ul class="list-unstyled mb-0 small">
                                                @foreach (['breakfast' => 'Buổi sáng', 'lunch' => 'Buổi trưa', 'dinner' => 'Buổi tối'] as $mealType => $timeLabel)
                                                    @php
                                                        // Chỉ hiển thị bữa có trong meal plan hoặc là bữa chuẩn
                                                        if (!isset($dayMeals[$mealType]) && !in_array($mealType, $standardMeals)) {
                                                            continue;
                                                        }
                                                        
                                                        $meal = $dayMeals[$mealType] ?? ['level' => 'standard', 'type' => 'restaurant', 'self_pay' => false];
                                                        $isExtraMeal = $mealService->isExtraMeal($dayId, $mealType, $totalDays);
                                                        $description = $mealService->generateMealDescription($meal, $mealType, $dayId, $totalDays);
                                                    @endphp
                                                    <li class="mb-1">
                                                        {!! $description !!}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">
                            Lịch trình đang được hệ thống cập nhật. Vui lòng liên hệ nhân viên để được tư vấn chi tiết hơn.
                        </p>
                    @endif
                </div>

                {{-- Ghi chú về giá --}}
                <p class="small text-muted">
                    <em>Lưu ý:</em> Đây là chi phí ước tính dựa trên số khách, số ngày, mức khách sạn và loại tour
                    (đoàn / cá nhân). Giá thực tế có thể thay đổi theo thời điểm khởi hành, loại phòng và các yêu cầu
                    phát sinh.
                </p>
            </div>

            {{-- ========== CỘT PHẢI: BOOKING & CHI TIẾT CHI PHÍ ========== --}}
            <div class="col-lg-4 col-md-8 col-sm-10 rmt-75">
                <div class="blog-sidebar tour-sidebar">

                    {{-- BOX BOOKING + CHI PHÍ --}}
                    <div class="widget widget-booking" data-aos="fade-up" data-aos-duration="1500" data-aos-offset="50">
                        <h5 class="widget-title">Tour Booking</h5>

                        <form action="{{ route('build-tour.choose', ['index' => $optionIndex]) }}" method="POST">
                            @csrf

                            <div class="date mb-25">
                                <b>Ngày bắt đầu</b>
                                <input type="text"
                                    value="{{ \Carbon\Carbon::parse($requestData['start_date'])->format('d-m-Y') }}"
                                    disabled>
                            </div>
                            <hr>
                            <div class="date mb-25">
                                <b>Ngày kết thúc</b>
                                <input type="text"
                                    value="{{ \Carbon\Carbon::parse($requestData['end_date'])->format('d-m-Y') }}"
                                    disabled>
                            </div>
                            <hr>
                            <div class="time py-5">
                                <b>Thời gian :</b>
                                <p>{{ $requestData['days'] }} ngày {{ $requestData['nights'] }} đêm</p>
                            </div>
                            <hr class="mb-25">
                            <h6>Vé:</h6>
                            <ul class="tickets clearfix">
                                <li>
                                    Người lớn ({{ $adults }})
                                    <span class="price">
                                        {{ $adults }} x {{ number_format($adultPrice, 0, ',', '.') }} VND
                                    </span>
                                </li>

                                @if($children > 0)
                                    <li>
                                        Trẻ em ({{ $children }})
                                        <span class="price">
                                            {{ $children }} x {{ number_format($childPrice, 0, ',', '.') }} VND
                                        </span>
                                    </li>
                                @endif
                            </ul>


                            {{-- 💰 CHI TIẾT CHI PHÍ / 1 NGƯỜI LỚN --}}
                            @if (!empty($priceBreakdown))
                                @php
                                    // Lấy giá trị sau hệ số gói (chưa nhân hệ số tour riêng) để hiển thị trong breakdown
                                    $hotelCost = $priceBreakdown['hotel_per_person'] ?? 0;
                                    $foodCost = $priceBreakdown['food_per_person'] ?? 0;
                                    $actCost = $priceBreakdown['activity_per_person'] ?? 0;
                                    $transport = $priceBreakdown['transport_per_person'] ?? 0;

                                    // Tổng chi phí dịch vụ gốc = tổng 4 mục cơ bản (sau hệ số gói, chưa nhân hệ số tour riêng)
                                    $coreCost = $hotelCost + $foodCost + $actCost + $transport;

                                    // Phí dịch vụ sau khi nhân hệ số gói (đã bao gồm phí tour riêng nếu có)
                                    $serviceFee = $priceBreakdown['service_fee_after_multiplier'] ?? $priceBreakdown['service_fee_per_person'] ?? 0;
                                    $surcharge = $priceBreakdown['surcharge_after_multiplier'] ?? $priceBreakdown['surcharge_per_person'] ?? 0;

                                    // Tổng trước giảm và số tiền giảm / 1 người lớn
                                    $baseBeforeDiscount = $priceBreakdown['base_before_discount_per_person'] ?? 0;
                                    $groupDiscountPercent = $priceBreakdown['group_discount_percent'] ?? 0;
                                    $discountPerAdult = $priceBreakdown['discount_amount_per_adult'] ?? 0;

                                    // Kiểm tra xem có phí tour riêng không
                                    $isPrivateTour = $priceBreakdown['is_private_tour'] ?? false;
                                    $privateMultiplier = $priceBreakdown['private_multiplier'] ?? 1.0;
                                @endphp

                                <div class="cost-breakdown mt-15 mb-10">
                                    <h6 class="mb-5">Chi tiết chi phí (1 người lớn)</h6>
                                    <table class="table table-sm mb-5">
                                        <tbody>
                                            <tr>
                                                <td>Khách sạn ({{ $option['nights'] }} đêm)</td>
                                                <td class="text-end">{{ number_format($hotelCost, 0, ',', '.') }} VND</td>
                                            </tr>
                                            <tr>
                                                <td>Ăn uống ({{ $option['days'] }} ngày)</td>
                                                <td class="text-end">{{ number_format($foodCost, 0, ',', '.') }} VND</td>
                                            </tr>
                                            <tr>
                                                <td>Vé tham quan & hoạt động</td>
                                                <td class="text-end">{{ number_format($actCost, 0, ',', '.') }} VND</td>
                                            </tr>
                                            <tr>
                                                <td>Di chuyển nội bộ</td>
                                                <td class="text-end">{{ number_format($transport, 0, ',', '.') }} VND</td>
                                            </tr>

                                            <tr class="fw-semibold">
                                                <td>Tổng chi phí dịch vụ gốc</td>
                                                <td class="text-end">{{ number_format($coreCost, 0, ',', '.') }} VND</td>
                                            </tr>

                                            <tr class="small text-muted">
                                                <td>
                                                    Phí dịch vụ / điều hành tour<sup>(*)</sup>
                                                </td>
                                                <td class="text-end">{{ number_format($serviceFee, 0, ',', '.') }} VND</td>
                                            </tr>

                                            @if($surcharge > 0)
                                                <tr class="small text-muted">
                                                    <td>Phụ thu cao điểm</td>
                                                    <td class="text-end">{{ number_format($surcharge, 0, ',', '.') }} VND</td>
                                                </tr>
                                            @endif

                                            {{-- Chỉ hiển thị "Tổng trước ưu đãi" nếu có ưu đãi --}}
                                            @if($groupDiscountPercent > 0 && $discountPerAdult > 0)
                                            {{-- Tổng trước khi áp dụng ưu đãi đoàn --}}
                                                {{-- Đảm bảo tổng khớp: coreCost + serviceFee + surcharge = baseBeforeDiscount --}}
                                            <tr class="fw-semibold">
                                                <td>Tổng trước ưu đãi</td>
                                                <td class="text-end">{{ number_format($baseBeforeDiscount, 0, ',', '.') }}
                                                    VND</td>
                                            </tr>

                                                <tr class="text-success small">
                                                    <td>Ưu đãi tour đoàn ({{ $groupDiscountPercent }}%)</td>
                                                    <td class="text-end">
                                                        -{{ number_format($discountPerAdult, 0, ',', '.') }} VND
                                                    </td>
                                                </tr>
                                            @endif

                                            <tr class="fw-bold">
                                                <td>Giá cuối cùng / người lớn</td>
                                                <td class="text-end">
                                                    {{ number_format($adultPrice, 0, ',', '.') }} VND
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p class="small text-muted mt-2 mb-0">
                                        <sup>(*)</sup> Phí dịch vụ / điều hành tour bao gồm: phí dịch vụ, phí điều hành, phí
                                        tạo tour riêng và các chi phí vận hành khác.
                                    </p>
                                </div>
                            @endif


                            {{-- ================= HOẠT ĐỘNG TRẢI NGHIỆM (CHI PHÍ TỰ TÚC) ================= --}}
                            @php
                                $totalPeopleOption = $option['total_people'] ?? $totalPeople;
                                $baseTotalPrice = $option['total_price'] ?? ($priceBreakdown['final_total_price'] ?? 0);
                            @endphp

                            @if (!empty($priceBreakdown['optionals']))
                                <div class="optional-activities card border-0 shadow-sm mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-2">Hoạt động trải nghiệm (chi phí tự túc)</h5>
                                        <p class="text-muted small mb-3">
                                            Các hoạt động dưới đây <strong>không nằm trong giá tour</strong>.
                                            Bạn có thể tick để ước lượng tổng chi phí chuyến đi nếu tham gia.
                                        </p>

                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($priceBreakdown['optionals'] as $idx => $opt)
                                                @php
                                                    $optId = $opt['id'] ?? ('opt_' . $idx);
                                                    $label = $opt['label'] ?? 'Hoạt động';
                                                    $note = $opt['note'] ?? null;
                                                    $pricePerPax = (int) ($opt['price_per_person'] ?? 0);
                                                    $totalForAll = $pricePerPax * $totalPeopleOption;
                                                @endphp

                                                <label
                                                    class="optional-card border rounded-3 p-3 d-flex align-items-start gap-3 w-100 mb-0">
                                                    <div class="form-check mt-1">
                                                        <input type="checkbox" class="form-check-input optional-checkbox"
                                                            id="optional_{{ $optId }}"
                                                            data-price-per-person="{{ $pricePerPax }}">
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="fw-semibold">{{ $label }}</span>
                                                        </div>

                                                        @if ($pricePerPax > 0)
                                                            <div class="small text-muted mb-1">
                                                                {{ number_format($pricePerPax, 0, ',', '.') }}đ/người
                                                                ({{ $totalPeopleOption }} người →
                                                                {{ number_format($totalForAll, 0, ',', '.') }}đ)
                                                            </div>
                                                        @endif

                                                        @if (!empty($note))
                                                            <div class="small text-secondary">
                                                                {{ $note }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Hidden input để lưu giá optional activities --}}
                            <input type="hidden" name="optional_activities_total" id="optionalActivitiesTotal"
                                value="0">
                            <input type="hidden" name="final_total_price" id="finalTotalPrice" value="{{ $baseTotal }}">

                            {{-- Tạm tính tổng (chỉ tính giá tour) --}}
                            <div class="mt-10 mb-1 small d-flex justify-content-between">
                                <span>Tổng chi phí tour (~ {{ $totalPeople }} khách)</span>
                                <span class="text-success fw-semibold" id="totalPriceGroup"
                                    data-base-total="{{ $baseTotal }}">
                                    {{ number_format($baseTotal, 0, ',', '.') }} VND
                                </span>
                            </div>

                            {{-- Dòng mô tả thêm chi phí tự túc, mặc định ẩn --}}
                            <div id="optionalExtraLabel" class="mt-1 mb-4 small text-muted d-none">
                                Đã bao gồm hoạt động tự túc: <span id="optionalExtraAmount"></span>
                            </div>

                            @if ($tourType === 'group' && $discountPercent > 0)
                                <div class="small text-success mb-5">
                                    Đã áp dụng ưu đãi tour đoàn: giảm {{ $discountPercent }}% / khách.
                                </div>
                            @endif

                            <button type="submit" class="theme-btn style-two w-100 mt-10 mb-5">
                                <span data-hover="Đặt ngay">Đặt ngay</span>
                                <i class="fal fa-arrow-right"></i>
                            </button>

                            <div class="text-center">
                                <a href="{{ route('contact') }}">Bạn cần trợ giúp không?</a>
                            </div>
                        </form>
                    </div>

                    {{-- BOX TRỢ GIÚP --}}
                    <div class="widget widget-contact" data-aos="fade-up" data-aos-duration="1500" data-aos-offset="50">
                        <h5 class="widget-title">Cần trợ giúp?</h5>
                        <ul class="list-style-one">
                            <li><i class="far fa-envelope"></i>
                                <a href="mailto:ttbthuy892@gmail.com">ttbthuy892@gmail.com</a>
                            </li>
                            <li><i class="far fa-phone-volume"></i>
                                <a href="tel:+00012345688">+000 (123) 456 88</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CSS cho gallery ảnh - đảm bảo các ảnh đều nhau --}}
<style>
    /* Gallery ảnh tour - đảm bảo tất cả ảnh có cùng kích thước */
    .tour-gallery .gallery-item {
        height: 400px; /* Chiều cao cố định */
        overflow: hidden;
        border-radius: 8px;
    }
    
    .tour-gallery .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Đảm bảo ảnh phủ đầy không bị méo */
        display: block;
    }
    
    /* Responsive cho mobile */
    @media (max-width: 768px) {
        .tour-gallery .gallery-item {
            height: 300px;
        }
    }
    
    @media (max-width: 576px) {
        .tour-gallery .gallery-item {
            height: 250px;
        }
    }

    /* CSS cho phần hoạt động tùy chọn */
    .cost-row-optional-card {
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        cursor: pointer;
        transition: all .2s ease;
    }

    .cost-row-optional-card:hover {
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .cost-row-optional.is-excluded {
        opacity: .85;
        background: #fff;
    }

    .optional-thumb {
        width: 70px;
        min-width: 70px;
        height: 55px;
        border-radius: 10px;
        overflow: hidden;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .optional-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .optional-thumb-placeholder {
        font-size: 18px;
        color: #64748b;
    }
</style>

{{-- SCRIPT CỘNG TIỀN OPTIONAL TRỰC TIẾP VÀO TỔNG --}}
<script>
    (function () {
        const checkboxes = document.querySelectorAll('.optional-checkbox');
        const totalEl = document.getElementById('totalPriceGroup');
        const extraLabel = document.getElementById('optionalExtraLabel');
        const extraAmountSpan = document.getElementById('optionalExtraAmount');

        if (!checkboxes.length || !totalEl) return;

        const baseTotal = parseInt(totalEl.dataset.baseTotal || '0', 10) || 0;
        const totalPeople = {{ (int) ($option['total_people'] ?? max(($requestData['adults'] ?? 1) + ($requestData['children'] ?? 0), 1)) }};

        function formatCurrency(v) {
            return v.toLocaleString('vi-VN') + ' VND';
        }

        function updateTotal() {
            let extra = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const per = parseInt(cb.dataset.pricePerPerson || '0', 10) || 0;
                    extra += per * totalPeople;
                }
            });

            const finalTotal = baseTotal + extra;
            totalEl.textContent = formatCurrency(finalTotal);

            // Cập nhật hidden input để gửi giá optional khi submit form
            const optionalTotalInput = document.getElementById('optionalActivitiesTotal');
            const finalTotalInput = document.getElementById('finalTotalPrice');
            if (optionalTotalInput) {
                optionalTotalInput.value = extra;
            }
            if (finalTotalInput) {
                finalTotalInput.value = finalTotal;
            }

            if (extra > 0 && extraLabel && extraAmountSpan) {
                extraLabel.classList.remove('d-none');
                extraAmountSpan.textContent = formatCurrency(extra);
            } else if (extraLabel) {
                extraLabel.classList.add('d-none');
            }
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotal);
        });

        updateTotal();
    })();
</script>

{{-- ========== MODAL CHỈNH SỬA ĂN UỐNG ========== --}}
{{-- Modal phải nằm TRƯỚC @include footer để vẫn nằm trong body nhưng sau tất cả content --}}
{{-- Bootstrap modal cần được đặt ở cấp độ body để hoạt động đúng --}}
<div class="modal fade" id="mealPlanModal" tabindex="-1" aria-labelledby="mealPlanModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="pointer-events: auto;">
            <div class="modal-header">
                <h5 class="modal-title" id="mealPlanModalLabel">
                    <i class="far fa-utensils"></i> Chỉnh sửa ăn uống
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mealPlanForm" onsubmit="return false;">
                    @csrf
                    <input type="hidden" name="option_index" id="option_index_input" value="{{ $optionIndex }}">
                    
                    @if (!empty($option['itinerary']))
                        @php
                            $mealPlan = $option['meal_plan'] ?? [];
                            $mealLevels = config('meals.levels');
                            $mealTypes = config('meals.types');
                        @endphp
                        
                        @foreach ($option['itinerary'] as $dayIdx => $day)
                            @php
                                $dayId = $dayIdx + 1;
                                $dayMeals = $mealPlan[$dayId] ?? [];
                            @endphp
                            
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="far fa-calendar-day"></i> {{ $day['day'] }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        // Xác định bữa chuẩn và bữa thêm cho ngày này
                                        $dayId = $dayIdx + 1;
                                        $totalDays = count($option['itinerary']);
                                        $mealService = app(\App\Services\MealService::class);
                                        $standardMeals = $mealService->getStandardMealsForDay($dayId, $totalDays);
                                        
                                        // Hiển thị tất cả 3 bữa, nhưng đánh dấu bữa thêm
                                        $availableMeals = ['breakfast' => 'Ăn sáng', 'lunch' => 'Ăn trưa', 'dinner' => 'Ăn tối'];
                                    @endphp
                                    
                                    @foreach ($availableMeals as $mealType => $mealLabel)
                                        @php
                                            $isExtraMeal = $mealService->isExtraMeal($dayId, $mealType, $totalDays);
                                            
                                            // Nếu là bữa thêm và chưa có trong meal_plan → mặc định tự túc (self_pay = true)
                                            // Khách có thể bỏ check "Tự túc" để bao gồm bữa thêm vào giá tour
                                            if ($isExtraMeal && !isset($dayMeals[$mealType])) {
                                                $meal = [
                                                    'level' => 'standard',
                                                    'type' => 'restaurant',
                                                    'self_pay' => true  // Bữa thêm mặc định tự túc
                                                ];
                                            } else {
                                                // Bữa chuẩn hoặc đã có trong meal_plan → dùng giá trị từ meal_plan
                                                $meal = $dayMeals[$mealType] ?? [
                                                    'level' => 'standard',
                                                    'type' => 'restaurant',
                                                    'self_pay' => false  // Bữa chuẩn mặc định đã bao gồm
                                                ];
                                            }
                                        @endphp
                                        
                                        @php
                                            // fix: unique id/for for each meal - tạo id unique cho mỗi phần tử
                                            $levelId = "meal_level_{$dayId}_{$mealType}";
                                            $typeId = "meal_type_{$dayId}_{$mealType}";
                                            $selfPayId = "meal_self_{$dayId}_{$mealType}";
                                        @endphp
                                        
                                        <div class="row mb-3 pb-3 border-bottom">
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">
                                                    {{ $mealLabel }}
                                                    @if ($isExtraMeal)
                                                        <span class="badge bg-warning text-dark ms-1 small" title="Bữa ăn tùy chọn, sẽ tính thêm tiền nếu chọn">
                                                            <i class="far fa-info-circle"></i> Bữa thêm
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success ms-1 small" title="Đã bao gồm trong giá tour">
                                                            <i class="far fa-check-circle"></i> Đã bao gồm
                                                        </span>
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="col-md-3">
                                                {{-- fix: unique id/for for each meal - label có for trỏ đúng id của select --}}
                                                {{-- dùng select chuẩn, bỏ custom dropdown để tránh bug chồng option --}}
                                                <label for="{{ $levelId }}" class="form-label small">Mức ăn</label>
                                                <select id="{{ $levelId }}"
                                                        name="meal_plan[{{ $dayId }}][{{ $mealType }}][level]" 
                                                        class="form-select form-select-sm meal-level-select"
                                                        data-no-nice-select="true">
                                                    @foreach ($mealLevels as $key => $level)
                                                        <option value="{{ $key }}" 
                                                                {{ ($meal['level'] ?? 'standard') === $key ? 'selected' : '' }}>
                                                            {{ $level['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                {{-- fix: unique id/for for each meal - label có for trỏ đúng id của select --}}
                                                {{-- dùng select chuẩn, bỏ custom dropdown để tránh bug chồng option --}}
                                                <label for="{{ $typeId }}" class="form-label small">Hình thức</label>
                                                <select id="{{ $typeId }}"
                                                        name="meal_plan[{{ $dayId }}][{{ $mealType }}][type]" 
                                                        class="form-select form-select-sm meal-type-select"
                                                        data-no-nice-select="true">
                                                    @foreach ($mealTypes as $key => $type)
                                                        <option value="{{ $key }}" 
                                                                {{ ($meal['type'] ?? 'restaurant') === $key ? 'selected' : '' }}>
                                                            {{ $type }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Tùy chọn</label>
                                                <div class="form-check mt-2">
                                                    {{-- fix: unique id/for for each meal - checkbox có id unique --}}
                                                    <input type="checkbox" 
                                                           id="{{ $selfPayId }}"
                                                           name="meal_plan[{{ $dayId }}][{{ $mealType }}][self_pay]" 
                                                           value="1"
                                                           class="form-check-input meal-self-pay-checkbox"
                                                           {{ ($meal['self_pay'] ?? false) ? 'checked' : '' }}>
                                                    {{-- fix: unique id/for for each meal - label có for trỏ đúng id của checkbox --}}
                                                    <label for="{{ $selfPayId }}" class="form-check-label small">Tự túc</label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="saveMealPlanBtn">
                    <i class="far fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CSS đảm bảo modal không bị che và có thể tương tác --}}
<style>
    /* Đảm bảo modal có z-index cao hơn tất cả element khác */
    /* Bootstrap 5 modal mặc định có z-index: 1055, backdrop: 1050 */
    #mealPlanModal {
        z-index: 1055 !important;
        position: fixed !important;
    }
    
    /* Đảm bảo modal-dialog có pointer-events để nhận click */
    /* Bootstrap mặc định modal-dialog có pointer-events: none, cần override */
    #mealPlanModal .modal-dialog {
        pointer-events: auto !important;
        z-index: 1056 !important;
        position: relative !important;
    }
    
    /* Đảm bảo modal-content có thể tương tác */
    #mealPlanModal .modal-content {
        pointer-events: auto !important;
        position: relative !important;
    }
    
    /* Đảm bảo tất cả phần tử trong modal có thể tương tác */
    #mealPlanModal .modal-content * {
        pointer-events: auto !important;
    }
    
    /* Đảm bảo modal-body có thể scroll và tương tác */
    #mealPlanModal .modal-body {
        pointer-events: auto !important;
        overflow-y: auto !important;
    }
    
    /* dùng select chuẩn, bỏ custom dropdown để tránh bug chồng option */
    /* Đảm bảo row không cắt dropdown - overflow visible để dropdown hiển thị đầy đủ */
    #mealPlanModal .row {
        overflow: visible !important;
    }
    
    #mealPlanModal .card-body {
        overflow: visible !important;
    }
    
    /* Đảm bảo select là native, không bị transform bởi niceSelect */
    #mealPlanModal select {
        display: block !important;
        width: 100% !important;
        position: relative !important;
        z-index: auto !important;
        -webkit-appearance: menulist !important;
        -moz-appearance: menulist !important;
        appearance: menulist !important;
    }
    
    /* Ẩn nice-select wrapper nếu có trong modal */
    #mealPlanModal .nice-select {
        display: none !important;
    }
    
    /* Đảm bảo select gốc hiển thị */
    #mealPlanModal select[data-no-nice-select="true"] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Đảm bảo backdrop không che modal */
    .modal-backdrop {
        z-index: 1054 !important;
    }
    
    /* Đảm bảo không có element nào che modal khi mở */
    body.modal-open .page-wrapper {
        position: relative !important;
        z-index: auto !important;
        pointer-events: auto !important;
    }
    
    body.modal-open .tour-details-page,
    body.modal-open .container,
    body.modal-open section {
        position: relative;
        z-index: auto !important;
    }
    
    /* Ẩn các overlay/backdrop khác khi modal mở */
    body.modal-open .page-wrapper::before,
    body.modal-open .page-wrapper::after,
    body.modal-open .tour-details-page::before,
    body.modal-open .tour-details-page::after,
    body.modal-open .overlay::before,
    body.modal-open .overlay::after {
        display: none !important;
        z-index: -1 !important;
        pointer-events: none !important;
    }
    
    /* Đảm bảo page-wrapper không chặn modal */
    body.modal-open .page-wrapper > *:not(.modal):not(.modal-backdrop) {
        pointer-events: auto !important;
    }
    
    /* Đảm bảo các input/select trong modal không bị disabled bởi CSS */
    #mealPlanModal select,
    #mealPlanModal input[type="checkbox"],
    #mealPlanModal button {
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    
    /* fix: unique id/for for each meal - đảm bảo mỗi select hoạt động độc lập */
    /* Ngăn browser tự động scroll đến phần tử khác khi click */
    #mealPlanModal .meal-level-select,
    #mealPlanModal .meal-type-select {
        position: relative !important;
        z-index: auto !important;
    }
    
    /* Đảm bảo mỗi row (mỗi bữa) là một container độc lập */
    /* dùng select chuẩn, bỏ custom dropdown để tránh bug chồng option */
    #mealPlanModal .row {
        position: relative;
        isolation: isolate; /* Tạo stacking context riêng cho mỗi row */
        overflow: visible !important; /* Đảm bảo dropdown không bị cắt */
    }
    
    #mealPlanModal .card-body {
        overflow: visible !important; /* Đảm bảo dropdown không bị cắt */
    }
    
    /* Đảm bảo select là native, không bị transform bởi niceSelect */
    /* Sửa dropdown 2 mũi tên - chỉ dùng mũi tên native của browser, không thêm custom */
    #mealPlanModal select {
        display: block !important;
        width: 100% !important;
        position: relative !important;
        z-index: auto !important;
        /* Dùng native dropdown của browser, không thêm custom arrow */
        -webkit-appearance: menulist !important;
        -moz-appearance: menulist !important;
        appearance: menulist !important;
        /* Loại bỏ background-image custom nếu có */
        background-image: none !important;
        background-position: unset !important;
        background-repeat: unset !important;
    }
    
    /* Loại bỏ pseudo-element tạo mũi tên thừa */
    #mealPlanModal select::after,
    #mealPlanModal select::before {
        display: none !important;
        content: none !important;
    }
    
    /* Loại bỏ wrapper có mũi tên custom */
    #mealPlanModal .form-select-wrapper::after,
    #mealPlanModal .select-wrapper::after {
        display: none !important;
    }
    
    /* Ẩn nice-select wrapper nếu có trong modal */
    #mealPlanModal .nice-select {
        display: none !important;
    }
    
    /* Đảm bảo select gốc hiển thị */
    #mealPlanModal select[data-no-nice-select="true"],
    #mealPlanModal select {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Đảm bảo nút đóng modal có thể click */
    #mealPlanModal .btn-close {
        pointer-events: auto !important;
        cursor: pointer !important;
        z-index: 1057 !important;
    }
</style>

{{-- JavaScript xử lý meal plan --}}
<script>
(function() {
    const saveBtn = document.getElementById('saveMealPlanBtn');
    const mealPlanForm = document.getElementById('mealPlanForm');
    const modal = document.getElementById('mealPlanModal');
    
    if (!saveBtn || !mealPlanForm) return;
    
    // dùng select chuẩn, bỏ custom dropdown để tránh bug chồng option
    // Hủy niceSelect nếu đã được apply cho select trong modal
    function destroyNiceSelectInModal() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.niceSelect) {
            jQuery('#mealPlanModal select').each(function() {
                const $select = jQuery(this);
                const $niceSelect = $select.next('.nice-select');
                if ($niceSelect.length) {
                    // Destroy niceSelect
                    $select.niceSelect('destroy');
                    // Đảm bảo select hiển thị
                    $select.css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                }
            });
        }
    }
    
    // Hủy niceSelect khi modal được mở
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            destroyNiceSelectInModal();
        });
        
        // Cũng hủy ngay nếu modal đã mở
        if (modal.classList.contains('show')) {
            destroyNiceSelectInModal();
        }
    }
    
    // Ngăn niceSelect apply vào select trong modal (nếu script.js chạy sau)
    // Sử dụng MutationObserver để theo dõi và destroy niceSelect ngay khi nó xuất hiện
    if (modal && typeof MutationObserver !== 'undefined') {
        const niceSelectObserver = new MutationObserver(function(mutations) {
            jQuery('#mealPlanModal select').each(function() {
                const $select = jQuery(this);
                if ($select.next('.nice-select').length) {
                    destroyNiceSelectInModal();
                }
            });
        });
        
        niceSelectObserver.observe(modal, {
            childList: true,
            subtree: true
        });
        
        // Cũng check ngay khi script load xong
        if (document.readyState === 'complete') {
            setTimeout(destroyNiceSelectInModal, 100);
        } else {
            window.addEventListener('load', function() {
                setTimeout(destroyNiceSelectInModal, 100);
            });
        }
    }
    
    // Disable select khi checkbox tự túc được chọn
    // fix: unique id/for for each meal - đảm bảo chỉ ảnh hưởng đến select trong cùng row (cùng bữa)
    function toggleMealInputs(checkbox) {
        // Tìm row chứa checkbox này (mỗi bữa là một row riêng)
        const row = checkbox.closest('.row');
        if (!row) return;
        
        // Chỉ tìm select trong row này, không ảnh hưởng đến bữa khác
        const selects = row.querySelectorAll('.meal-level-select, .meal-type-select');
        selects.forEach(select => {
            // Chỉ disable khi checkbox được check, nhưng vẫn giữ pointer-events
            if (checkbox.checked) {
                select.disabled = true;
                select.style.opacity = '0.5';
                select.style.cursor = 'not-allowed';
                select.style.pointerEvents = 'none'; // Chặn click nhưng vẫn có thể lấy value
            } else {
                select.disabled = false;
                select.style.opacity = '1';
                select.style.cursor = 'pointer';
                select.style.pointerEvents = 'auto';
            }
        });
    }
    
    // Khởi tạo trạng thái ban đầu
    // fix: unique id/for for each meal - mỗi checkbox được xử lý độc lập
    document.querySelectorAll('.meal-self-pay-checkbox').forEach(cb => {
        toggleMealInputs(cb);
        // Đảm bảo event handler chỉ ảnh hưởng đến checkbox hiện tại
        cb.addEventListener('change', function(e) {
            e.stopPropagation(); // Ngăn event bubble lên
            toggleMealInputs(this); // this = checkbox hiện tại
        });
    });
    
    // fix: unique id/for for each meal - đảm bảo mỗi select hoạt động độc lập
    // Ngăn browser tự động scroll/focus đến phần tử khác khi click
    document.querySelectorAll('#mealPlanModal .meal-level-select, #mealPlanModal .meal-type-select').forEach(select => {
        select.addEventListener('focus', function(e) {
            // Đảm bảo focus vào đúng select này, không scroll đến phần tử khác
            e.stopPropagation();
        });
        
        select.addEventListener('change', function(e) {
            // Đảm bảo change event chỉ ảnh hưởng đến select này
            e.stopPropagation();
        });
    });
    
    // Xử lý lưu meal plan
    saveBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const optionIndex = document.getElementById('option_index_input')?.value || '{{ $optionIndex }}';
        
        // Chuyển FormData thành object
        const mealPlanData = {};
        
        // Lấy tất cả các select và checkbox
        const allMealInputs = mealPlanForm.querySelectorAll('[name^="meal_plan["]');
        
        allMealInputs.forEach(input => {
            const name = input.name;
            // Parse key: meal_plan[1][breakfast][level]
            const match = name.match(/meal_plan\[(\d+)\]\[(\w+)\]\[(\w+)\]/);
            if (match) {
                const [, dayId, mealType, field] = match;
                if (!mealPlanData[dayId]) mealPlanData[dayId] = {};
                if (!mealPlanData[dayId][mealType]) mealPlanData[dayId][mealType] = {};
                
                // Xử lý checkbox
                if (input.type === 'checkbox') {
                    mealPlanData[dayId][mealType][field] = input.checked;
                } else {
                    // Lấy giá trị (disabled select vẫn có value)
                    mealPlanData[dayId][mealType][field] = input.value || input.options[input.selectedIndex]?.value || '';
                }
            }
        });
        
        // Đảm bảo tất cả các bữa đều có đầy đủ thông tin
        // Nếu thiếu, thêm giá trị mặc định
        const allDays = new Set();
        const allMealTypes = ['breakfast', 'lunch', 'dinner'];
        
        Object.keys(mealPlanData).forEach(dayId => {
            allDays.add(dayId);
            allMealTypes.forEach(mealType => {
                if (!mealPlanData[dayId][mealType]) {
                    mealPlanData[dayId][mealType] = {
                        level: 'standard',
                        type: 'restaurant',
                        self_pay: false
                    };
                } else {
                    // Đảm bảo có đầy đủ các field
                    if (!mealPlanData[dayId][mealType].hasOwnProperty('level')) {
                        mealPlanData[dayId][mealType].level = 'standard';
                    }
                    if (!mealPlanData[dayId][mealType].hasOwnProperty('type')) {
                        mealPlanData[dayId][mealType].type = 'restaurant';
                    }
                    if (!mealPlanData[dayId][mealType].hasOwnProperty('self_pay')) {
                        mealPlanData[dayId][mealType].self_pay = false;
                    }
                }
            });
        });
        
        console.log('Meal plan data to send:', mealPlanData);
        console.log('Option index:', optionIndex);
        console.log('URL:', `{{ route('build-tour.update-meals', ['index' => $optionIndex]) }}`);
        
        // Kiểm tra dữ liệu trước khi gửi
        if (Object.keys(mealPlanData).length === 0) {
            alert('Không có dữ liệu meal plan để gửi. Vui lòng kiểm tra lại.');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="far fa-save"></i> Lưu thay đổi';
            return;
        }
        
        // Gửi AJAX
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="far fa-spinner fa-spin"></i> Đang lưu...';
        
        const url = `{{ route('build-tour.update-meals', ['index' => $optionIndex]) }}`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        console.log('Sending request to:', url);
        console.log('CSRF Token:', csrfToken ? 'Found' : 'NOT FOUND');
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                meal_plan: mealPlanData
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            // Đọc response text trước để debug
            return response.text().then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Hiển thị thông báo thành công
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message, 'Thành công');
                } else {
                    alert(data.message);
                }
                
                // Đóng modal
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
                
                // Reload trang để cập nhật giá và hiển thị mô tả mới
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message, 'Lỗi');
                } else {
                    alert(data.message);
                }
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="far fa-save"></i> Lưu thay đổi';
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            
            let errorMessage = 'Có lỗi xảy ra khi lưu. Vui lòng thử lại.';
            if (error.message) {
                errorMessage += '\nChi tiết: ' + error.message;
            }
            
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage, 'Lỗi', {timeOut: 10000});
            } else {
                alert(errorMessage);
            }
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="far fa-save"></i> Lưu thay đổi';
        });
    });
})();
</script>

@include('clients.blocks.footer')