<!-- Hero Area Start -->
<section class="hero-area bgc-black pt-200 rpt-120 rel z-2">
    <div class="container-fluid">
        <h1 class="hero-title" data-aos="flip-up" data-aos-delay="50" data-aos-duration="1500" data-aos-offset="50">
            Tours Du Lịch</h1>
        <div class="main-hero-image bgs-cover"
            style="background-image: url({{ asset('clients/assets/images/hero/hero.jpg') }});">
        </div>
    </div>
    <form action="{{ route('search') }}" method="GET" id="search_form">

        <div class="container container-1400">
            <div class="search-filter-inner" data-aos="zoom-out-down" data-aos-duration="1400" data-aos-offset="50">

                <!-- Cột 1 -->
                <div class="filter-item" style="display: flex; gap: 12px; width: 32%;">
                    <div class="icon"><i class="fal fa-map-marker-alt"></i></div>
                    <div class="destination-wrapper"
                        style="display:flex; flex-direction: column; width:100%; position:relative;">
                        <span class="title" style="font-weight: bold;">Bạn muốn đi đâu?</span>
                        <input type="text" id="destination" name="keyword"
                            placeholder="ví dụ: Fansipan, Nha Trang, Đà Nẵng..." class="my-input" autocomplete="off">
                        <div id="suggest-box" class="suggest-box"></div>

                        <button type="button" id="open-map-btn" style="margin-top:6px; align-self:flex-start;
                               padding:4px 10px; border-radius:999px;
                               border:1px solid #4caf50; background:#fff;
                               font-size:12px; color:#2e7d32; cursor:pointer;">
                            Chọn trên bản đồ
                        </button>

                        <input type="hidden" name="lat" id="search_lat">
                        <input type="hidden" name="lng" id="search_lng">
                    </div>
                </div>

                <!-- Cột 2 -->
                <div class="filter-item" style="width: 22%;">
                    <div class="icon"><i class="fal fa-calendar-alt"></i></div>
                    <span class="title">Ngày khởi hành</span>
                    <input type="text" id="start_date" name="start_date" class="datetimepicker datetimepicker-custom"
                        placeholder="Chọn ngày đi" readonly>
                </div>

                <!-- Cột 3 -->
                <div class="filter-item" style="width: 22%;">
                    <div class="icon"><i class="fal fa-calendar-alt"></i></div>
                    <span class="title">Ngày kết thúc</span>
                    <input type="text" id="end_date" name="end_date" class="datetimepicker datetimepicker-custom"
                        placeholder="Chọn ngày về" readonly>
                </div>

                <!-- Nút Tìm kiếm -->
                <div class="search-button" style="width: 22%; display:flex; justify-content:center;">
                    <button class="theme-btn" type="submit">
                        <span>Tìm Kiếm</span>
                        <i class="far fa-search"></i>
                    </button>
                </div>

            </div>
        </div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('destination');
            const box = document.getElementById('suggest-box');
            const form = document.getElementById('search_form');
            const HISTORY_KEY = 'travela_search_history';

            // ===== DANH SÁCH ĐIỂM ĐẾN LỚN =====
            const MAJOR_DESTINATIONS = @json($destinations ?? [])
                .map(label => ({ label, value: label }));

            // ===== Helper: bỏ dấu tiếng Việt để search không dấu =====
            function removeVN(str = '') {
                return str.normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/đ/g, "d").replace(/Đ/g, "D")
                    .toLowerCase();
            }

            // ===== LỊCH SỬ TÌM KIẾM =====
            function getHistory() {
                try {
                    return JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
                } catch (e) {
                    console.error(e);
                    return [];
                }
            }

            function saveHistory(keyword) {
                const key = (keyword || '').trim();
                if (!key) return;

                let history = getHistory();

                // Xoá trùng (so sánh không phân biệt hoa thường)
                const lower = key.toLowerCase();
                history = history.filter(item => item.toLowerCase() !== lower);

                // Cho từ khoá mới lên đầu
                history.unshift(key);

                // Giữ tối đa 5 cái gần nhất
                history = history.slice(0, 5);

                localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
            }

            // ===== HIỂN THỊ / ẨN BOX =====
            function showBox() {
                if (box.innerHTML.trim() === '') {
                    box.style.display = 'none';
                } else {
                    box.style.display = 'block';
                }
            }

            function hideBox() {
                box.style.display = 'none';
            }

            // ===== DROPDOWN MẶC ĐỊNH (khi chưa gõ gì) =====
            function renderDefaultDropdown() {
                box.innerHTML = '';

                const history = getHistory();

                // --- LỊCH SỬ TÌM KIẾM (tối đa 5 cái) ---
                if (history.length > 0) {
                    const titleH = document.createElement('div');
                    titleH.className = 'suggest-section-title';
                    titleH.textContent = 'Lịch sử tìm kiếm';
                    box.appendChild(titleH);

                    history.slice(0, 5).forEach(term => {
                        const item = document.createElement('div');
                        item.className = 'suggest-item suggest-history';
                        item.innerHTML = `
                    <div class="suggest-icon"><i class="fal fa-clock"></i></div>
                    <div class="suggest-name">${term}</div>
                `;
                        item.addEventListener('click', function () {
                            input.value = term;
                            hideBox();
                            submitSearch();
                        });
                        box.appendChild(item);
                    });
                }

                // --- ĐIỂM ĐẾN GỢI Ý ---
                const titleD = document.createElement('div');
                titleD.className = 'suggest-section-title';
                titleD.textContent = 'Điểm đến';
                box.appendChild(titleD);

                MAJOR_DESTINATIONS.slice(0, 6).forEach(place => {
                    const item = document.createElement('div');
                    item.className = 'suggest-item';
                    item.innerHTML = `
                <div class="suggest-icon"><i class="fal fa-map-marker-alt"></i></div>
                <div class="suggest-name">${place.label}</div>
            `;
                    item.addEventListener('click', function () {
                        input.value = place.value;
                        hideBox();
                        submitSearch();
                    });
                    box.appendChild(item);
                });

                showBox();
            }

            // ===== DROPDOWN KHI ĐANG GÕ =====
            function renderSearchDropdown(query) {
                const q = query.trim();
                if (!q) {
                    renderDefaultDropdown();
                    return;
                }

                const nq = removeVN(q);
                const list = MAJOR_DESTINATIONS.filter(p =>
                    removeVN(p.label).includes(nq)
                ).slice(0, 10);

                box.innerHTML = '';

                const title = document.createElement('div');
                title.className = 'suggest-section-title';
                title.textContent = 'Gợi ý điểm đến';
                box.appendChild(title);

                if (list.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'suggest-empty';
                    empty.textContent = 'Không tìm thấy điểm đến phù hợp';
                    box.appendChild(empty);
                    showBox();
                    return;
                }

                list.forEach(place => {
                    const item = document.createElement('div');
                    item.className = 'suggest-item';
                    item.innerHTML = `
                <div class="suggest-icon"><i class="fal fa-map-marker-alt"></i></div>
                <div class="suggest-name">${place.label}</div>
            `;
                    item.addEventListener('click', function () {
                        input.value = place.value;
                        hideBox();
                        submitSearch();
                    });
                    box.appendChild(item);
                });

                showBox();
            }

            // ===== SUBMIT FORM /search + lưu lịch sử =====
            function submitSearch() {
                const term = input.value.trim();
                if (!term) return;
                saveHistory(term);
                form.submit();
            }

            // ===== EVENTS =====
            input.addEventListener('focus', function () {
                if (!input.value.trim()) {
                    renderDefaultDropdown();
                } else {
                    renderSearchDropdown(input.value);
                }
            });

            input.addEventListener('input', function () {
                renderSearchDropdown(input.value);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitSearch();
                }
                if (e.key === 'Escape') {
                    hideBox();
                }
            });

            // Lưu lịch sử khi submit bằng nút "Tìm kiếm"
            form.addEventListener('submit', function () {
                const kw = input.value.trim();
                if (kw) saveHistory(kw);
            });

            // Ẩn box khi blur (delay chút để click được item)
            input.addEventListener('blur', function () {
                setTimeout(hideBox, 150);
            });
        });
    </script>

</section>
<!-- Hero Area End -->