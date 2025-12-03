{{-- resources/views/clients/build_tour.blade.php --}}
@if ($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@include('clients.blocks.header', [
    'title' => ' | ' . ($title ?? 'Thiết kế Tour theo yêu cầu'),
    'includeBuildTourCss' => true,
])
@include('clients.blocks.banner', ['title' => $title ?? 'Thiết kế Tour theo yêu cầu'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/build-tour.css') }}">
@endpush

@php
    $formData = $formData ?? [];
    $tourType   = $formData['tour_type']   ?? 'Tour ghép';
    $hotelLevel = $formData['hotel_level'] ?? '2-3 sao';
    $intensity  = $formData['intensity']   ?? 'Nhẹ';
    $oldInterests = $formData['interests'] ?? [];
@endphp

<section class="pt-80 pb-80" style="background: #f3f4f6;">
    <div class="container">
        <div class="build-tour-wrapper">
            <div class="build-tour-header text-center mb-4">
                <h2>Thiết Kế Tour Theo Yêu Cầu</h2>
                <p>Hãy cho chúng tôi biết nhu cầu của bạn. Hệ thống sẽ tạo ra 2–3 lịch trình phù hợp nhất.</p>
            </div>

            <form class="build-tour-form" action="{{ route('build-tour.submit') }}" method="POST" id="buildTourForm">
                @csrf

                {{-- THANH BƯỚC --}}
                <div class="steps-indicator mb-3">
                    <div class="step-item active" id="stepIndicator1">
                        <span class="step-index">1</span>
                        <div class="step-text">
                            <strong>Điểm đến & Ngày đi</strong>
                            <small>Chọn nơi muốn đến và khoảng thời gian</small>
                        </div>
                    </div>

                    <div class="step-item" id="stepIndicator2">
                        <span class="step-index">2</span>
                        <div class="step-text">
                            <strong>Ngân sách & Sở thích</strong>
                            <small>Thông tin chi tiết hơn</small>
                        </div>
                    </div>

                    <div class="step-item disabled">
                        <span class="step-index">3</span>
                        <div class="step-text">
                            <strong>Thông tin khác</strong>
                            <small>Gợi ý tour & xác nhận</small>
                        </div>
                    </div>
                </div>

                {{-- ========== STEP 1 ========== --}}
                <div id="step1Section">
                    <div class="row g-4">
                        {{-- CỘT TRÁI --}}
                        <div class="col-md-7">
                            {{-- Điểm đến chính --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    Điểm đến chính <span class="text-danger">*</span>
                                </label>

                                <div class="destination-select">
                                    <div class="destination-search-input">
                                        <input type="text" id="destinationSearch" class="form-control"
                                            placeholder="Ví dụ: Phú Quốc, Đà Nẵng, Nha Trang..." autocomplete="off">
                                        <i class="far fa-search"></i>
                                    </div>

                                    {{-- Gợi ý từ API --}}
                                    <div id="destinationSuggestions" class="destination-suggestions"></div>

                                    {{-- Các điểm đã chọn --}}
                                    <div id="selectedDestinations" class="selected-destinations"></div>

                                    {{-- Hidden input gửi server (JSON mảng tên điểm đến) --}}
                                    <input type="hidden" name="main_destinations" id="mainDestinationsInput">
                                </div>

                                <div class="form-text">
                                    Bạn có thể chọn 1 hoặc nhiều tỉnh/thành hoặc địa danh nổi bật. Gõ vài ký tự để tìm kiếm.
                                    
                                </div>
                            </div>

                            {{-- Các điểm bắt buộc --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    Các điểm bắt buộc muốn ghé <span class="text-danger">*</span>
                                </label>

                                <div id="mustVisitContainer" class="must-visit-wrapper" style="display:none;">
                                    <div class="must-visit-title">
                                        Chọn ít nhất <strong>2 địa danh</strong> bạn muốn ưu tiên ghé:
                                    </div>
                                    <div id="mustVisitList" class="must-visit-grid">
                                        {{-- JS render --}}
                                    </div>
                                </div>

                                <div class="form-text">
                                    Sau khi bạn chọn điểm đến chính, hệ thống sẽ gợi ý các điểm tham quan nổi bật để bạn
                                    chọn.
                                </div>
                            </div>
                        </div>

                        {{-- CỘT PHẢI --}}
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label">
                                    Ngày khởi hành dự kiến <span class="text-danger">*</span>
                                </label>
                                <input
    type="date"
    name="start_date"
    id="startDate"
    class="form-control"
    required
    min="{{ \Carbon\Carbon::now()->addDays(3)->format('Y-m-d') }}"
>
<div class="form-text">
    Chọn ngày bắt đầu hành trình (cách hôm nay ít nhất 3 ngày).
</div>

                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Ngày kết thúc dự kiến <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="end_date" id="endDate" class="form-control" required>
                                <div class="form-text">
                                    Hệ thống sẽ tự tính số ngày/đêm dựa trên khoảng thời gian này.
                                </div>
                            </div>
                            
            
                            {{-- Thông tin số ngày/đêm + hidden để gửi server --}}
                            <div class="small text-muted" id="daysNightsInfo" style="display:none;">
                                Dự kiến: <span id="daysText"></span> ngày <span id="nightsText"></span> đêm.
                            </div>
                            <input type="hidden" name="days" id="daysInput">
                            <input type="hidden" name="nights" id="nightsInput">

                        
                            <div class="alert alert-light border rounded-3 small mt-3"
                                style="color:#14532d; border-color:#bbf7d0;">
                                <i class="far fa-info-circle"></i>
                                <span class="ms-1">
                                    Ở bước sau, bạn sẽ chọn ngân sách, số khách, mức khách sạn và cường độ lịch trình.
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- FOOTER STEP 1 --}}
                    <div class="build-tour-footer">
                        <div class="small text-muted">
                            Bước 1/2 – Thông tin cơ bản về <strong>điểm đến</strong> và <strong>thời gian</strong>.
                        </div>
                        <button type="button" class="btn-build-tour" id="goToStep2">
                            <i class="far fa-arrow-right"></i>
                            Tiếp tục – Ngân sách & Sở thích
                        </button>
                    </div>
                </div>

                {{-- ========== STEP 2 ========== --}}
                <div id="step2Section" style="display:none;">
                    <div class="row g-4">
                        {{-- CỘT TRÁI: Ngân sách + Sở thích + Ghi chú --}}
                        <div class="col-md-7">
                            {{-- Ngân sách --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    Ngân sách dự kiến / 1 người <span class="text-danger">*</span>
                                </label>
                                <div class="budget-range-wrapper">
                                    <input type="range" id="budgetRange" name="budget_per_person"
                                        class="form-range flex-grow-1" min="1000000" max="10000000" step="500000"
                                        value="2000000">
                                    <span id="budgetValue" class="budget-value">~ 2.000.000 đ</span>
                                </div>
                                <div class="form-text">
                                    Ước lượng số tiền bạn có thể chi cho mỗi người.
                                </div>
                            </div>

                            {{-- Sở thích / loại hình --}}
                            <div class="mb-3">
                            <label class="form-label">
                                Sở thích / loại hình bạn muốn <span class="text-danger">*</span>
                            </label>

                            <div class="interest-wrapper">
                                @php
                                    $interests = ['Tham quan', 'Trải nghiệm', 'Nghỉ dưỡng', 'Ăn uống', 'Vui chơi – giải trí','Khác'];
                                @endphp

                                @foreach ($interests as $interest)
                                    <label class="interest-pill">
                                        <input type="checkbox" name="interests[]" value="{{ $interest }}">
                                        <span>{{ $interest }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="form-text">
                                Có thể chọn nhiều loại hình cùng lúc.
                            </div>
                        </div>


                            {{-- Ghi chú thêm --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    Ghi chú thêm (nếu có)
                                </label>
                                <textarea name="note" rows="3" class="form-control"
                                    placeholder="Yêu cầu đặc biệt về ăn uống, sức khỏe, trẻ nhỏ, người già,..."></textarea>
                            </div>
                        </div>

                        {{-- CỘT PHẢI: Số khách + Mức khách sạn + Cường độ --}}
<div class="col-md-5">
    {{-- Số lượng khách --}}
    <div class="mb-3">
        <label class="form-label">
            Số lượng khách <span class="text-danger">*</span>
        </label>
        <div class="row g-2">
            <div class="col-6">
                <div class="guest-input-pill">
                    <input type="number" name="adults" min="1"
                           class="guest-input-field"
                           placeholder="Người lớn" required>
                </div>
            </div>
            <div class="col-6">
                <div class="guest-input-pill">
                    <input type="number" name="children" min="0"
                           class="guest-input-field"
                           placeholder="Trẻ em">
                </div>
            </div>
        </div>

        <div class="form-text">
     Hệ thống sẽ tự áp dụng ưu đãi theo số lượng khách: Càng đông – càng rẻ (ưu đãi lên đến ~8%)
    </div>
    </div>


                            {{-- Mức khách sạn --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    Mức khách sạn mong muốn
                                </label>
                                <div class="chip-group">
                                    <label class="chip-item active">
                                        <input type="radio" name="hotel_level" value="2-3 sao" checked>
                                        <span>2–3 sao</span>
                                    </label>
                                    <label class="chip-item">
                                        <input type="radio" name="hotel_level" value="3-4 sao">
                                        <span>3–4 sao</span>
                                    </label>
                                    <label class="chip-item">
                                        <input type="radio" name="hotel_level" value="Resort">
                                        <span>Resort / cao cấp</span>
                                    </label>
                                    <label class="chip-item">
                                        <input type="radio" name="hotel_level" value="Chưa biết">
                                        <span>Chưa biết</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Cường độ lịch trình --}}
                            <div class="mb-3">
                                <label class="form-label">
                                    Cường độ lịch trình
                                </label>
                                <div class="chip-group">
                                    <label class="chip-item active">
                                        <input type="radio" name="intensity" value="Nhẹ" checked>
                                        <span>Nhẹ – thư giãn</span>
                                    </label>
                                    <label class="chip-item">
                                        <input type="radio" name="intensity" value="Vừa">
                                        <span>Vừa – cân bằng</span>
                                    </label>
                                    <label class="chip-item">
                                        <input type="radio" name="intensity" value="Dày">
                                        <span>Dày – đi nhiều điểm</span>
                                    </label>
                                    <label class="chip-item">
                                    <input type="radio" name="intensity" value="Chưa biết">
                                    <span>Chưa biết / linh hoạt</span>
                                </label>
                                </div>
                                <div class="form-text">
                                    Ảnh hưởng đến số lượng điểm tham quan trong ngày.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FOOTER STEP 2 --}}
                    <div class="build-tour-footer">
                        <button type="button" class="btn btn-link p-0 small" id="backToStep1">
                            ← Quay lại bước 1
                        </button>

                        <button type="submit" class="btn-build-tour">
                            <i class="far fa-paper-plane"></i>
                            Gửi yêu cầu & xem gợi ý tour
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</section>

<script>
    // ===================== NGÂN SÁCH (STEP 2) =====================
    const range = document.getElementById('budgetRange');
    const value = document.getElementById('budgetValue');

    if (range && value) {
        const formatCurrency = (number) => number.toLocaleString('vi-VN') + ' đ';

        const updateBudgetLabel = () => {
            const v = parseInt(range.value || 0, 10);
            value.textContent = '~ ' + formatCurrency(v);
        };

        range.addEventListener('input', updateBudgetLabel);
        updateBudgetLabel();
    }

    // ===================== ELEMENTS CHUNG =====================
    const destinationSearch      = document.getElementById('destinationSearch');
    const suggestionsEl          = document.getElementById('destinationSuggestions');
    const selectedWrapper        = document.getElementById('selectedDestinations');
    const hiddenMainDestinations = document.getElementById('mainDestinationsInput');
    const mustVisitContainer     = document.getElementById('mustVisitContainer');
    const mustVisitList          = document.getElementById('mustVisitList');

    const step1Section   = document.getElementById('step1Section');
    const step2Section   = document.getElementById('step2Section');
    const stepIndicator1 = document.getElementById('stepIndicator1');
    const stepIndicator2 = document.getElementById('stepIndicator2');

    const daysInfoEl   = document.getElementById('daysNightsInfo');
    const daysTextEl   = document.getElementById('daysText');
    const nightsTextEl = document.getElementById('nightsText');
    const daysInput    = document.getElementById('daysInput');
    const nightsInput  = document.getElementById('nightsInput');

    let selectedDestinations = []; // [{id, name, popular_places: []}]
    let tempSuggestions      = []; // kết quả API

    // ===================== GỌI API ĐIỂM ĐẾN =====================
    async function fetchDestinations(query) {
        if (!query) return [];

        const q = query.trim();
        if (!q) return [];

        try {
            const res = await fetch(`/api/destinations?q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                console.error('Destinations API error:', res.status);
                return [];
            }
            const data = await res.json();
            return Array.isArray(data) ? data : [];
        } catch (e) {
            console.error('Fetch destinations error', e);
            return [];
        }
    }

    // ===================== RENDER GỢI Ý =====================
    function renderSuggestionsList(data) {
        suggestionsEl.innerHTML = '';

        if (!data.length) {
            suggestionsEl.classList.remove('active');
            return;
        }

        data.forEach(item => {
            const div = document.createElement('div');
            div.className = 'destination-suggestion-item';
            div.innerHTML = `
                <div class="destination-suggestion-icon">
                    <i class="far fa-map-marker-alt"></i>
                </div>
                <div class="destination-suggestion-content">
                    <div class="destination-suggestion-name">${item.name}</div>
                </div>
            `;

            div.addEventListener('click', () => {
                addDestination(item);
                destinationSearch.value = '';
                suggestionsEl.classList.remove('active');
            });

            suggestionsEl.appendChild(div);
        });

        suggestionsEl.classList.add('active');
    }

    // ===================== INPUT SUGGEST =====================
    if (destinationSearch) {
        destinationSearch.addEventListener('input', async function () {
            const q = this.value.trim();
            if (q.length < 1) {
                suggestionsEl.classList.remove('active');
                return;
            }
            const data = await fetchDestinations(q);
            tempSuggestions = data;
            renderSuggestionsList(data);
        });

        destinationSearch.addEventListener('focus', async function () {
            const q = this.value.trim();
            if (q.length < 1) return;
            const data = await fetchDestinations(q);
            tempSuggestions = data;
            renderSuggestionsList(data);
        });

        // Enter -> chọn gợi ý đầu tiên
        destinationSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (tempSuggestions.length > 0) {
                    addDestination(tempSuggestions[0]);
                }
                destinationSearch.value = '';
                suggestionsEl.classList.remove('active');
            }
        });

        // Click ra ngoài để ẩn dropdown
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.destination-select')) {
                suggestionsEl.classList.remove('active');
            }
        });
    }

    // ===================== THÊM ĐIỂM ĐẾN + CHIPS =====================
    function addDestination(item) {
        if (!selectedDestinations.find(x => x.id === item.id)) {
            selectedDestinations.push(item);
        }
        renderSelectedChips();
        renderMustVisitPlaces();
    }

    function renderSelectedChips() {
        selectedWrapper.innerHTML = '';

        selectedDestinations.forEach(item => {
            const chip = document.createElement('div');
            chip.className = 'selected-chip';
            chip.innerHTML = `
                <span>${item.name}</span>
                <button type="button">&times;</button>
            `;
            chip.querySelector('button').onclick = () => {
                selectedDestinations = selectedDestinations.filter(x => x.id !== item.id);
                renderSelectedChips();
                renderMustVisitPlaces();
            };
            selectedWrapper.appendChild(chip);
        });

        // Lưu JSON tên điểm đến gửi lên server
        hiddenMainDestinations.value = JSON.stringify(selectedDestinations.map(x => x.name));
    }

    // ===================== CÁC ĐIỂM BẮT BUỘC MUỐN GHÉ =====================
function renderMustVisitPlaces() {
    mustVisitList.innerHTML = '';

    let places = [];

    selectedDestinations.forEach(function (item) {
        (item.popular_places || []).forEach(function (raw) {

            // TH1: raw là chuỗi JSON kiểu ["A","B","C"]
            if (typeof raw === 'string' && raw.trim().startsWith('[')) {
                try {
                    const arr = JSON.parse(raw);
                    if (Array.isArray(arr)) {
                        arr.forEach(function (p) {
                            if (p && !places.includes(p)) {
                                places.push(p);
                            }
                        });
                        return; // đã xử lý xong raw này
                    }
                } catch (e) {
                    console.warn('Parse popular_places JSON error', e);
                    // fall-through xuống TH2
                }
            }

            // TH2: raw là 1 tên địa danh bình thường
            if (raw && !places.includes(raw)) {
                places.push(raw);
            }
        });
    });

    if (!places.length) {
        mustVisitContainer.style.display = 'none';
        return;
    }

    // Tạo checkbox + pill cho từng địa điểm
    places.forEach(function (place) {
        const id = 'mustvisit_' + place.replace(/\s+/g, '_');

        const label = document.createElement('label');
        label.className = 'must-visit-item';
        label.innerHTML =
            '<input type="checkbox" name="must_visit_places[]" value="' + place + '" id="' + id + '">' +
            '<span>' + place + '</span>';

        mustVisitList.appendChild(label);
    });

    mustVisitContainer.style.display = 'block';
}

    // ===================== HỖ TRỢ HIỂN THỊ LỖI =====================
    function clearFieldErrors() {
        document.querySelectorAll('.form-control.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
        mustVisitContainer.classList.remove('has-error');
    }

    function showFieldError(inputEl, message) {
        if (!inputEl) return;
        inputEl.classList.add('is-invalid');

        const group = inputEl.closest('.mb-3') || inputEl.parentElement;
        if (!group) return;

        const oldMsg = group.querySelector('.field-error-msg');
        if (oldMsg) oldMsg.remove();

        const msg = document.createElement('div');
        msg.className = 'field-error-msg';
        msg.textContent = message;
        group.appendChild(msg);
    }

    // ===================== TÍNH NGÀY / ĐÊM =====================
    function calcDaysNights() {
        const startDateVal = document.getElementById('startDate').value;
        const endDateVal   = document.getElementById('endDate').value;

        if (!startDateVal || !endDateVal) {
            daysInfoEl.style.display = 'none';
            daysInput.value = '';
            nightsInput.value = '';
            return;
        }

        const start = new Date(startDateVal);
        const end   = new Date(endDateVal);
        if (end < start) {
            daysInfoEl.style.display = 'none';
            daysInput.value = '';
            nightsInput.value = '';
            return;
        }

        const diffMs   = end - start;
        const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24)) + 1; // tính cả 2 đầu
        const nights   = Math.max(diffDays - 1, 0);

        daysTextEl.textContent   = diffDays;
        nightsTextEl.textContent = nights;
        daysInput.value          = diffDays;
        nightsInput.value        = nights;

        daysInfoEl.style.display = 'block';
    }

    document.getElementById('startDate').addEventListener('change', calcDaysNights);
    document.getElementById('endDate').addEventListener('change', calcDaysNights);

    // ===================== VALIDATE STEP 1 & CHUYỂN STEP 2 =====================
    document.getElementById('goToStep2').addEventListener('click', function () {
        clearFieldErrors();

        const mainDestinations = JSON.parse(hiddenMainDestinations.value || '[]');
        const startDateVal     = document.getElementById('startDate').value;
        const endDateVal       = document.getElementById('endDate').value;
        const mustVisitChecked = document.querySelectorAll('input[name="must_visit_places[]"]:checked').length;

        let hasError     = false;
        let firstErrorEl = null;

        // Điểm đến chính
        if (mainDestinations.length === 0) {
            hasError = true;
            const el = destinationSearch;
            showFieldError(el, 'Vui lòng chọn ít nhất 1 điểm đến chính.');
            firstErrorEl = firstErrorEl || el;
        }

        // Ngày khởi hành
        if (!startDateVal) {
            hasError = true;
            const el = document.getElementById('startDate');
            showFieldError(el, 'Vui lòng chọn ngày khởi hành.');
            firstErrorEl = firstErrorEl || el;
        }

        // Ngày kết thúc
        if (!endDateVal) {
            hasError = true;
            const el = document.getElementById('endDate');
            showFieldError(el, 'Vui lòng chọn ngày kết thúc.');
            firstErrorEl = firstErrorEl || el;
        } else if (startDateVal && new Date(endDateVal) < new Date(startDateVal)) {
            hasError = true;
            const el = document.getElementById('endDate');
            showFieldError(el, 'Ngày kết thúc phải sau ngày khởi hành.');
            firstErrorEl = firstErrorEl || el;
        }

        // Các điểm bắt buộc
        if (mustVisitChecked < 2) {
            hasError = true;
            mustVisitContainer.classList.add('has-error');

            let msg = mustVisitContainer.querySelector('.field-error-msg');
            if (!msg) {
                msg = document.createElement('div');
                msg.className = 'field-error-msg';
                mustVisitContainer.appendChild(msg);
            }
            msg.textContent = 'Vui lòng chọn ít nhất 2 địa điểm bắt buộc muốn ghé.';

            firstErrorEl = firstErrorEl || mustVisitContainer;
        }

        if (hasError) {
            if (firstErrorEl && firstErrorEl.scrollIntoView) {
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (firstErrorEl && firstErrorEl.focus) {
                firstErrorEl.focus();
            }
            return;
        }

        // Nếu không lỗi -> tính days/nights + chuyển Step 2
        calcDaysNights();

        step1Section.style.display = 'none';
        step2Section.style.display = 'block';

        stepIndicator1.classList.remove('active');
        stepIndicator2.classList.add('active');
    });

    // ===================== QUAY LẠI STEP 1 =====================
    document.getElementById('backToStep1').addEventListener('click', function () {
        step2Section.style.display = 'none';
        step1Section.style.display = 'block';

        stepIndicator2.classList.remove('active');
        stepIndicator1.classList.add('active');
    });

    // ===================== ACTIVE CHIP RADIO (STEP 2) =====================
    document.querySelectorAll('.chip-group input[type="radio"]').forEach(input => {
        input.addEventListener('change', () => {
            const group = input.closest('.chip-group');
            group.querySelectorAll('.chip-item').forEach(label => label.classList.remove('active'));
            input.closest('.chip-item').classList.add('active');
        });
    });
</script>

@include('clients.blocks.footer')
