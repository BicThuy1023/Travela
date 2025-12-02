@include('clients.blocks.header_home')
@include('clients.blocks.banner_home')

<!--Form Back Drop-->
<div class="form-back-drop"></div>

<!-- Destinations Area start Trang Chu -->
<section class="destinations-area bgc-black pt-100 pb-70 rel z-1">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="section-title text-white text-center counter-text-wrap mb-70" data-aos="fade-up"
                    data-aos-duration="1500" data-aos-offset="50">
                    <h2>Khám phá kho báu việt nam cùng ASIA Travel</h2>
                    <p>Website<span class="count-text plus" data-speed="3000" data-stop="24080">0</span>
                        phổ biến nhất mà bạn sẽ nhớ</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            @foreach ($tours as $tour)
                <div class="col-xxl-3 col-xl-4 col-md-6" style="margin-bottom: 30px">
                    <div class="destination-item block_tours" data-aos="fade-up" data-aos-duration="1500"
                        data-aos-offset="50">
                        <div class="image">
                            <div class="ratting"><i class="fas fa-star"></i> {{ number_format($tour->rating, 1) }}</div>
                            <a href="#" class="heart"><i class="fas fa-heart"></i></a>
                            <img src="{{ asset('admin/assets/images/gallery-tours/' . $tour->images[0] . '') }}"
                                alt="Destination">
                        </div>
                        <div class="content">
                            <span class="location"><i class="fal fa-map-marker-alt"></i>{{ $tour->destination }}</span>
                            <h5><a href="{{ route('tour-detail', ['id' => $tour->tourId]) }}">{{ $tour->title }}</a>
                            </h5>
                            <ul class="blog-meta">
                                <li><i class="far fa-clock"></i>{{ $tour->time }}</li>
                                <li><i class="far fa-user"></i>{{ $tour->quantity }}</li>
                            </ul>
                        </div>
                        <div class="destination-footer">
                            <span class="price"><span>{{ number_format($tour->priceAdult, 0, ',', '.') }}</span> VND /
                                người</span>
                            <a href="{{ route('tour-detail', ['id' => $tour->tourId]) }}" class="read-more">Đặt ngay <i
                                    class="fal fa-angle-right"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
<!-- Destinations Area end -->


<!-- About Us Area start GIOI THIEU -->
<section class="about-us-area py-100 rpb-90 rel z-1">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-xl-5 col-lg-6">
                <div class="about-us-content rmb-55" data-aos="fade-left" data-aos-duration="1500" data-aos-offset="50">
                    <div class="section-title mb-25">
                        <h2>Du lịch với sự tự tin Lý do hàng đầu để chọn công ty chúng tôi</h2>
                    </div>
                    <p>Chúng tôi sẽ nỗ lực hết mình để biến giấc mơ du lịch của bạn thành hiện thực những viên ngọc ẩn
                        và những điểm tham quan không thể bỏ qua</p>
                    <div class="divider counter-text-wrap mt-45 mb-55"><span>Chúng tôi có <span><span
                                    class="count-text plus" data-speed="3000" data-stop="5">0</span>
                                Năm</span> kinh nghiệm</span></div>
                    <div class="row">
                        <div class="col-6">
                            <div class="counter-item counter-text-wrap">
                                <span class="count-text k-plus" data-speed="2000" data-stop="1">0</span>
                                <span class="counter-title">Điểm đến phổ biến</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="counter-item counter-text-wrap">
                                <span class="count-text m-plus" data-speed="3000" data-stop="8">0</span>
                                <span class="counter-title">Khách hàng hài lòng</span>
                            </div>
                        </div>
                    </div>
                    <a href="destination1.html" class="theme-btn mt-10 style-two">
                        <span data-hover="Khám phá Điểm đến">Khám phá Điểm đến</span>
                        <i class="fal fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-xl-7 col-lg-6" data-aos="fade-right" data-aos-duration="1500" data-aos-offset="50">
                <div class="about-us-image">
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape1.png') }}" alt="Shape">
                    </div>
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape2.png') }}" alt="Shape">
                    </div>
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape3.png') }}" alt="Shape">
                    </div>
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape4.png') }}" alt="Shape">
                    </div>
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape5.png') }}" alt="Shape">
                    </div>
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape6.png') }}" alt="Shape">
                    </div>
                    <div class="shape"><img src="{{ asset('clients/assets/images/about/shape7.png') }}" alt="Shape">
                    </div>
                    <img src="{{ asset('clients/assets/images/about/about.png') }}" alt="About">
                </div>
            </div>
        </div>
    </div>
