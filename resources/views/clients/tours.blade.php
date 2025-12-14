@include('clients.blocks.header')
@include('clients.blocks.banner')

<!-- Tour Grid Area start -->
<section class="tour-grid-page py-100 rel z-1">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-10 rmb-75">
                <div class="shop-sidebar">
                    <div class="div_filter_clear">
                        <button class="clear_filter" name="btn_clear">
                            <a href="{{ route('tours') }}">Clear</a>
                        </button>
                    </div>
                    <div class="widget widget-filter" data-aos="fade-up" data-aos-delay="50" data-aos-duration="1500"
                        data-aos-offset="50">
                        <h6 class="widget-title">Lọc theo giá</h6>
                        <div class="price-filter-wrap">
                            <div class="price-slider-range"></div>
                            <div class="price">
                                <span>Giá </span>
                                <input type="text" id="price" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="widget widget-activity" data-aos="fade-up" data-aos-duration="1500"
                        data-aos-offset="50">
                        <h6 class="widget-title">Điểm đến</h6>
                        <ul class="radio-filter">
                            <li>
                                <input class="form-check-input" type="radio" name="domain" id="id_mien_bac"
                                    value="b">
                                <label for="id_mien_bac">Miền Bắc <span>{{ $domainsCount['mien_bac'] }}</span></label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="domain" id="id_mien_trung"
                                    value="t">
                                <label for="id_mien_trung">Miền Trung
                                    <span>{{ $domainsCount['mien_trung'] }}</span></label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="domain" id="id_mien_nam"
                                    value="n">
                                <label for="id_mien_nam">Miền Nam <span>{{ $domainsCount['mien_nam'] }}</span></label>
                            </li>
                        </ul>
                    </div>

                    <div class="widget widget-reviews" data-aos="fade-up" data-aos-duration="1500" data-aos-offset="50">
                        <h6 class="widget-title">Đánh giá</h6>
                        <ul class="radio-filter">
                            <li>
                                <input class="form-check-input" type="radio" name="filter_star" id="5star"
                                    value="5">
                                <label for="5star">
                                    <span class="ratting">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </span>
                                </label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="filter_star" id="4star"
                                    value="4">
                                <label for="4star">
                                    <span class="ratting">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt white"></i>
                                    </span>
                                </label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="filter_star" id="3star"
                                    value="3">
                                <label for="3star">
                                    <span class="ratting">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star white"></i>
                                        <i class="fas fa-star-half-alt white"></i>
                                    </span>
                                </label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="filter_star" id="2star"
                                    value="2">
                                <label for="2star">
                                    <span class="ratting">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star white"></i>
                                        <i class="fas fa-star white"></i>
                                        <i class="fas fa-star-half-alt white"></i>
                                    </span>
                                </label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="filter_star" id="1star"
                                    value="1">
                                <label for="1star">
                                    <span class="ratting">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star white"></i>
                                        <i class="fas fa-star white"></i>
                                        <i class="fas fa-star white"></i>
                                        <i class="fas fa-star-half-alt white"></i>
                                    </span>
                                </label>
                            </li>
                        </ul>
                    </div>

                    <div class="widget widget-duration" data-aos="fade-up" data-aos-duration="1500"
                        data-aos-offset="50">
                        <h6 class="widget-title">Thời gian</h6>
                        <ul class="radio-filter">
                            <li>
                                <input class="form-check-input" type="radio" name="duration" id="3ngay2dem"
                                    value="3n2d">
                                <label for="3ngay2dem">3 ngày 2 đêm</label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="duration" id="4ngay3dem"
                                    value="4n3d">
                                <label for="4ngay3dem">4 ngày 3 đêm</label>
                            </li>
                            <li>
                                <input class="form-check-input" type="radio" name="duration" id="5ngay4dem"
                                    value="5n4d">
                                <label for="5ngay4dem">5 ngày 4 đêm</label>
                            </li>
                        </ul>
                    </div>

                    @if(isset($promotions) && $promotions->count() > 0)
                        <div class="widget widget-promotions" data-aos="fade-up" data-aos-duration="1500"
                            data-aos-offset="50" style="margin-bottom: 30px;">
                            <h6 class="widget-title">
                                <i class="fa fa-tag" style="color: #ff8c42;"></i> Mã khuyến mãi
                            </h6>
                            @foreach ($promotions as $promotion)
                                <div class="promo-sidebar-card" style="background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%); border-left: 4px solid #ff8c42; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                        <div style="flex: 1;">
                                            <h6 style="font-size: 14px; font-weight: 600; color: #2c3e50; margin-bottom: 5px;">
                                                {{ $promotion->name }}
                                            </h6>
                                            <div style="font-size: 20px; font-weight: bold; color: #ff8c42; margin: 5px 0;">
                                                @if($promotion->discount_type === 'percent')
                                                    Giảm {{ $promotion->discount_value }}%
                                                @else
                                                    Giảm {{ number_format($promotion->discount_value, 0, ',', '.') }} VNĐ
                                                @endif
                                            </div>
                                            @if($promotion->min_order_amount > 0)
                                            <div style="font-size: 11px; color: #666; margin-top: 5px;">
                                                Đơn tối thiểu: {{ number_format($promotion->min_order_amount, 0, ',', '.') }} VNĐ
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="border-top: 1px dashed #ffb366; padding-top: 10px; margin-top: 10px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                            <span style="font-size: 11px; color: #999;">Mã:</span>
                                            <span style="font-size: 14px; font-weight: bold; color: #73d13d; font-family: 'Courier New', monospace; letter-spacing: 1px;">
                                                {{ $promotion->code }}
                                            </span>
                                        </div>
                                        <button onclick="copyPromoCode('{{ $promotion->code }}', this)" 
                                                style="width: 100%; background: #ff8c42; color: white; border: none; padding: 8px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                                            <i class="fa fa-copy"></i> Sao chép mã
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="{{ route('client.promotions.index') }}" style="color: #ff8c42; font-size: 13px; font-weight: 600; text-decoration: none;">
                                    Xem tất cả mã <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if (!empty($toursPopular) && !$toursPopular->isEmpty())
                        <div class="widget widget-tour" data-aos="fade-up" data-aos-duration="1500"
                            data-aos-offset="50">
                            <h6 class="widget-title">Trending tours</h6>
                            @foreach ($toursPopular as $tour)
                                <div class="destination-item tour-grid style-three bgc-lighter">
                                    <div class="image">
                                        <span class="badge">10% Off</span>
                                        <img src="{{ asset('admin/assets/images/gallery-tours/' . $tour->images[0]) }}"
                                            alt="Tour">
                                    </div>
                                    <div class="content">
                                        <div class="destination-header">
                                            <span class="location"><i class="fal fa-map-marker-alt"></i>
                                                {{ $tour->destination }}</span>
                                            <div class="ratting">
                                                <i class="fas fa-star"></i>
                                                <span>{{ $tour->rating }}</span>
                                            </div>
                                        </div>
                                        <h6><a
                                                href="{{ route('tour-detail', ['id' => $tour->tourId]) }}">{{ $tour->title }}</a>
                                        </h6>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="widget widget-cta mt-30" data-aos="fade-up" data-aos-duration="1500"
                    data-aos-offset="50">
                    <div class="content text-white">
                        <span class="h6">Khám Phá Việt Nam</span>
                        <h3>Địa điểm du lịch tốt nhất</h3>
                        <a href="{{ route('tours') }}" class="theme-btn style-two bgc-secondary">
                            <span data-hover="Khám phá ngay">Khám phá ngay</span>
                            <i class="fal fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="image">
                        <img src="{{ asset('clients/assets/images/widgets/cta-widget.png') }}" alt="CTA">
                    </div>
                    <div class="cta-shape"><img src="{{ asset('clients/assets/images/widgets/cta-shape2.png') }}"
                            alt="Shape"></div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="shop-shorter rel z-3 mb-20">
                    <div class="sort-text mb-15 me-4 me-xl-auto">
                        Tours tìm thấy
                    </div>
                    <div class="sort-text mb-15 me-4">
                        Sắp xếp theo
                    </div>
                    <select id="sorting_tours">
                        <option value="default" selected="">Sắp xếp theo</option>
                        <option value="new">Mới nhất</option>
                        <option value="old">Cũ nhất</option>
                        <option value="hight-to-low">Cao đến thấp</option>
                        <option value="low-to-high">Thấp đến cao</option>
                    </select>
                </div>

                <div class="tour-grid-wrap">
                    <div class="loader" style="display: none;"></div>
                    <div class="row" id="tours-container" style="display: flex !important; flex-wrap: wrap !important;">
                        @include('clients.partials.filter-tours')
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
<!-- Tour Grid Area end -->

