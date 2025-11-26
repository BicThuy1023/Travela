@include('clients.blocks.header')
@include('clients.blocks.banner')

<section class="tour-grid-page py-100 rel z-2">
    <div class="container">

        {{-- Container kết quả --}}
        <div id="search-results" class="row">
            {{--
            Với trang search thường (/search):
            - Không render kết quả bằng Blade
            - Kết quả sẽ được JS fetch từ /api/search-tours-js và đổ vào đây
            --}}
            @if(isset($isNearby) && $isNearby && isset($tours) && $tours->count())
                @foreach($tours as $tour)
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="destination-item tour-grid style-three bgc-lighter equal-block-fix"
                            data-start-lat="{{ $tour->location_lat }}" data-start-lng="{{ $tour->location_lng }}"
                            data-end-lat="{{ $tour->end_lat }}" data-end-lng="{{ $tour->end_lng }}">

                            <div class="image">
                                <a href="#" class="heart"><i class="fas fa-heart"></i></a>
                                <img src="{{ asset('admin/assets/images/gallery-tours/' . ($tour->images[0] ?? 'no-image.jpg')) }}"
                                    alt="Tour">
                            </div>

                            <div class="content equal-content-fix">
                                <div class="destination-header">
                                    <span class="location">
                                        <i class="fal fa-map-marker-alt"></i> {{ $tour->destination }}
                                    </span>

                                    <div class="ratting">
                                        @php $rating = round($tour->rating ?? 0); @endphp
                                        @for($i = 0; $i < 5; $i++)
                                            @if($rating > $i)
                                                <i class="fas fa-star filled-star"></i>
                                            @else
                                                <i class="far fa-star empty-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                </div>

                                <h5>
                                    <a href="{{ url('/tour-detail/' . $tour->tourId) }}">
                                        {{ $tour->title }}
                                    </a>
                                </h5>

                                <ul class="blog-meta">
                                    <li>
                                        <i class="far fa-clock"></i> {{ $tour->time }}
                                    </li>
                                    <li>
                                        <i class="far fa-user"></i> {{ $tour->quantity }}
                                    </li>

                                    @if(isset($tour->start_distance))
                                        <li>
                                            <i class="far fa-map"></i>
                                            {{ number_format($tour->start_distance, 1) }} km
                                        </li>
                                    @endif

                                    <div class="destination-footer">
                                        <span class="price">
                                            <span>{{ number_format($tour->priceAdult, 0, ',', '.') }}</span> VND / người
                                        </span>
                                        <a href="{{ url('/tour-detail/' . $tour->tourId) }}"
                                            class="theme-btn style-two style-three">
                                            <span data-hover="Đặt ngay">Đặt ngay</span>
                                            <i class="fal fa-arrow-right"></i>
                                        </a>
                                    </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Thông báo loading --}}
        <div id="loading" class="text-center text-white mt-4" style="display:none;">
            <h4>Đang tìm kiếm tour...</h4>
        </div>

    </div>
</section>

@include('clients.blocks.new_letter')

