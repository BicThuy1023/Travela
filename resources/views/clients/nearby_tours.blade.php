@include('clients.blocks.header')
@include('clients.blocks.banner')

<section class="tour-grid-page py-100 rel z-2">
    <div class="container">

        <div class="row">
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
                                    <a href="{{ route('tour-detail', ['id' => $tour->tourId]) }}">
                                        {{ $tour->title }}
                                    </a>
                                </h5>

                                <ul class="blog-meta">
                                    <li><i class="far fa-clock"></i> {{ $tour->time }}</li>
                                    <li><i class="far fa-user"></i> {{ $tour->quantity }}</li>
                                    @if(isset($tour->start_distance))
                                        <li>
                                            <i class="far fa-map"></i>
                                            {{ number_format($tour->start_distance, 1) }} km
                                        </li>
                                    @endif
                                </ul>

                                <div class="destination-footer">
                                    <span class="price">
                                        <span>{{ number_format($tour->priceAdult, 0, ',', '.') }}</span> VND / người
                                    </span>
                                    <a href="{{ route('tour-detail', ['id' => $tour->tourId]) }}"
                                        class="theme-btn style-two style-three">
                                        <span data-hover="Đặt ngay">Đặt ngay</span>
                                        <i class="fal fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12">
                    <h4 class="alert alert-danger">
                        Không tìm thấy tour nào gần vị trí bạn chọn.
                        Hãy thử chọn vị trí khác hoặc mở rộng bán kính.
                    </h4>
                </div>
            @endif
        </div>

    </div>
</section>

@include('clients.blocks.footer')