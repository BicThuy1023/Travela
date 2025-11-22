@include('clients.blocks.header')
@include('clients.blocks.banner')

<section class="tour-grid-page py-100 rel z-2">
    <div class="container">

        {{-- Container kết quả --}}
        <div id="search-results" class="row">
            @if(isset($tours) && $tours->count())
                @foreach($tours as $tour)
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="destination-item tour-grid style-three bgc-lighter equal-block-fix">
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
                                    <li><i class="far fa-clock"></i> {{ $tour->time }}</li>
                                    <li><i class="far fa-user"></i> {{ $tour->quantity }}</li>

                                    {{-- nếu là nearby thì show thêm khoảng cách --}}
                                    @if(isset($isNearby) && $isNearby)
                                        <li><i class="far fa-map"></i> {{ number_format($tour->distance ?? 0, 2) }} km</li>
                                    @endif
                                </ul>

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

                {{-- ✅ THÊM THÔNG BÁO NẾU NEARBY MÀ RỖNG --}}
            @elseif(isset($isNearby) && $isNearby)
                <div class="col-12">
                    <div class="alert alert-warning">
                        Không tìm thấy tour nào gần vị trí bạn chọn. Hãy thử chọn vị trí khác để tìm kiếm.
                    </div>
                </div>
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

            const keyword = "{{ $keyword ?? '' }}";
            const startDate = "{{ $startDate ?? '' }}";
            const endDate = "{{ $endDate ?? '' }}";

            const params = new URLSearchParams();
            if (keyword) params.append('keyword', keyword);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            loadingDiv.style.display = 'block';

            fetch("{{ url('/api/search-tours-js') }}" + '?' + params.toString())
                .then(r => r.json())
                .then(json => {
                    loadingDiv.style.display = 'none';
                    resultsContainer.innerHTML = '';

                    if (!json.success || !json.data || json.data.length === 0) {
                        resultsContainer.innerHTML = `
                            <h4 class="alert alert-danger">
                                Không tìm thấy tour phù hợp với từ khóa "{{ $keyword }}".
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

                        // ⭐ 
                        let ratingHtml = '';
                        const rating = Math.round(Number(tour.rating || 0));
                        for (let i = 0; i < 5; i++) {
                            if (rating > i) {
                                ratingHtml += '<i class="fas fa-star filled-star"></i>';
                            } else {
                                ratingHtml += '<i class="far fa-star empty-star"></i>';
                            }
                        }

                        col.innerHTML = `
                            <div class="destination-item tour-grid style-three bgc-lighter equal-block-fix">
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