</section>
<!-- About Us Area end -->


<!-- Popular Destinations Area start -->
<section class="popular-destinations-area rel z-1">
    <div class="container-fluid">
        <div class="popular-destinations-wrap br-20 bgc-lighter pt-100 pb-70">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="section-title text-center counter-text-wrap mb-70" data-aos="fade-up"
                        data-aos-duration="1500" data-aos-offset="50">
                        <h2>Khám phá các điểm đến phổ biến</h2>
                        <p>Website <span class="count-text plus" data-speed="3000" data-stop="24080">0</span> trải
                            nghiệm phổ biến nhất</p>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    @php $count = 0; @endphp
                    @foreach ($toursPopular as $tour)
                        @if ($count == 2 || $count == 3)
                            <!-- Cột thứ 3 và thứ 4 sẽ là col-md-6 -->
                            <div class="col-md-6 item ">
                        @else
                                <!-- Các cột còn lại sẽ là col-xl-3 col-md-6 -->
                                <div class="col-xl-3 col-md-6 item ">
                            @endif

                                <div class="destination-item style-two" data-aos-duration="1500" data-aos-offset="50">
                                    <div class="image" style="max-height: 250px">
                                        <a href="#" class="heart"><i class="fas fa-heart"></i></a>
                                        <img src="{{ asset('admin/assets/images/gallery-tours/' . $tour->images[0]) }}"
                                            alt="Destination">
                                    </div>
                                    <div class="content">
                                        <h6 class="tour-title"><a
                                                href="{{ route('tour-detail', ['id' => $tour->tourId]) }}">{{ $tour->title }}</a>
                                        </h6>
                                        <span class="time">{{ $tour->time }}</span>
                                        <a href="{{ route('tour-detail', ['id' => $tour->tourId]) }}" class="more"><i
                                                class="fas fa-chevron-right"></i></a>
                                    </div>
                                </div>

                            </div>

                            @php $count++; @endphp
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
</section>
<!-- Popular Destinations Area end -->


<!-- Features Area start -->
<section class="features-area pt-100 pb-45 rel z-1">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-xl-6">
                <div class="features-content-part mb-55" data-aos="fade-left" data-aos-duration="1500"
                    data-aos-offset="50">
                    <div class="section-title mb-60">
                        <h2>Trải nghiệm du lịch tuyệt đỉnh mang đến sự khác biệt cho công ty chúng tôi</h2>
                    </div>
                    <div class="features-customer-box">
                        <div class="image">
                            <img src="{{ asset('clients/assets/images/features/features-box.jpg') }}" alt="Features">
                        </div>
                        <div class="content">
                            <div class="feature-authors mb-15">
                                <img src="{{ asset('clients/assets/images/features/feature-author1.jpg') }}"
                                    alt="Author">
                                <img src="{{ asset('clients/assets/images/features/feature-author2.jpg') }}"
                                    alt="Author">
                                <img src="{{ asset('clients/assets/images/features/feature-author3.jpg') }}"
                                    alt="Author">
                                <span>4k+</span>
                            </div>
                            <h6>850K+ Khách hàng hài lòng</h6>
                            <div class="divider style-two counter-text-wrap my-25"><span><span class="count-text plus"
                                        data-speed="3000" data-stop="5">0</span>
                                    Năm</span></div>
                            <p>Chúng tôi tự hào cung cấp các hành trình được cá nhân hóa</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6" data-aos="fade-right" data-aos-duration="1500" data-aos-offset="50">
                <div class="row pb-25">
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="icon"><i class="flaticon-tent"></i></div>
                            <div class="content">
                                <h5><a href="{{ route('tours') }}">Chinh Phục Cảnh Quan Việt Nam</a></h5>
                                <p>Khám phá những cảnh đẹp hùng vĩ và tuyệt vời của đất nước Việt Nam.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="icon"><i class="flaticon-tent"></i></div>
                            <div class="content">
                                <h5><a href="{{ route('tours') }}">Trải Nghiệm Đặc Sắc Việt Nam</a></h5>
                                <p>Trải nghiệm những hoạt động và lễ hội đặc trưng của văn hóa Việt.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item mt-20">
                            <div class="icon"><i class="flaticon-tent"></i></div>
                            <div class="content">
                                <h5><a href="{{ route('tours') }}">Khám Phá Di Sản Việt Nam</a></h5>
                                <p>Khám phá các di sản thế giới và những kỳ quan thiên nhiên nổi tiếng.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="icon"><i class="flaticon-tent"></i></div>
                            <div class="content">
                                <h5><a href="{{ route('tours') }}">Vẻ Đẹp Thiên Nhiên Việt </a></h5>
                                <p>Chinh phục vẻ đẹp tự nhiên hoang sơ và kỳ vĩ của Việt Nam.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Features Area end -->

