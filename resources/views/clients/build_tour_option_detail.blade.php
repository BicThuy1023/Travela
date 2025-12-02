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

{{-- ========== GALLERY ẢNH TOUR CUSTOM ========== --}}
@php
    $galleryImages = [
        asset('clients/assets/images/custom-tour/custom-1.jpg'),
        asset('clients/assets/images/custom-tour/custom-2.jpg'),
        asset('clients/assets/images/custom-tour/custom-3.jpg'),
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
                <div class="gallery-item gallery-between">
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
                <h3>Lịch trình</h3>
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
                                            <p class="mb-0">
                                                <strong>Điểm tham quan:</strong> {{ $placesStr }}
                                            </p>
                                        @endif
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

{{-- CSS nhỏ cho phần hoạt động tùy chọn --}}
<style>
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

@include('clients.blocks.footer')