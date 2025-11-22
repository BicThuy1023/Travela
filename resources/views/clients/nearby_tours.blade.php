@include('clients.blocks.header')
@include('clients.blocks.banner')

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map {
        width: 100%;
        height: 420px;
        border-radius: 10px;
        overflow: hidden;
    }

    .nearby-wrapper {
        padding: 60px 0;
        background-color: #0b0b0f;
        color: #fff;
    }

    .nearby-card {
        background: #1b1b24;
        border-radius: 10px;
        padding: 15px 18px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .nearby-card h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .nearby-card p {
        margin: 3px 0;
        font-size: 14px;
        color: #bbb;
    }

    .nearby-distance {
        font-weight: 600;
        font-size: 15px;
    }

    .nearby-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        background: #ff7e00;
        font-size: 11px;
        color: #000;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .coords-display {
        font-size: 13px;
        margin-top: 8px;
        color: #ddd;
    }

    .search-btn {
        margin-top: 12px;
        padding: 10px 20px;
        border-radius: 999px;
        border: none;
        background: linear-gradient(90deg, #ff7e00, #ffb347);
        color: #000;
        font-weight: 600;
        cursor: pointer;
    }

    .search-btn:hover {
        filter: brightness(1.05);
    }

    @media (min-width: 992px) {
        .nearby-layout {
            display: grid;
            grid-template-columns: 2fr 3fr;
            gap: 30px;
        }
    }
</style>

<section class="nearby-wrapper">
    <div class="container">

        <div class="section-title text-white text-center mb-4">
            <h2>Tìm tour gần vị trí của bạn</h2>
            <p>Chọn một điểm bất kỳ trên bản đồ, sau đó bấm <strong>Tìm tour gần đây</strong>.</p>
        </div>

        <div class="nearby-layout">

            {{-- Cột bên trái: bản đồ + nút tìm --}}
            <div>
                <form action="{{ route('nearby.tours') }}" method="GET" id="nearby-form">
                    <div id="map"></div>

                    <div class="coords-display">
                        <span>Vĩ độ (lat): <span id="lat-text">{{ $lat ?? 'chưa chọn' }}</span></span><br>
                        <span>Kinh độ (lng): <span id="lng-text">{{ $lng ?? 'chưa chọn' }}</span></span>
                    </div>

                    <input type="hidden" id="lat-input" name="lat" value="{{ $lat }}">
                    <input type="hidden" id="lng-input" name="lng" value="{{ $lng }}">

                    <button type="submit" class="search-btn">
                        Tìm tour gần đây
                    </button>
                </form>
            </div>

            {{-- Cột bên phải: kết quả tour --}}
            <div>
                @if ($lat && $lng)
                    <div style="margin-bottom: 12px;">
                        <span class="nearby-badge">Kết quả</span>
                        <p style="margin-top:6px;">
                            Vị trí bạn chọn: <strong>{{ $lat }}, {{ $lng }}</strong>
                        </p>
                    </div>

                    @if($tours->count())
                        @foreach($tours as $tour)
                            <div class="nearby-card">
                                <div>
                                    <h4>{{ $tour->tourName ?? $tour->title ?? 'Tour #' . $tour->tourId }}</h4>
                                    @if (!empty($tour->location))
                                        <p>Địa điểm: {{ $tour->location }}</p>
                                    @endif
                                    @if (!empty($tour->shortDescription))
                                        <p>{{ $tour->shortDescription }}</p>
                                    @endif
                                </div>
                                <div style="text-align: right;">
                                    <div class="nearby-distance">
                                        {{ number_format($tour->distance, 2) }} km
                                    </div>
                                    <a href="{{ url('/tours/' . $tour->tourId) }}" class="btn-theme btn-sm"
                                        style="margin-top:8px; display:inline-block;">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p>Không tìm thấy tour nào trong bán kính đã chọn. Hãy thử chọn vị trí khác hoặc mở rộng bán kính trong
                            controller.</p>
                    @endif
                @else
                    <p>Hãy click lên bản đồ để chọn vị trí, sau đó bấm <strong>Tìm tour gần đây</strong>.</p>
                @endif
            </div>
        </div>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Tọa độ mặc định: Việt Nam (giữa giữa)
    const defaultLat = {{ $lat ?? 15.9 }};
    const defaultLng = {{ $lng ?? 105.8 }};
    const defaultZoom = {{ ($lat && $lng) ? 9 : 6 }};

    const map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let marker = null;

    // Nếu đã có lat/lng (sau khi search) thì hiển thị marker
    @if ($lat && $lng)
        marker = L.marker([{{ $lat }}, {{ $lng }}]).addTo(map);
    @endif

    // Click để chọn vị trí
    map.on('click', function (e) {
        const lat = e.latlng.lat.toFixed(7);
        const lng = e.latlng.lng.toFixed(7);

        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker([lat, lng]).addTo(map);

        // Cập nhật text + hidden input
        document.getElementById('lat-text').innerText = lat;
        document.getElementById('lng-text').innerText = lng;

        document.getElementById('lat-input').value = lat;
        document.getElementById('lng-input').value = lng;
    });
</script>

@include('clients.blocks.footer')