@include('clients.blocks.new_letter')
@include('clients.blocks.footer')
<style>
    /* Đảm bảo tours-container luôn hiển thị */
    #tours-container {
        display: flex !important;
        flex-wrap: wrap !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-height: 200px !important;
    }
    
    #tours-container .col-xl-4,
    #tours-container .col-md-6 {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    #tours-container .destination-item {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    .loader {
        display: none;
        text-align: center;
        padding: 20px;
    }
    
    .loader.show {
        display: block;
    }
</style>
<script>
    var filterToursUrl = "{{ route('filter-tours') }}";
    
    // ========== XỬ LÝ FILTER TOURS ==========
    $(document).ready(function() {
        let filterTimeout;
        let currentFilters = {
            minPrice: null,
            maxPrice: null,
            domain: null,
            star: null,
            time: null,
            sorting: null
        };

        // Hàm gửi request filter
        function applyFilters() {
            $('.loader').addClass('show').show();
            $('#tours-container').css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            }).html('');

            // Thu thập dữ liệu filter (chỉ gửi những filter có giá trị)
            const filters = {};
            if (currentFilters.minPrice !== null && currentFilters.maxPrice !== null) {
                filters.minPrice = currentFilters.minPrice;
                filters.maxPrice = currentFilters.maxPrice;
            }
            if (currentFilters.domain !== null) {
                filters.domain = currentFilters.domain;
            }
            if (currentFilters.star !== null) {
                filters.star = currentFilters.star;
            }
            if (currentFilters.time !== null) {
                filters.time = currentFilters.time;
            }
            if (currentFilters.sorting !== null) {
                filters.sorting = currentFilters.sorting;
            }

            console.log('Gửi filter:', filters);

            // Gửi AJAX request
            $.ajax({
                url: filterToursUrl,
                type: 'GET',
                data: filters,
                success: function(response) {
                    console.log('Response nhận được (first 500 chars):', response ? response.substring(0, 500) : 'null');
                    console.log('Response type:', typeof response);
                    console.log('Response length:', response ? response.length : 0);
                    
                    // Đảm bảo response là string HTML
                    if (response && typeof response === 'string' && response.trim() !== '') {
                        // Xóa nội dung cũ và thêm nội dung mới
                        const container = $('#tours-container');
                        console.log('Container tìm thấy:', container.length);
                        console.log('Container selector:', container.selector || '#tours-container');
                        
                        if (container.length === 0) {
                            console.error('Không tìm thấy container #tours-container');
                            return;
                        }
                        
                        // Xóa nội dung cũ
                        container.empty();
                        
                        // Đảm bảo container hiển thị với flexbox
                        container.css({
                            'display': 'flex',
                            'flex-wrap': 'wrap',
                            'visibility': 'visible',
                            'opacity': '1',
                            'min-height': '200px'
                        });
                        
                        // Thêm nội dung mới
                        container.html(response);
                        
                        // Đảm bảo tất cả cards hiển thị
                        setTimeout(function() {
                            container.find('.col-xl-4, .col-md-6').css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                            
                            container.find('.destination-item').css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                        }, 100);
                        
                        // Kiểm tra sau khi thêm
                        const tourCards = container.find('.destination-item');
                        console.log('Số lượng tour cards sau khi thêm:', tourCards.length);
                        console.log('Container HTML sau (first 200 chars):', container.html().substring(0, 200));
                        
                        // Kiểm tra visibility
                        const containerVisible = container.is(':visible');
                        console.log('Container có visible không:', containerVisible);
                        console.log('Container display:', container.css('display'));
                        console.log('Container visibility:', container.css('visibility'));
                        console.log('Container height:', container.height());
                        console.log('Container width:', container.width());
                        
                        // Kiểm tra từng card
                        tourCards.each(function(index) {
                            const card = $(this);
                            const cardVisible = card.is(':visible');
                            console.log(`Card ${index + 1}:`, {
                                visible: cardVisible,
                                display: card.css('display'),
                                height: card.height(),
                                width: card.width(),
                                parent: card.parent().attr('class')
                            });
                        });
                        
                        // Force browser reflow
                        container[0].offsetHeight;
                        
                        // Reinitialize AOS animation nếu có
                        if (typeof AOS !== 'undefined') {
                            AOS.refresh();
                        }
                    } else {
                        console.warn('Response rỗng hoặc không hợp lệ');
                        $('#tours-container').html('<div class="col-12 text-center"><p class="text-muted">Không tìm thấy tour nào phù hợp với bộ lọc của bạn.</p></div>');
                    }
                    $('.loader').hide();
                },
                error: function(xhr, status, error) {
                    console.error('Lỗi khi lọc tour:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    $('.loader').hide();
                    $('#tours-container').html('<div class="col-12 text-center"><p class="text-danger">Có lỗi xảy ra khi lọc tour. Vui lòng thử lại.</p></div>');
                }
            });
        }

        // Xử lý price slider
        if ($('.price-slider-range').length) {
            $(".price-slider-range").on("slidechange", function(event, ui) {
                currentFilters.minPrice = ui.values[0];
                currentFilters.maxPrice = ui.values[1];
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(applyFilters, 500);
            });
        }

        // Xử lý radio button domain
        $('input[name="domain"]').on('change', function() {
            currentFilters.domain = $(this).val();
            applyFilters();
        });

        // Xử lý radio button filter_star (đánh giá)
        $('input[name="filter_star"]').on('change', function() {
            currentFilters.star = $(this).val();
            applyFilters();
        });

        // Xử lý radio button duration (thời gian)
        $('input[name="duration"]').on('change', function() {
            currentFilters.time = $(this).val();
            applyFilters();
        });

        // Xử lý sorting
        $('#sorting_tours').on('change', function() {
            const value = $(this).val();
            if (value !== 'default') {
                currentFilters.sorting = value;
            } else {
                currentFilters.sorting = null;
            }
            applyFilters();
        });

        // Xử lý nút Clear
        $('.clear_filter a').on('click', function(e) {
            e.preventDefault();
            // Reset tất cả filter
            currentFilters = {
                minPrice: null,
                maxPrice: null,
                domain: null,
                star: null,
                time: null,
                sorting: null
            };
            // Reset UI
            $('input[name="domain"]').prop('checked', false);
            $('input[name="filter_star"]').prop('checked', false);
            $('input[name="duration"]').prop('checked', false);
            $('#sorting_tours').val('default');
            // Reset price slider
            if ($('.price-slider-range').length) {
                $(".price-slider-range").slider("values", [0, 20000000]);
                $("#price").val("0 vnđ - 20.000.000 vnđ");
            }
            // Reload trang
            window.location.href = $(this).attr('href');
        });

        // Xử lý pagination links (delegate event cho dynamic content)
        $(document).on('click', '.pagination-tours a.page-link', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            if (url) {
                $('.loader').show();
                $('#tours-container').html('');
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        $('#tours-container').html(response);
                        $('.loader').hide();
                        // Scroll to top
                        $('html, body').animate({
                            scrollTop: $('.tour-grid-wrap').offset().top - 100
                        }, 500);
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi khi load trang:', error);
                        $('.loader').hide();
                        $('#tours-container').html('<div class="col-12 text-center"><p class="text-danger">Có lỗi xảy ra khi tải trang. Vui lòng thử lại.</p></div>');
                    }
                });
            }
        });
    });
    
    function copyPromoCode(code, button) {
        const textarea = document.createElement('textarea');
        textarea.value = code;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            document.execCommand('copy');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fa fa-check"></i> Đã sao chép!';
            button.style.background = '#73d13d';
            
            if (typeof toastr !== 'undefined') {
                toastr.success('Đã sao chép mã: ' + code);
            } else {
                alert('Đã sao chép mã: ' + code);
            }
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '#ff8c42';
            }, 2000);
        } catch (err) {
            console.error('Lỗi khi sao chép:', err);
            alert('Không thể sao chép mã. Vui lòng sao chép thủ công: ' + code);
        }
        
        document.body.removeChild(textarea);
    }
</script>