<!-- CTA Area start -->
<section class="cta-area pt-100 rel z-1">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-4 col-md-6" data-aos="zoom-in-down" data-aos-duration="1500" data-aos-offset="50">
                <div class="cta-item"
                    style="background-image: url( {{ asset('clients/assets/images/cta/cta1.jpg') }});">
                    <span class="category">Khám Phá Vẻ Đẹp Văn Hóa Việt</span>
                    <h2>Tìm hiểu những giá trị văn hóa độc đáo của các vùng miền Việt Nam.</h2>
                    <a href="{{ route('tours') }}" class="theme-btn style-two bgc-secondary">
                        <span data-hover="Khám phá">Khám phá</span>
                        <i class="fal fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-xl-4 col-md-6" data-aos="zoom-in-down" data-aos-delay="50" data-aos-duration="1500"
                data-aos-offset="50">
                <div class="cta-item"
                    style="background-image: url( {{ asset('clients/assets/images/cta/cta2.jpg') }});">
                    <span class="category">Bãi biển Sea</span>
                    <h2>Bãi trong xanh dạt dào ở Việt Nam</h2>
                    <a href="{{ route('tours') }}" class="theme-btn style-two">
                        <span data-hover="Khám phá">Khám phá</span>
                        <i class="fal fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-xl-4 col-md-6" data-aos="zoom-in-down" data-aos-delay="100" data-aos-duration="1500"
                data-aos-offset="50">
                <div class="cta-item"
                    style="background-image: url( {{ asset('clients/assets/images/cta/cta3.jpg') }});">
                    <span class="category">Thác nước</span>
                    <h2>Thác nước lớn nhất Việt Nam</h2>
                    <a href="{{ route('tours') }}" class="theme-btn style-two bgc-secondary">
                        <span data-hover="Khám phá">Khám phá</span>
                        <i class="fal fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- CTA Area end -->

