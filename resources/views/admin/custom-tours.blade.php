@include('admin.blocks.header')
<div class="container body">
    <div class="main_container">
        @include('admin.blocks.sidebar')

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="page-title">
                    <div class="title_left">
                        <h3>Quản lý <small>Tours theo yêu cầu</small></h3>
                    </div>
                </div>

                <div class="clearfix"></div>

                {{-- Tabs Navigation --}}
                <ul class="nav nav-tabs bar_tabs" id="customToursTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab == 'tours' ? 'active' : '' }}" id="tours-tab" data-toggle="tab" href="#tours" role="tab" aria-controls="tours" aria-selected="{{ $activeTab == 'tours' ? 'true' : 'false' }}">
                            Danh sách Tours
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab == 'bookings' ? 'active' : '' }}" id="bookings-tab" data-toggle="tab" href="#bookings" role="tab" aria-controls="bookings" aria-selected="{{ $activeTab == 'bookings' ? 'true' : 'false' }}">
                            Quản lý Booking
                        </a>
                    </li>
                </ul>

                {{-- Tab Content --}}
                <div class="tab-content" id="customToursTabContent">
                    {{-- Tab 1: Danh sách Tours --}}
                    <div class="tab-pane fade {{ $activeTab == 'tours' ? 'show active' : '' }}" id="tours" role="tabpanel" aria-labelledby="tours-tab">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 ">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Danh sách Tours theo yêu cầu</h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                            </li>
                                            <li><a class="close-link"><i class="fa fa-close"></i></a>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="card-box table-responsive">
                                                    <p class="text-muted font-13 m-b-30">
                                                        Danh sách các tour được thiết kế theo yêu cầu của khách hàng.
                                                    </p>
                                                    <table id="datatable-customTours" class="table table-striped table-bordered"
                                                        style="width:100%">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 50px;">ID</th>
                                                                <th style="width: 120px;">Mã tour</th>
                                                                <th style="width: 150px;">Tên tour</th>
                                                                <th style="width: 300px;">Mô tả</th>
                                                                <th style="width: 120px;">Điểm đến</th>
                                                                <th style="width: 100px;">Số lượng người lớn</th>
                                                                <th style="width: 100px;">Số lượng trẻ em</th>
                                                                <th style="width: 120px;">Giá ước tính</th>
                                                                <th style="width: 80px;">Số lượt đặt</th>
                                                                <th style="width: 120px;">Ngày tạo</th>
                                                                <th style="width: 120px;">Lần đặt cuối</th>
                                                                <th style="width: 150px;">Hành động</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($customTours as $tour)
                                                                @php
                                                                    $option = json_decode($tour->option_json, true) ?? [];
                                                                    $description = '';
                                                                    
                                                                    // Tạo mô tả theo format "Khám phá Tours" (Tham quan, Lưu trú, Hoạt động khác)
                                                                    $highlights = $option['highlights'] ?? [];
                                                                    $hotelLevel = $option['hotel_level'] ?? ($tour->hotel_level ?? '2-3 sao');
                                                                    $intensity = $option['intensity'] ?? ($tour->intensity ?? 'Nhẹ');
                                                                    $adults = $tour->adults ?? ($option['adults'] ?? ($option['total_people'] ?? 0));
                                                                    $children = $tour->children ?? ($option['children'] ?? 0);
                                                                    
                                                                    // Tham quan
                                                                    $thamQuan = !empty($highlights) ? implode(', ', $highlights) : 'Các điểm nổi bật trong hành trình theo lịch trình chi tiết bên dưới.';
                                                                    
                                                                    // Lưu trú
                                                                    $luuTru = "Khách sạn tiêu chuẩn {$hotelLevel}, vị trí thuận tiện tham quan, tiện nghi thoải mái.";
                                                                    
                                                                    // Hoạt động khác
                                                                    $hoatDongKhac = "Lịch trình " . strtolower($intensity) . ", kết hợp tham quan – trải nghiệm – nghỉ ngơi hợp lý cho {$adults} người lớn";
                                                                    if ($children > 0) {
                                                                        $hoatDongKhac .= " và {$children} trẻ em";
                                                                    }
                                                                    $hoatDongKhac .= ".";
                                                                    
                                                                    // Ghép thành description với HTML
                                                                    $description = "<strong>Tham quan:</strong> {$thamQuan}<br>";
                                                                    $description .= "<strong>Lưu trú:</strong> {$luuTru}<br>";
                                                                    $description .= "<strong>Hoạt động khác:</strong> {$hoatDongKhac}";
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ $tour->id }}</td>
                                                                    <td>{{ $tour->code ?? 'N/A' }}</td>
                                                                    <td>{{ $tour->title }}</td>
                                                                    <td>{!! $description !!}</td>
                                                                    <td>{{ $tour->destination ?? 'N/A' }}</td>
                                                                    <td>{{ $tour->adults ?? 0 }} người</td>
                                                                    <td>{{ $tour->children ?? 0 }} người</td>
                                                                    <td>{{ number_format($tour->estimated_cost ?? 0, 0, ',', '.') }} VNĐ</td>
                                                                    <td>
                                                                        <span class="badge badge-info">{{ $tour->booking_count ?? 0 }}</span>
                                                                    </td>
                                                                    <td>{{ $tour->created_at ? \Carbon\Carbon::parse($tour->created_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                                                                    <td>
                                                                        @if ($tour->last_booking_date)
                                                                            {{ \Carbon\Carbon::parse($tour->last_booking_date)->format('d/m/Y H:i') }}
                                                                        @else
                                                                            <span class="text-muted">Chưa có</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-sm btn-success edit-custom-tour" 
                                                                                data-toggle="modal" 
                                                                                data-target="#edit-custom-tour-modal"
                                                                                data-tour-id="{{ $tour->id }}" 
                                                                                data-url-edit="{{ route('admin.custom_tours.get-edit') }}"
                                                                                title="Chỉnh sửa">
                                                                            <i class="fa fa-edit"></i> Sửa
                                                                        </button>
                                                                        <a href="{{ route('admin.custom_tours.index', ['tab' => 'bookings', 'tour_id' => $tour->id]) }}" 
                                                                           class="btn btn-sm btn-primary" 
                                                                           title="Xem booking">
                                                                            <i class="fa fa-eye"></i> Xem booking
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Quản lý Booking --}}
                    <div class="tab-pane fade {{ $activeTab == 'bookings' ? 'show active' : '' }}" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 ">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Booking Tours theo yêu cầu</h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                            </li>
                                            <li><a class="close-link"><i class="fa fa-close"></i></a>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="card-box table-responsive">
                                                    <p class="text-muted font-13 m-b-30">
                                                        Chào mừng bạn đến với trang quản lý tour đã đặt theo yêu cầu. Tại đây, bạn có thể xác nhận,
                                                        xem chi tiết, và quản lý tất cả các tour đã được đặt hiện có.
                                                    </p>
                                                    <table id="datatable-customBookings" class="table table-striped table-bordered"
                                                        style="width:100%">
                                                        <thead>
                                                            <tr>
                                                                <th>Tên Tours</th>
                                                                <th>Tên khách hàng</th>
                                                                <th>Email</th>
                                                                <th>Số điện thoại</th>
                                                                <th>Địa chỉ</th>
                                                                <th>Ngày đặt</th>
                                                                <th>Người lớn</th>
                                                                <th>Trẻ em</th>
                                                                <th>Tổng giá tiền</th>
                                                                <th>Trạng thái Booking</th>
                                                                <th>Thanh toán</th>
                                                                <th>Trạng thái</th>
                                                                <th>Hành động</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="tbody-custom-booking">
                                                            @include('admin.partials.list-custom-booking')
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page content -->
        <!-- Modal Edit Custom Tour-->
        <div class="modal fade" id="edit-custom-tour-modal" tabindex="-1" role="dialog" aria-labelledby="edit-custom-tour-Label"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document" style="max-width: 90%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-custom-tour-Label">Chỉnh sửa Tour theo yêu cầu</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="wizard-custom-tour" class="form_wizard wizard_horizontal">
                            <ul class="wizard_steps">
                                <li>
                                    <a href="#step-custom-1">
                                        <span class="step_no">1</span>
                                        <span class="step_descr">
                                            Bước 1<br />
                                            <small>Nhập thông tin</small>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#step-custom-2">
                                        <span class="step_no">2</span>
                                        <span class="step_descr">
                                            Bước 2<br />
                                            <small>Lộ trình</small>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                            <form id="form-edit-custom-tour" method="POST">
                                @csrf
                                <input type="hidden" name="custom_tour_id" id="custom_tour_id">
                                
                                <div id="step-custom-1">
                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Tên tour
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input class="form-control" name="title" id="edit_title" placeholder="Nhập tên Tour" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Điểm đến
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input class="form-control" name="destination" id="edit_destination" placeholder="Điểm đến" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Số lượng người lớn
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input class="form-control" type="number" name="adults" id="edit_adults" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Số lượng trẻ em
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input class="form-control" type="number" name="children" id="edit_children" value="0" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Giá người lớn
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input class="form-control" type="number" name="price_per_adult" id="edit_price_per_adult" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Giá trẻ em
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input class="form-control" type="number" name="price_per_child" id="edit_price_per_child" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Ngày khởi hành
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input type="text" class="form-control datetimepicker" id="edit_start_date" name="start_date" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Ngày kết thúc
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <input type="text" class="form-control datetimepicker" id="edit_end_date" name="end_date" required>
                                        </div>
                                    </div>

                                    <div class="field item form-group">
                                        <label class="col-form-label col-md-3 col-sm-3 label-align">Mô tả
                                            <span>*</span></label>
                                        <div class="col-md-9 col-sm-9">
                                            <textarea name="description" id="edit_description" rows="10" required></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div id="step-custom-2">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h2 class="StepTitle" style="margin: 0;">Nhập lộ trình</h2>
                                        <button type="button" class="btn btn-primary" id="add-timeline-custom-tour">
                                            <i class="fa fa-plus"></i> Thêm ngày
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.blocks.footer')

<script>
    // Đảm bảo jQuery đã được load
    (function() {
        function initCustomToursScript() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initCustomToursScript, 100);
                return;
            }
            
            var $ = jQuery;
            
            // Xử lý confirm và finish booking cho custom tours
            $(document).on("click", "#bookings .confirm-booking", function (e) {
        e.preventDefault();

        const bookingId = $(this).data("bookingid");
        const urlConfirm = $(this).data("urlconfirm");

        $.ajax({
            url: urlConfirm,
            method: "POST",
            data: {
                bookingId: bookingId,
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#tbody-custom-booking").html(response.data);
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (error) {
                toastr.error("Có lỗi xảy ra. Vui lòng thử lại sau.");
            },
        });
    });

    $(document).on("click", "#bookings .finish-booking", function (e) {
        e.preventDefault();

        const bookingId = $(this).data("bookingid");
        const urlFinish = $(this).data("urlfinish");

        $.ajax({
            url: urlFinish,
            method: "POST",
            data: {
                bookingId: bookingId,
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    $("#tbody-custom-booking").html(response.data);
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (error) {
                toastr.error("Có lỗi xảy ra. Vui lòng thử lại sau.");
            },
        });
    });

            // Xử lý click nút Edit Custom Tour
            $(document).on("click", ".edit-custom-tour", function (e) {
                e.preventDefault();
                const tourId = $(this).data("tour-id");
                const urlEdit = $(this).data("url-edit");

                if (!tourId || !urlEdit) {
                    toastr.error('Thông tin tour không hợp lệ.');
                    return false;
                }

                // Reset form và wizard
                $("#form-edit-custom-tour")[0].reset();
                $("#custom_tour_id").val('');
                $("#step-custom-2").empty();
                timelineCounter_custom = 1;
                
                // Khởi tạo SmartWizard
                init_SmartWizard_Custom_Tour();

                // Gọi AJAX để lấy dữ liệu trước khi mở modal
                $.ajax({
                    type: "GET",
                    url: urlEdit,
                    data: {
                        _token: $('meta[name="csrf-token"]').attr("content"),
                        custom_tour_id: tourId,
                    },
                    success: function (response) {
                        if (response.success) {
                            const tour = response.tour;
                            
                            // Điền dữ liệu vào form step 1
                            $("#custom_tour_id").val(tour.id || '');
                            $("#edit_title").val(tour.title || '');
                            $("#edit_destination").val(tour.destination || '');
                            $("#edit_adults").val(tour.adults || 0);
                            $("#edit_children").val(tour.children || 0);
                            $("#edit_price_per_adult").val(tour.price_per_adult || 0);
                            $("#edit_price_per_child").val(tour.price_per_child || 0);
                            
                            // Format ngày tháng (giống như trang tours: d-m-Y)
                            if (tour.start_date) {
                                const startDate = moment(tour.start_date, "YYYY-MM-DD").format("DD-MM-YYYY");
                                $("#edit_start_date").val(startDate);
                            }
                            if (tour.end_date) {
                                const endDate = moment(tour.end_date, "YYYY-MM-DD").format("DD-MM-YYYY");
                                $("#edit_end_date").val(endDate);
                            }

                            // Khởi tạo datetimepicker cho các trường ngày
                            if (typeof $.fn.datetimepicker !== 'undefined') {
                                $("#edit_start_date, #edit_end_date").datetimepicker({
                                    format: 'd-m-Y',
                                    timepicker: false,
                                    scrollInput: false
                                });
                            }

                            // Khởi tạo CKEditor cho description
                            if (typeof CKEDITOR !== 'undefined') {
                                if (CKEDITOR.instances['edit_description']) {
                                    CKEDITOR.instances['edit_description'].destroy();
                                }
                                var editor = CKEDITOR.replace('edit_description');
                                
                                editor.on('instanceReady', function() {
                                    editor.setData(tour.description || '');
                                });
                                
                                setTimeout(function() {
                                    if (CKEDITOR.instances['edit_description']) {
                                        CKEDITOR.instances['edit_description'].setData(tour.description || '');
                                    }
                                }, 300);
                            } else {
                                $("#edit_description").val(tour.description || '');
                            }
                            
                            // Load itinerary vào step 2
                            // Reset counter trước khi load
                            timelineCounter_custom = 1;
                            $("#step-custom-2").empty();
                            
                            // Debug: log itinerary data
                            console.log('Loading itinerary:', tour.itinerary);
                            
                            if (tour.itinerary && Array.isArray(tour.itinerary) && tour.itinerary.length > 0) {
                                // Load từng ngày theo thứ tự với delay nhỏ để đảm bảo DOM đã sẵn sàng
                                tour.itinerary.forEach(function(day, index) {
                                    setTimeout(function() {
                                        // Lấy description - đảm bảo chỉ lấy description của ngày này
                                        let dayDescription = day.description || '';
                                        
                                        // Decode HTML entities trước (ví dụ: &agrave; -> à, &nbsp; -> space)
                                        const tempDiv = document.createElement('div');
                                        tempDiv.innerHTML = dayDescription;
                                        dayDescription = tempDiv.textContent || tempDiv.innerText || dayDescription;
                                        
                                        // Nếu description chứa nhiều ngày (có pattern "Ngày X:"), tách đúng phần
                                        const currentDayNum = index + 1;
                                        
                                        // Tìm pattern "Ngày X:" (có thể có HTML entities hoặc không)
                                        // Pattern: "Ngày 1:", "Ngày 2:", etc. (có thể có <strong> tag hoặc không)
                                        const dayPatterns = [
                                            // Pattern với <strong> tag và HTML entities
                                            new RegExp(`<strong>Ng&agrave;y\\s*${currentDayNum}:<\\/strong>([\\s\\S]*?)(?=<strong>Ng&agrave;y\\s*${currentDayNum + 1}:<\\/strong>|$)`, 'i'),
                                            // Pattern với <strong> tag không có entities
                                            new RegExp(`<strong>Ngày\\s*${currentDayNum}:<\\/strong>([\\s\\S]*?)(?=<strong>Ngày\\s*${currentDayNum + 1}:<\\/strong>|$)`, 'i'),
                                            // Pattern không có tag, chỉ text "Ngày X:"
                                            new RegExp(`Ngày\\s*${currentDayNum}:\\s*([\\s\\S]*?)(?=Ngày\\s*${currentDayNum + 1}:|$)`, 'i'),
                                        ];
                                        
                                        let foundMatch = false;
                                        for (let i = 0; i < dayPatterns.length; i++) {
                                            const match = dayDescription.match(dayPatterns[i]);
                                            if (match && match[1]) {
                                                dayDescription = match[1].trim();
                                                // Loại bỏ các tag HTML thừa ở đầu/cuối
                                                dayDescription = dayDescription.replace(/^<p>\s*/, '').replace(/\s*<\/p>$/, '');
                                                dayDescription = dayDescription.replace(/^<br\s*\/?>\s*/i, '').replace(/\s*<br\s*\/?>$/i, '');
                                                foundMatch = true;
                                                break;
                                            }
                                        }
                                        
                                        // Nếu không tìm thấy pattern, có thể description đã đúng hoặc cần xử lý khác
                                        if (!foundMatch && dayDescription.includes('Ngày')) {
                                            // Thử tách từ đầu đến khi gặp "Ngày X+1:" hoặc hết
                                            const nextDayPatterns = [
                                                new RegExp(`<strong>Ng&agrave;y\\s*${currentDayNum}:<\\/strong>([\\s\\S]*?)(?=<strong>Ng&agrave;y|$)`, 'i'),
                                                new RegExp(`<strong>Ngày\\s*${currentDayNum}:<\\/strong>([\\s\\S]*?)(?=<strong>Ngày|$)`, 'i'),
                                                new RegExp(`Ngày\\s*${currentDayNum}:\\s*([\\s\\S]*?)(?=Ngày|$)`, 'i'),
                                            ];
                                            
                                            for (let i = 0; i < nextDayPatterns.length; i++) {
                                                const match = dayDescription.match(nextDayPatterns[i]);
                                                if (match && match[1]) {
                                                    dayDescription = match[1].trim();
                                                    dayDescription = dayDescription.replace(/^<p>\s*/, '').replace(/\s*<\/p>$/, '');
                                                    dayDescription = dayDescription.replace(/^<br\s*\/?>\s*/i, '').replace(/\s*<br\s*\/?>$/i, '');
                                                    foundMatch = true;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Nếu vẫn không tìm thấy và là ngày đầu tiên, có thể description đã bị combine
                                        // Trong trường hợp này, chỉ lấy phần đầu tiên (trước khi gặp "Ngày 2:")
                                        if (!foundMatch && index === 0 && dayDescription.includes('Ngày 2')) {
                                            const firstDayMatch = dayDescription.match(/([\s\S]*?)(?=Ngày\s*2:|<strong>Ngày\s*2:|$)/i);
                                            if (firstDayMatch && firstDayMatch[1]) {
                                                dayDescription = firstDayMatch[1].trim();
                                                // Loại bỏ tag <strong>Ngày 1:</strong> nếu có
                                                dayDescription = dayDescription.replace(/<strong>Ng&agrave;y\s*1:<\/strong>/i, '');
                                                dayDescription = dayDescription.replace(/<strong>Ngày\s*1:<\/strong>/i, '');
                                                dayDescription = dayDescription.replace(/Ngày\s*1:\s*/i, '');
                                                dayDescription = dayDescription.trim();
                                            }
                                        }
                                        
                                        // Format description để có định dạng giống trang detail
                                        // Đảm bảo "Buổi sáng:", "Buổi chiều:", "Buổi tối:" được in đậm và có xuống dòng
                                        if (dayDescription) {
                                            // Nếu description là text thuần (không có HTML), format lại
                                            if (!dayDescription.includes('<strong>') && !dayDescription.includes('<p>')) {
                                                // Tách theo "Buổi sáng:", "Buổi chiều:", "Buổi tối:"
                                                dayDescription = dayDescription.replace(/(Buổi sáng:)/g, '<strong>$1</strong>');
                                                dayDescription = dayDescription.replace(/(Buổi chiều:)/g, '<strong>$1</strong>');
                                                dayDescription = dayDescription.replace(/(Buổi tối:)/g, '<strong>$1</strong>');
                                                
                                                // Thêm xuống dòng sau mỗi phần
                                                dayDescription = dayDescription.replace(/(Buổi sáng:)/g, '<p><strong>$1</strong>');
                                                dayDescription = dayDescription.replace(/(Buổi chiều:)/g, '</p><p><strong>$1</strong>');
                                                dayDescription = dayDescription.replace(/(Buổi tối:)/g, '</p><p><strong>$1</strong>');
                                                
                                                // Đảm bảo có thẻ đóng </p> ở cuối
                                                if (!dayDescription.endsWith('</p>')) {
                                                    dayDescription += '</p>';
                                                }
                                                
                                                // Nếu không có thẻ mở <p> ở đầu, thêm vào
                                                if (!dayDescription.startsWith('<p>')) {
                                                    dayDescription = '<p>' + dayDescription;
                                                }
                                            } else {
                                                // Nếu đã có HTML, đảm bảo "Buổi sáng:", "Buổi chiều:", "Buổi tối:" được in đậm
                                                dayDescription = dayDescription.replace(/(Buổi sáng:)/gi, '<strong>$1</strong>');
                                                dayDescription = dayDescription.replace(/(Buổi chiều:)/gi, '<strong>$1</strong>');
                                                dayDescription = dayDescription.replace(/(Buổi tối:)/gi, '<strong>$1</strong>');
                                                
                                                // Đảm bảo có xuống dòng giữa các phần (thay <br> bằng </p><p>)
                                                dayDescription = dayDescription.replace(/<br\s*\/?>\s*<strong>/gi, '</p><p><strong>');
                                                dayDescription = dayDescription.replace(/<br\s*\/?>\s*<br\s*\/?>/gi, '</p><p>');
                                            }
                                        }
                                        
                                        // Đảm bảo có đầy đủ thông tin
                                        const dayData = {
                                            day: day.day || `Ngày ${index + 1}`,
                                            description: dayDescription,
                                            places: day.places || [],
                                            estimatedHours: day.estimatedHours || 0
                                        };
                                        
                                        console.log('Adding day:', dayData);
                                        addTimelineEntryCustom(dayData);
                                    }, index * 150); // Delay nhỏ giữa các ngày để CKEditor khởi tạo đúng
                                });
                            } else {
                                console.log('No itinerary data found');
                            }

                            // Mở modal
                            $("#edit-custom-tour-modal").modal('show');
                            
                            // Reset wizard về step 1
                            setTimeout(function() {
                                if ($("#wizard-custom-tour").length) {
                                    $("#wizard-custom-tour").smartWizard("goToStep", 1);
                                }
                            }, 100);
                        } else {
                            toastr.error(response.message || 'Không thể tải thông tin tour.');
                        }
                    },
                    error: function (xhr, textStatus, errorThrown) {
                        console.error('Error loading tour data:', xhr);
                        let errorMsg = "Có lỗi xảy ra khi tải dữ liệu tour. Vui lòng thử lại sau.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            errorMsg = "Không tìm thấy tour.";
                        } else if (xhr.status === 500) {
                            errorMsg = "Lỗi server. Vui lòng kiểm tra lại.";
                        }
                        toastr.error(errorMsg);
                    },
                });
            });
            
            // Khởi tạo SmartWizard cho custom tour
            function init_SmartWizard_Custom_Tour() {
                if (typeof $.fn.smartWizard === "undefined") {
                    return;
                }
                
                $("#wizard-custom-tour").smartWizard({
                    onLeaveStep: function (obj, context) {
                        var stepIndex = context.fromStep;
                        var nextStepIndex = context.toStep;
                        
                        // Kiểm tra bước 1
                        if (stepIndex === 1) {
                            var isValid = true;
                            $("#step-custom-1 input[required], #step-custom-1 textarea[required]").each(function () {
                                if ($(this).val().trim() === "") {
                                    isValid = false;
                                    $(this).addClass("is-invalid");
                                    toastr.error("Vui lòng điền đầy đủ các trường bắt buộc!");
                                } else {
                                    $(this).removeClass("is-invalid");
                                }
                            });
                            
                            // Kiểm tra CKEditor description
                            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['edit_description']) {
                                var descData = CKEDITOR.instances['edit_description'].getData();
                                if (!descData || descData.trim() === '') {
                                    isValid = false;
                                    toastr.error("Vui lòng điền mô tả!");
                                }
                            }
                            
                            return isValid;
                        }
                        return true;
                    },
                    onFinish: function() {
                        // Xử lý khi nhấn Finish
                        saveCustomTour();
                    }
                });
            }
            
            // Biến đếm timeline cho custom tour
            var timelineCounter_custom = 1;
            
            // Hàm thêm timeline entry cho custom tour
            function addTimelineEntryCustom(data = null) {
                // Lấy dữ liệu từ object itinerary
                const title = data ? (data.day || data.title || `Ngày ${timelineCounter_custom}`) : `Ngày ${timelineCounter_custom}`;
                let description = data ? (data.description || '') : "";

                // Escape HTML cho input title (chỉ text thuần)
                const escapedTitle = $('<div>').text(title).html();
                
                // Lấy danh sách places (điểm tham quan)
                const places = data ? (data.places || []) : [];
                const placesText = places.length > 0 ? places.join(', ') : '';
                
                // Format description để có định dạng giống trang detail
                // Đảm bảo "Buổi sáng:", "Buổi chiều:", "Buổi tối:" được in đậm và có xuống dòng
                if (description) {
                    // Nếu description là text thuần (không có HTML), format lại
                    if (!description.includes('<strong>') && !description.includes('<p>')) {
                        // Tách theo "Buổi sáng:", "Buổi chiều:", "Buổi tối:" và thêm <strong> tag
                        description = description.replace(/(Buổi sáng:)/g, '<p><strong>$1</strong>');
                        description = description.replace(/(Buổi chiều:)/g, '</p><p><strong>$1</strong>');
                        description = description.replace(/(Buổi tối:)/g, '</p><p><strong>$1</strong>');
                        
                        // Đảm bảo có thẻ đóng </p> ở cuối
                        if (!description.endsWith('</p>')) {
                            description += '</p>';
                        }
                        
                        // Nếu không có thẻ mở <p> ở đầu, thêm vào
                        if (!description.startsWith('<p>')) {
                            description = '<p>' + description;
                        }
                    } else {
                        // Nếu đã có HTML, đảm bảo "Buổi sáng:", "Buổi chiều:", "Buổi tối:" được in đậm
                        description = description.replace(/(Buổi sáng:)/gi, '<strong>$1</strong>');
                        description = description.replace(/(Buổi chiều:)/gi, '<strong>$1</strong>');
                        description = description.replace(/(Buổi tối:)/gi, '<strong>$1</strong>');
                        
                        // Đảm bảo có xuống dòng giữa các phần (thay <br> bằng </p><p>)
                        description = description.replace(/<br\s*\/?>\s*<strong>/gi, '</p><p><strong>');
                        description = description.replace(/<br\s*\/?>\s*<br\s*\/?>/gi, '</p><p>');
                    }
                }
                
                // Tạo giá trị cho input "Ngày" - bao gồm cả tên ngày và các điểm tham quan
                const dayValue = placesText ? `${escapedTitle} ${placesText}` : escapedTitle;
                
                const timelineEntry = `
                    <div class="timeline-entry" id="timeline-entry-custom-${timelineCounter_custom}" style="margin-bottom: 20px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px;">
                        <label for="day-custom-${timelineCounter_custom}">Ngày ${timelineCounter_custom}</label>
                        <input type="text" class="form-control" id="day-custom-${timelineCounter_custom}" 
                               name="day-custom-${timelineCounter_custom}" 
                               placeholder="Ngày thứ... (có thể thêm các điểm tham quan sau tên ngày, phân cách bằng dấu phẩy)" 
                               value="${dayValue}" 
                               required>
                        
                        <label for="itinerary-custom-${timelineCounter_custom}" style="margin-top: 10px; display: block;">Lộ trình:</label>
                        <textarea id="itinerary-custom-${timelineCounter_custom}" name="itinerary-custom-${timelineCounter_custom}" required></textarea>
                        
                        <button type="button" class="btn btn-round btn-danger remove-btn-custom" data-id="${timelineCounter_custom}" style="margin-top: 10px;">Xóa Timeline này</button>
                    </div>
                `;

                // Thêm vào div#step-custom-2
                $("#step-custom-2").append(timelineEntry);

                // Khởi tạo CKEditor cho textarea vừa thêm
                if (typeof CKEDITOR !== 'undefined') {
                    const textareaId = `itinerary-custom-${timelineCounter_custom}`;
                    
                    // Đợi một chút để đảm bảo DOM đã được render
                    setTimeout(function() {
                        const editor = CKEDITOR.replace(textareaId);
                        
                        // Nếu có dữ liệu, set vào CKEditor sau khi instance ready
                        if (description && description.trim() !== '') {
                            editor.on('instanceReady', function() {
                                // Set data với HTML content (không escape vì CKEditor cần HTML)
                                editor.setData(description);
                            });
                            
                            // Fallback: set data sau một khoảng thời gian ngắn hơn
                            setTimeout(function() {
                                if (editor && editor.status === 'ready') {
                                    editor.setData(description);
                                } else if (editor) {
                                    // Nếu chưa ready, đợi thêm
                                    editor.on('instanceReady', function() {
                                        editor.setData(description);
                                    });
                                }
                            }, 200);
                        }
                    }, 100);
                } else {
                    // Nếu không có CKEditor, set text thuần vào textarea
                    if (description) {
                        $(`#itinerary-custom-${timelineCounter_custom}`).val(description);
                    }
                }

                timelineCounter_custom++;
            }
            
            // Xử lý khi nhấn nút thêm timeline
            $(document).on("click", "#add-timeline-custom-tour", function () {
                addTimelineEntryCustom();
            });

            // Xử lý khi nhấn nút xóa timeline
            $(document).on("click", ".remove-btn-custom", function () {
                const id = $(this).data("id");
                const entryId = `#timeline-entry-custom-${id}`;
                
                // Destroy CKEditor instance nếu có
                const textareaId = `itinerary-custom-${id}`;
                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[textareaId]) {
                    CKEDITOR.instances[textareaId].destroy();
                }
                
                $(entryId).remove();
            });
            
            // Hàm lưu custom tour
            function saveCustomTour() {
                // Lấy dữ liệu từ CKEditor description
                var description = '';
                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['edit_description']) {
                    description = CKEDITOR.instances['edit_description'].getData();
                } else {
                    description = $("#edit_description").val();
                }
                
                // Thu thập timeline data
                var timelineData = [];
                $("#step-custom-2 .timeline-entry").each(function () {
                    const dayInput = $(this).find('input[name^="day-custom"]');
                    const itineraryTextarea = $(this).find("textarea[name^='itinerary-custom']");
                    
                    if (dayInput.length && itineraryTextarea.length) {
                        let dayValue = dayInput.val();
                        
                        // Tách tên ngày và các điểm tham quan
                        // Format: "Ngày 1 Phố Cổ Hà Nội, Chùa Trấn Quốc, ..." hoặc "Ngày 1 – Phố Cổ Hà Nội, ..."
                        let title = dayValue;
                        let places = [];
                        
                        // Tìm pattern: "Ngày X" hoặc "Ngày X –" hoặc "Ngày X -" rồi lấy phần sau
                        const dayPattern = /^Ngày\s*\d+\s*[–-]?\s*(.*)$/i;
                        const match = dayValue.match(dayPattern);
                        
                        if (match && match[1] && match[1].trim()) {
                            // Có điểm tham quan sau tên ngày
                            title = dayValue.substring(0, match[0].indexOf(match[1])).trim();
                            const placesText = match[1].trim();
                            places = placesText ? placesText.split(',').map(p => p.trim()).filter(p => p) : [];
                        } else {
                            // Không có điểm tham quan, chỉ có tên ngày
                            title = dayValue.trim();
                            places = [];
                        }
                        
                        const textareaId = itineraryTextarea.attr("id");
                        let itinerary = '';
                        
                        // Lấy từ CKEditor nếu có
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[textareaId]) {
                            itinerary = CKEDITOR.instances[textareaId].getData();
                        } else {
                            itinerary = itineraryTextarea.val();
                        }
                        
                        if (title && itinerary) {
                            timelineData.push({
                                title: title,
                                itinerary: itinerary,
                                places: places
                            });
                        }
                    }
                });
                
                // Tạo form data
                var formData = {
                    custom_tour_id: $("#custom_tour_id").val(),
                    title: $("#edit_title").val(),
                    destination: $("#edit_destination").val(),
                    adults: $("#edit_adults").val(),
                    children: $("#edit_children").val(),
                    price_per_adult: $("#edit_price_per_adult").val(),
                    price_per_child: $("#edit_price_per_child").val(),
                    start_date: $("#edit_start_date").val(),
                    end_date: $("#edit_end_date").val(),
                    description: description,
                    timeline: timelineData,
                    _token: $('meta[name="csrf-token"]').attr("content")
                };

                const urlUpdate = "{{ route('admin.custom_tours.update') }}";

                $.ajax({
                    type: "POST",
                    url: urlUpdate,
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message || 'Cập nhật thành công!');
                            $("#edit-custom-tour-modal").modal("hide");
                            // Reload trang để cập nhật dữ liệu
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Cập nhật thất bại.');
                        }
                    },
                    error: function (xhr, textStatus, errorThrown) {
                        let errorMsg = "Có lỗi xảy ra. Vui lòng thử lại sau.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    },
                });
            }
            
            // Reset wizard khi modal đóng
            $("#edit-custom-tour-modal").on("hidden.bs.modal", function () {
                if ($("#wizard-custom-tour").length && typeof $.fn.smartWizard !== 'undefined') {
                    $("#wizard-custom-tour").smartWizard("goToStep", 1);
                }
                $("#step-custom-2").empty();
                timelineCounter_custom = 1;
                
                // Destroy tất cả CKEditor instances
                if (typeof CKEDITOR !== 'undefined') {
                    for (var instance in CKEDITOR.instances) {
                        CKEDITOR.instances[instance].destroy();
                    }
                }
            });

        }
        
        // Khởi tạo khi DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCustomToursScript);
        } else {
            initCustomToursScript();
        }
    })();
</script>