{{-- ===== JS SEARCH ĐẶT Ở ĐÂY, TRƯỚC FOOTER ===== --}}
<script>
    // true nếu trang này là tìm gần vị trí (/nearby-tours)
    const IS_NEARBY = {{ isset($isNearby) && $isNearby ? 'true' : 'false' }};

    // Chỉ chạy JS fetch API cho search bình thường, KHÔNG chạy cho nearby
    if (!IS_NEARBY) {
        document.addEventListener('DOMContentLoaded', function () {
            const baseImageFolder = "{{ asset('admin/assets/images/gallery-tours') }}";
            const defaultImage = "{{ asset('admin/assets/images/gallery-tours/no-image.jpg') }}";

            const resultsContainer = document.getElementById('search-results');
            const loadingDiv = document.getElementById('loading');

            // Từ PHP (form search)
            const keyword = "{{ $keyword ?? '' }}";
            const startDate = "{{ $startDate ?? '' }}";
            const endDate = "{{ $endDate ?? '' }}";

            // 🔥 Lấy thêm toạ độ start/end từ URL (khi đi từ popup map, v.v.)
            const urlParams = new URLSearchParams(window.location.search);
            const startLat = urlParams.get('start_lat');
            const startLng = urlParams.get('start_lng');
            const endLat = urlParams.get('end_lat');
            const endLng = urlParams.get('end_lng');

            const params = new URLSearchParams();

            if (keyword) params.append('keyword', keyword);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            // 🔥 Gửi thêm 4 tham số route nếu có đủ
            if (startLat && startLng && endLat && endLng) {
                params.append('start_lat', startLat);
                params.append('start_lng', startLng);
                params.append('end_lat', endLat);
                params.append('end_lng', endLng);
            }

            console.log('Params gửi lên API:', params.toString());

            loadingDiv.style.display = 'block';

            fetch("{{ url('/api/search-tours-js') }}" + '?' + params.toString())
                .then(r => r.json())
                .then(json => {
                    loadingDiv.style.display = 'none';
                    resultsContainer.innerHTML = '';

                    if (!json.success || !json.data || json.data.length === 0) {
                        resultsContainer.innerHTML = `
                            <h4 class="alert alert-danger">
                                Không tìm thấy tour phù hợp với yêu cầu hiện tại.
                            </h4>
                        `;
                        return;
                    }

                    json.data.forEach(tour => {
                        const col = document.createElement('div');
                        col.className = 'col-xl-4 col-md-6 mb-4';

                        // Ảnh
                        let imageSrc = defaultImage;
                        if (tour.images && Array.isArray(tour.images) && tour.images.length > 0) {
                            imageSrc = baseImageFolder + '/' + tour.images[0];
                        }

                        const titleText = tour.title || '';
                        const destinationText = tour.destination || '';
                        const timeText = tour.time || '';
                        const quantityText = tour.quantity || '';
                        const priceText = tour.priceAdult
                            ? Number(tour.priceAdult).toLocaleString('vi-VN')
                            : '';

                        // ⭐ rating
                        let ratingHtml = '';
                        const rating = Math.round(Number(tour.rating || 0));
                        for (let i = 0; i < 5; i++) {
                            if (rating > i) {
                                ratingHtml += '<i class="fas fa-star filled-star"></i>';
                            } else {
                                ratingHtml += '<i class="far fa-star empty-star"></i>';
                            }
                        }

                        const startLatVal = tour.location_lat ?? '';
                        const startLngVal = tour.location_lng ?? '';
                        const endLatVal = tour.end_lat ?? '';
                        const endLngVal = tour.end_lng ?? '';

                        col.innerHTML = `
                            <div class="destination-item tour-grid style-three bgc-lighter equal-block-fix"
                                 data-start-lat="${startLatVal}"
                                 data-start-lng="${startLngVal}"
                                 data-end-lat="${endLatVal}"
                                 data-end-lng="${endLngVal}">
                                <div class="image">
                                    <a href="#" class="heart"><i class="fas fa-heart"></i></a>
                                    <img src="${imageSrc}" alt="Tour">
                                </div>
                                <div class="content equal-content-fix">
                                    <div class="destination-header">
                                        <span class="location">
                                            <i class="fal fa-map-marker-alt"></i> ${destinationText}
                                        </span>
                                        <div class="ratting">
                                            ${ratingHtml}
                                        </div>
                                    </div>
                                    <h5>
                                        <a href="{{ url('/tour-detail') }}/${tour.tourId}">
                                            ${titleText}
                                        </a>
                                    </h5>
                                    <ul class="blog-meta">
                                        <li><i class="far fa-clock"></i> ${timeText}</li>
                                        <li><i class="far fa-user"></i> ${quantityText}</li>
                                    </ul>
                                    <div class="destination-footer">
                                        <span class="price">
                                            <span>${priceText}</span> VND / người
                                        </span>
                                        <a href="{{ url('/tour-detail') }}/${tour.tourId}"
                                           class="theme-btn style-two style-three">
                                            <span data-hover="Đặt ngay">Đặt ngay</span>
                                            <i class="fal fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;

                        resultsContainer.appendChild(col);
                    });
                })
                .catch(err => {
                    loadingDiv.style.display = 'none';
                    console.error(err);
                    resultsContainer.innerHTML =
                        '<p class="text-danger">Có lỗi xảy ra khi tải dữ liệu tìm kiếm.</p>';
                });
        });
    }
</script>

@include('clients.blocks.footer')