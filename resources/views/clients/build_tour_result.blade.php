{{-- resources/views/clients/build_tour_result.blade.php --}}

@include('clients.blocks.header', [
    'title' => ' | ' . ($title ?? 'Gợi ý Tour theo yêu cầu'),
    'includeBuildTourCss' => true,
])
@include('clients.blocks.banner', ['title' => $title ?? 'Gợi ý Tour theo yêu cầu'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/build-tour.css') }}">
@endpush

<section class="pt-80 pb-80" style="background:#f3f4f6;">
    <div class="container">
        <div class="build-tour-wrapper">

            {{-- ========== HEADER + STEPS ========== --}}
            <div class="build-tour-header text-center mb-4">
                <h2>Thiết Kế Tour Theo Yêu Cầu</h2>
                <p>Hãy cho chúng tôi biết nhu cầu của bạn. Hệ thống sẽ tạo ra 2–3 lịch trình phù hợp nhất.</p>
            </div>

            <div class="steps-indicator mb-4">
                <div class="step-item">
                    <span class="step-index">1</span>
                    <div class="step-text">
                        <strong>Điểm đến & Ngày đi</strong>
                        <small>Chọn nơi muốn đến và khoảng thời gian</small>
                    </div>
                </div>

                <div class="step-item">
                    <span class="step-index">2</span>
                    <div class="step-text">
                        <strong>Ngân sách & Sở thích</strong>
                        <small>Thông tin chi tiết hơn</small>
                    </div>
                </div>

                <div class="step-item active">
                    <span class="step-index">3</span>
                    <div class="step-text">
                        <strong>Thông tin khác</strong>
                        <small>Gợi ý tour & xác nhận</small>
                    </div>
                </div>
            </div>

            {{-- ========== TÓM TẮT YÊU CẦU ========== --}}
            @php
                $tourTypeRaw = $requestData['tour_type'] ?? 'group';
                $tourTypeLabel = $tourTypeRaw === 'private' ? 'Tour cá nhân' : 'Tour đoàn';
            @endphp

            <div class="summary-box mb-4">
                <h4 class="mb-3">Tóm tắt yêu cầu của bạn</h4>

                <div class="row g-3">
                    {{-- Điểm đến chính --}}
                    <div class="col-md-4">
                        <div class="summary-label">Điểm đến chính</div>
                        <div class="summary-value">
                            @foreach ($requestData['main_destinations'] as $d)
                                <span class="badge badge-soft-green me-1 mb-1">{{ $d }}</span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Thời gian --}}
                    <div class="col-md-4">
                        <div class="summary-label">Khoảng thời gian</div>
                        <div class="summary-value">
                            {{ \Carbon\Carbon::parse($requestData['start_date'])->format('d/m/Y') }}
                            –
                            {{ \Carbon\Carbon::parse($requestData['end_date'])->format('d/m/Y') }}
                            <br>
                            <small>{{ $requestData['days'] }} ngày – {{ $requestData['nights'] }} đêm</small>
                        </div>
                    </div>

                    {{-- Ngân sách & số khách --}}
                    <div class="col-md-4">
                        <div class="summary-label">Ngân sách & số khách</div>
                        <div class="summary-value">
                            ~ {{ number_format($requestData['budget_per_person'], 0, ',', '.') }} đ/người<br>
                            {{ $requestData['adults'] }} người lớn
                            @if ($requestData['children'] > 0)
                                + {{ $requestData['children'] }} trẻ em
                            @endif
                        </div>
                    </div>

                    {{-- Loại tour --}}
                    <div class="col-md-4">
                        <div class="summary-label">Loại tour</div>
                        <div class="summary-value">
                            @if ($tourTypeRaw === 'group')
                                <span class="badge badge-soft-green">Tour đoàn</span>
                                <small class="d-block text-muted mt-1">
                                    Đi theo đoàn, có ưu đãi theo số lượng khách.
                                </small>
                            @else
                                <span class="badge badge-soft-blue">Tour cá nhân</span>
                                <small class="d-block text-muted mt-1">
                                    Lịch trình riêng tư, linh hoạt cho nhóm nhỏ / gia đình.
                                </small>
                            @endif
                        </div>
                    </div>

                    {{-- Sở thích --}}
                    <div class="col-md-4">
                        <div class="summary-label">Sở thích / loại hình</div>
                        <div class="summary-value">
                            @forelse ($requestData['interests'] as $i)
                                <span class="badge badge-soft-blue me-1 mb-1">{{ $i }}</span>
                            @empty
                                <span class="text-muted">Chưa chọn</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Mức khách sạn --}}
                    <div class="col-md-4">
                        <div class="summary-label">Mức khách sạn mong muốn</div>
                        <div class="summary-value">
                            {{ $requestData['hotel_level'] }}
                        </div>
                    </div>

                    {{-- Cường độ lịch trình --}}
                    <div class="col-md-6">
                        <div class="summary-label">Cường độ lịch trình</div>
                        <div class="summary-value">
                            {{ $requestData['intensity'] }}
                        </div>
                    </div>

                    {{-- Điểm bắt buộc --}}
                    <div class="col-md-6">
                        <div class="summary-label">Các điểm ưu tiên muốn ghé</div>
                        <div class="summary-value">
                            @forelse ($requestData['must_visit_places'] as $p)
                                <span class="badge badge-soft-gray me-1 mb-1">{{ $p }}</span>
                            @empty
                                <span class="text-muted">Không có yêu cầu cụ thể</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Ghi chú --}}
                    @if (!empty($requestData['note']))
                        <div class="col-12">
                            <div class="summary-label">Ghi chú đặc biệt</div>
                            <div class="summary-value">
                                {{ $requestData['note'] }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ========== DANH SÁCH TOUR GỢI Ý ========== --}}
            <h4 class="section-title">Các phương án tour theo yêu cầu</h4>
            <p class="section-desc">
                Hệ thống đã tạo ra {{ count($generatedTours) }} phương án tour phù hợp dựa trên thông tin bạn cung cấp.
                Vui lòng chọn 1 tour để tiếp tục đặt.
            </p>

            @if (count($generatedTours))
                <div class="suggested-tour-list">
                    @foreach ($generatedTours as $option)
                        @php
                            $optionTourType = $option['tour_type'] ?? $tourTypeRaw;
                            $isGroupTour = $optionTourType === 'group';
                            $discountPercent = (int)($option['group_discount_percent'] ?? 0);
                        @endphp

                        <div class="suggested-tour-item mb-3 p-3 p-md-4 bg-white rounded shadow-sm">
                            {{-- Header: phương án + mã + loại tour --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                                    <span class="badge badge-soft-yellow">
                                        Phương án {{ $option['option_index'] }}
                                    </span>

                                    @if ($isGroupTour)
                                        <span class="badge badge-soft-green">
                                            Tour đoàn
                                        </span>
                                    @else
                                        <span class="badge badge-soft-blue">
                                            Tour cá nhân
                                        </span>
                                    @endif

                                    @if ($isGroupTour && $discountPercent > 0)
                                        <span class="badge badge-soft-red">
                                            Ưu đãi đoàn -{{ $discountPercent }}% / khách
                                        </span>
                                    @endif
                                </div>

                                <small class="text-muted">
                                    Mã: {{ $option['code'] }}
                                </small>
                            </div>

                            {{-- Nội dung chính: chia 2 cột --}}
                            <div class="row g-3 align-items-stretch">
                                <div class="col-md-8">
                                    <div class="tour-card-title mb-1">
                                        {{ $option['title'] }}
                                    </div>

                                    <div class="tour-card-meta mb-2">
                                        {{ $option['days'] }}N{{ $option['nights'] }}Đ •
                                        {{ $option['hotel_level'] }} • {{ $option['intensity'] }}
                                    </div>

                                    @if (!empty($option['highlights']))
                                        <div class="mb-2">
                                            <strong>Ưu tiên ghé:</strong><br>
                                            @foreach ($option['highlights'] as $p)
                                                <span class="badge badge-soft-gray me-1 mb-1">{{ $p }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (!empty($option['itinerary']))
                                        <div class="mb-2 mb-md-0">
                                            <strong class="d-block mb-1">Lịch trình dự kiến:</strong>
                                            <ul class="mt-1 ps-3 small mb-0">
                                                @foreach ($option['itinerary'] as $day)
                                                    <li class="mb-1">
                                                        <strong>{{ $day['day'] }}:</strong>
                                                        {{ $day['description'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>

                                {{-- Giá + nút xem chi tiết --}}
<div class="col-md-4 d-flex flex-column justify-content-between text-md-end">
    <div class="mb-3">
        <strong>Giá dự kiến:</strong><br>
       @php
    $adultPrice  = $option['price_per_adult'] ?? ($option['price_per_person'] ?? 0);
    $childPrice  = $option['price_per_child'] ?? (int) round($adultPrice * 0.75 / 1000) * 1000;
    $totalPrice  = $option['total_price'] ?? 0;
@endphp 
<span class="tour-card-price d-block">
    {{ number_format($adultPrice, 0, ',', '.') }} đ / người lớn
</span>
<span class="text-muted small d-block">
    (Trẻ em ước tính: {{ number_format($childPrice, 0, ',', '.') }} đ)
</span>
<span class="text-success fw-bold d-block mt-1">
    Tổng khoảng: {{ number_format($totalPrice, 0, ',', '.') }} đ
</span>

        @if ($isGroupTour && $discountPercent > 0)
            <span class="small text-success d-block mt-1">
                Đã áp dụng ưu đãi đoàn: giảm {{ $discountPercent }}% / khách
            </span>
        @endif
    </div>

    {{-- Chuyển sang trang xem chi tiết phương án --}}
    <form action="{{ route('build-tour.detail', ['index' => $option['option_index']]) }}" method="GET" class="mt-auto mb-0">
        <button type="submit" class="btn-tour-modern">
            Xem chi tiết <span class="arrow">↗</span>
        </button>
    </form>
</div>

                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning mt-3">
                    Hiện tại hệ thống chưa tạo được phương án tour phù hợp.
                    Vui lòng thử lại hoặc liên hệ trực tiếp nhân viên tư vấn.
                </div>
            @endif

            {{-- ========== FOOTER ACTIONS ========== --}}
            <div class="build-tour-footer">
                <a href="{{ url('/build-tour') }}" class="btn-build-tour">
                    <i class="fas fa-redo-alt"></i>
                    Thiết kế tour khác
                </a>
            </div>

        </div>
    </div>
</section>

@include('clients.blocks.footer')