{{-- Leaflet CSS/JS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

{{-- POPUP MAP --}}
<style>
    .map-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .map-modal {
        background: #111319;
        border-radius: 16px;
        width: 90%;
        max-width: 900px;
        padding: 18px 20px 16px;
        color: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .7);
    }

    #map-popup {
        width: 100%;
        height: 420px;
        border-radius: 12px;
        overflow: hidden;
        margin-top: 8px;
    }

    .map-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .map-modal-footer {
        margin-top: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
    }

    .btn-map {
        border-radius: 999px;
        padding: 8px 16px;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }

    .btn-map-confirm {
        background: linear-gradient(90deg, #4caf50, #81c784);
        color: #000;
    }

    .btn-map-cancel {
        background: transparent;
        color: #ddd;
    }
</style>

<div class="map-modal-backdrop" id="map-modal-backdrop">
    <div class="map-modal">
        <div class="map-modal-header">
            <h4 style="margin:0; font-size:18px;">Chọn vị trí trên bản đồ</h4>
            <button type="button" class="btn-map btn-map-cancel" id="close-map-btn">
                Đóng
            </button>
        </div>

        <div style="margin-top:6px; font-size:13px; color:#ccc;">
            Click vào bản đồ để đặt marker. Sau đó bấm <strong>Xác nhận vị trí</strong>.
        </div>

        <div id="map-popup"></div>

        <div class="map-modal-footer">
            <div>
                Vĩ độ: <span id="popup-lat">chưa chọn</span> |
                Kinh độ: <span id="popup-lng">chưa chọn</span>
            </div>
            <button type="button" class="btn-map btn-map-confirm" id="confirm-map-btn">
                Xác nhận vị trí
            </button>
        </div>
    </div>
</div>

<script>
    const modalBackdrop = document.getElementById('map-modal-backdrop');
    const openMapBtn = document.getElementById('open-map-btn');
    const closeMapBtn = document.getElementById('close-map-btn');
    const confirmMapBtn = document.getElementById('confirm-map-btn');

    const startLatText = document.getElementById('popup-start-lat');
    const startLngText = document.getElementById('popup-start-lng');
    const endLatText = document.getElementById('popup-end-lat');
    const endLngText = document.getElementById('popup-end-lng');

    const startLatInput = document.getElementById('search_start_lat');
    const startLngInput = document.getElementById('search_start_lng');
    const endLatInput = document.getElementById('search_end_lat');
    const endLngInput = document.getElementById('search_end_lng');

    const destinationInput = document.getElementById('destination');
    const searchFormMap = document.getElementById('search_form');

    const originalAction = "{{ route('search') }}";
    const nearbyAction = "{{ route('nearby.tours') }}";

    let map, mapInited = false;
    let startMarker = null;
    let endMarker = null;
    let clickStep = 0;

    const defaultLat = 15.9;
    const defaultLng = 105.8;

    const redIcon = new L.Icon({
        iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
        shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const greenIcon = new L.Icon.Default();

    // mở popup
    openMapBtn.addEventListener('click', function () {
        modalBackdrop.style.display = 'flex';

        if (!mapInited) {
            map = L.map('map-popup', {
                center: [defaultLat, defaultLng],
                zoom: 6,
                zoomControl: true,
                scrollWheelZoom: true,
                dragging: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(map);

            map.on('click', function (e) {
                const lat = e.latlng.lat.toFixed(7);
                const lng = e.latlng.lng.toFixed(7);

                if (clickStep === 0) {
                    if (startMarker) map.removeLayer(startMarker);

                    startMarker = L.marker([lat, lng], { icon: greenIcon })
                        .addTo(map)
                        .bindPopup("Điểm khởi hành")
                        .openPopup();

                    startLatText.textContent = lat;
                    startLngText.textContent = lng;
                    startLatInput.value = lat;
                    startLngInput.value = lng;

                    clickStep = 1;
                    return;
                }

                if (clickStep === 1) {
                    if (endMarker) map.removeLayer(endMarker);

                    endMarker = L.marker([lat, lng], { icon: redIcon })
                        .addTo(map)
                        .bindPopup("Điểm kết thúc")
                        .openPopup();

                    endLatText.textContent = lat;
                    endLngText.textContent = lng;
                    endLatInput.value = lat;
                    endLngInput.value = lng;

                    clickStep = 2;
                    return;
                }

                // click lần 3 -> reset
                if (startMarker) map.removeLayer(startMarker);
                if (endMarker) map.removeLayer(endMarker);
                startMarker = null;
                endMarker = null;
                clickStep = 0;

                startLatText.textContent = 'chưa chọn';
                startLngText.textContent = 'chưa chọn';
                endLatText.textContent = 'chưa chọn';
                endLngText.textContent = 'chưa chọn';

                startLatInput.value = '';
                startLngInput.value = '';
                endLatInput.value = '';
                endLngInput.value = '';

                map.fire('click', e);
            });

            mapInited = true;
        }

        setTimeout(() => { map.invalidateSize(); }, 200);
    });

    function closeModal() {
        modalBackdrop.style.display = 'none';
    }
    closeMapBtn.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', function (e) {
        if (e.target === modalBackdrop) closeModal();
    });

    confirmMapBtn.addEventListener('click', function () {
        if (!startLatInput.value || !startLngInput.value) {
            alert('Bạn chưa chọn điểm khởi hành.');
            return;
        }

        if (endLatInput.value && endLngInput.value) {
            destinationInput.value = 'Đã chọn 2 điểm trên bản đồ';
        } else {
            destinationInput.value = 'Đã chọn 1 điểm trên bản đồ';
        }

        closeModal();
    });

    // đổi action form khi có dùng bản đồ
    searchFormMap.addEventListener('submit', function () {
        const hasStart = startLatInput.value !== '' && startLngInput.value !== '';

        if (hasStart) {
            searchFormMap.action = nearbyAction;
        } else {
            searchFormMap.action = originalAction;
        }
    });
</script>



@include('clients.blocks.footer_home')