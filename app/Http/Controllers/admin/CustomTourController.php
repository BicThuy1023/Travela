<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\BookingModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomTourController extends Controller
{
    private $booking;

    public function __construct()
    {
        $this->booking = new BookingModel();
    }

    public function index(Request $request)
    {
        $title = 'Quản lý Tours theo yêu cầu';
        
        // Tab mặc định: 'tours' hoặc 'bookings'
        $activeTab = $request->get('tab', 'tours');

        // Lấy danh sách custom tours CHỈ những tour đã có booking (khách đã đặt)
        $customTours = DB::table('tbl_custom_tours')
            ->join(DB::raw('(SELECT custom_tour_id, COUNT(bookingId) as booking_count, MAX(bookingDate) as last_booking_date FROM tbl_booking WHERE custom_tour_id IS NOT NULL GROUP BY custom_tour_id) as booking_stats'), function($join) {
                $join->on('tbl_custom_tours.id', '=', 'booking_stats.custom_tour_id');
            })
            ->select(
                'tbl_custom_tours.*',
                'booking_stats.booking_count',
                'booking_stats.last_booking_date'
            )
            ->where('booking_stats.booking_count', '>', 0) // Chỉ lấy những tour có booking
            ->orderByDesc('tbl_custom_tours.id')
            ->get();

        // Parse option_json để lấy title và description cho mỗi tour
        foreach ($customTours as $tour) {
            $option = json_decode($tour->option_json, true) ?? [];
            $tour->title = $option['title'] ?? 'Tour theo yêu cầu';
            $tour->code = $option['code'] ?? 'N/A';
            
            // Lấy mô tả từ itinerary hoặc highlights
            if (!empty($option['itinerary']) && is_array($option['itinerary'])) {
                $firstDay = $option['itinerary'][0] ?? [];
                $tour->description = $firstDay['description'] ?? '';
            } elseif (!empty($option['highlights']) && is_array($option['highlights'])) {
                $tour->description = implode(', ', array_slice($option['highlights'], 0, 3));
            } else {
                $tour->description = 'Tour được thiết kế theo yêu cầu';
            }
        }

        // Lấy danh sách booking của custom tours
        $customBookings = $this->getCustomTourBookings();
        $customBookings = $this->updateHideBooking($customBookings);
        
        // Thêm start_date và end_date từ custom_tours vào booking để hiển thị
        foreach ($customBookings as $booking) {
            $customTour = DB::table('tbl_custom_tours')->where('id', $booking->custom_tour_id)->first();
            if ($customTour) {
                $booking->tour_start_date = $customTour->start_date ?? null;
                $booking->tour_end_date = $customTour->end_date ?? null;
            }
        }

        return view('admin.custom-tours', compact('title', 'customTours', 'customBookings', 'activeTab'));
    }

    /**
     * Lấy danh sách booking của custom tours
     */
    private function getCustomTourBookings()
    {
        $bookings = DB::table('tbl_booking')
            ->join('tbl_custom_tours', 'tbl_booking.custom_tour_id', '=', 'tbl_custom_tours.id')
            ->leftJoin('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
            ->whereNotNull('tbl_booking.custom_tour_id')
            ->select(
                'tbl_booking.*',
                'tbl_custom_tours.option_json',
                'tbl_custom_tours.start_date',
                'tbl_custom_tours.end_date',
                'tbl_checkout.paymentMethod',
                'tbl_checkout.paymentStatus',
                'tbl_checkout.checkoutId'
            )
            ->orderByDesc('tbl_booking.bookingDate')
            ->get();

        // Parse option_json để lấy title cho mỗi booking
        foreach ($bookings as $booking) {
            $option = json_decode($booking->option_json, true) ?? [];
            $booking->title = $option['title'] ?? 'Tour theo yêu cầu';
        }

        return $bookings;
    }

    /**
     * Cập nhật hide cho booking (giống BookingManagementController)
     */
    private function updateHideBooking($list_booking)
    {
        // Lấy ngày hiện tại
        $currentDate = date('Y-m-d');

        foreach ($list_booking as $booking) {
            $booking->hide = 'hide';
            // Nếu không có checkout hoặc paymentStatus = 'n' (chưa thanh toán), hiển thị nút "Đã hoàn thành"
            if ($booking->paymentStatus === null || $booking->paymentStatus === 'n') {
                $booking->hide = '';
            }
            
            // So sánh endDate của booking với ngày hiện tại (nếu có)
            if (isset($booking->endDate) && $booking->endDate < $currentDate) {
                $booking->hide = '';
            }
        }
        return $list_booking;
    }

    /**
     * Xác nhận booking cho custom tour
     */
    public function confirmBooking(Request $request)
    {
        $bookingId = $request->bookingId;

        $dataConfirm = [
            'bookingStatus' => 'y'
        ];

        $result = $this->booking->updateBooking($bookingId, $dataConfirm);

        if ($result) {
            $customBookings = $this->getCustomTourBookings();
            $customBookings = $this->updateHideBooking($customBookings);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'data' => view('admin.partials.list-custom-booking', compact('customBookings'))->render()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại.'
            ], 500);
        }
    }

    /**
     * Hoàn thành booking cho custom tour
     */
    public function finishBooking(Request $request)
    {
        $bookingId = $request->bookingId;

        $dataConfirm = [
            'bookingStatus' => 'f'
        ];

        $result = $this->booking->updateBooking($bookingId, $dataConfirm);

        if ($result) {
            $customBookings = $this->getCustomTourBookings();
            $customBookings = $this->updateHideBooking($customBookings);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'data' => view('admin.partials.list-custom-booking', compact('customBookings'))->render()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại.'
            ], 500);
        }
    }

    /**
     * Lấy thông tin custom tour để chỉnh sửa
     */
    public function getCustomTourEdit(Request $request)
    {
        $customTourId = $request->custom_tour_id;

        $customTour = DB::table('tbl_custom_tours')
            ->where('id', $customTourId)
            ->first();

        if (!$customTour) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tour.',
            ], 404);
        }

        // Parse option_json để lấy thông tin chi tiết (tất cả thông tin khách đã nhập)
        $option = json_decode($customTour->option_json, true) ?? [];
        
        // Lấy mô tả theo format "Khám phá Tours" (Tham quan, Lưu trú, Hoạt động khác)
        $description = '';
        
        // Tham quan: từ highlights
        $highlights = $option['highlights'] ?? [];
        $thamQuan = !empty($highlights) ? implode(', ', $highlights) : 'Các điểm nổi bật trong hành trình theo lịch trình chi tiết bên dưới.';
        
        // Lưu trú: từ hotel_level
        $hotelLevel = $option['hotel_level'] ?? ($customTour->hotel_level ?? '2-3 sao');
        $luuTru = "Khách sạn tiêu chuẩn {$hotelLevel}, vị trí thuận tiện tham quan, tiện nghi thoải mái.";
        
        // Hoạt động khác: từ intensity, adults, children
        $intensity = $option['intensity'] ?? ($customTour->intensity ?? 'Nhẹ');
        $adults = $customTour->adults ?? ($option['adults'] ?? ($option['total_people'] ?? 0));
        $children = $customTour->children ?? ($option['children'] ?? 0);
        $hoatDongKhac = "Lịch trình " . strtolower($intensity) . ", kết hợp tham quan – trải nghiệm – nghỉ ngơi hợp lý cho {$adults} người lớn";
        if ($children > 0) {
            $hoatDongKhac .= " và {$children} trẻ em";
        }
        $hoatDongKhac .= ".";
        
        // Ghép thành description
        $description = "<p><strong>Tham quan:</strong> {$thamQuan}</p>";
        $description .= "<p><strong>Lưu trú:</strong> {$luuTru}</p>";
        $description .= "<p><strong>Hoạt động khác:</strong> {$hoatDongKhac}</p>";
        
        // Lấy toàn bộ itinerary để trả về cho step 2
        $itinerary = $option['itinerary'] ?? [];

        // Lấy giá từ price_breakdown hoặc từ option hoặc từ database
        $priceBreakdown = $option['price_breakdown'] ?? [];
        $pricePerAdult = $priceBreakdown['adult_price'] ?? $option['price_per_adult'] ?? $customTour->price_per_adult ?? 0;
        $pricePerChild = $priceBreakdown['child_price'] ?? $option['price_per_child'] ?? $customTour->price_per_child ?? 0;

        $tourData = [
            'id' => $customTour->id,
            'title' => $option['title'] ?? ($customTour->title ?? 'Tour theo yêu cầu'),
            'destination' => $customTour->destination ?? ($option['destination'] ?? ''),
            'adults' => $customTour->adults ?? ($option['adults'] ?? ($option['total_people'] ?? 0)),
            'children' => $customTour->children ?? ($option['children'] ?? 0),
            'price_per_adult' => $pricePerAdult,
            'price_per_child' => $pricePerChild,
            'start_date' => $customTour->start_date,
            'end_date' => $customTour->end_date,
            'description' => $description,
            'itinerary' => $itinerary, // Trả về toàn bộ itinerary cho step 2
        ];

        return response()->json([
            'success' => true,
            'tour' => $tourData,
        ]);
    }

    /**
     * Cập nhật custom tour
     */
    public function updateCustomTour(Request $request)
    {
        $customTourId = $request->custom_tour_id;
        $title = $request->input('title');
        $destination = $request->input('destination');
        $adults = $request->input('adults');
        $children = $request->input('children');
        $totalPeople = (int) $adults + (int) $children;
        $pricePerAdult = $request->input('price_per_adult');
        $pricePerChild = $request->input('price_per_child');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $description = $request->input('description');

        // Validate
        if (!$customTourId || !$title || !$destination) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ thông tin.',
            ], 400);
        }

        // Chuyển đổi ngày từ d-m-Y sang Y-m-d (giống như trang tours)
        try {
            if ($startDate) {
                $startDate = Carbon::createFromFormat('d-m-Y', $startDate)->format('Y-m-d');
            }
            if ($endDate) {
                $endDate = Carbon::createFromFormat('d-m-Y', $endDate)->format('Y-m-d');
            }
        } catch (Exception $e) {
            // Thử format d/m/Y nếu d-m-Y không được
            try {
                if ($startDate) {
                    $startDate = Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d');
                }
                if ($endDate) {
                    $endDate = Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');
                }
            } catch (Exception $e2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Định dạng ngày không hợp lệ. Vui lòng nhập theo định dạng dd-mm-yyyy.',
                ], 400);
            }
        }

        // Lấy custom tour hiện tại
        $customTour = DB::table('tbl_custom_tours')
            ->where('id', $customTourId)
            ->first();

        if (!$customTour) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tour.',
            ], 404);
        }

        // Parse option_json hiện tại
        $option = json_decode($customTour->option_json, true) ?? [];
        
        // Cập nhật thông tin trong option_json
        $option['title'] = $title;
        
        // Parse description từ step 1 (format "Khám phá Tours")
        // Description có format: <p><strong>Tham quan:</strong> ...</p><p><strong>Lưu trú:</strong> ...</p><p><strong>Hoạt động khác:</strong> ...</p>
        // Cần extract các thông tin này để cập nhật vào option_json
        if ($description) {
            // Extract Tham quan (highlights)
            if (preg_match('/<strong>Tham quan:<\/strong>\s*([^<]+)/i', $description, $matches)) {
                $thamQuanText = strip_tags(trim($matches[1]));
                if ($thamQuanText && $thamQuanText !== 'Các điểm nổi bật trong hành trình theo lịch trình chi tiết bên dưới.') {
                    $option['highlights'] = array_map('trim', explode(',', $thamQuanText));
                }
            }
            
            // Extract Lưu trú (hotel_level)
            if (preg_match('/Khách sạn tiêu chuẩn\s*([^,]+)/i', $description, $matches)) {
                $hotelLevel = trim($matches[1]);
                $option['hotel_level'] = $hotelLevel;
            }
            
            // Extract Hoạt động khác (intensity, adults, children đã có từ request)
            if (preg_match('/Lịch trình\s+([^,]+),/i', $description, $matches)) {
                $intensity = ucfirst(trim($matches[1]));
                $option['intensity'] = $intensity;
            }
        }
        
        // Cập nhật mô tả ngày đầu tiên nếu có
        if (!empty($option['itinerary']) && is_array($option['itinerary']) && count($option['itinerary']) > 0) {
            $option['itinerary'][0]['description'] = $description;
        }
        
        // Cập nhật itinerary từ timeline (step 2)
        $timelines = $request->input('timeline');
        if ($timelines && is_array($timelines) && count($timelines) > 0) {
            $newItinerary = [];
            foreach ($timelines as $idx => $timeline) {
                $dayTitle = $timeline['title'] ?? '';
                $dayDescription = $timeline['itinerary'] ?? '';
                $places = $timeline['places'] ?? [];
                
                // Nếu places là string (từ input), chuyển thành array
                if (is_string($places)) {
                    $places = !empty($places) ? explode(',', $places) : [];
                    $places = array_map('trim', $places);
                    $places = array_filter($places);
                }
                
                // Lấy estimatedHours từ itinerary cũ nếu có
                $oldItinerary = $option['itinerary'] ?? [];
                $oldDay = $oldItinerary[$idx] ?? [];
                $estimatedHours = $oldDay['estimatedHours'] ?? 0;
                
                $newItinerary[] = [
                    'day' => $dayTitle,
                    'description' => $dayDescription,
                    'places' => array_values($places), // Đảm bảo là array và re-index
                    'estimatedHours' => $estimatedHours,
                ];
            }
            $option['itinerary'] = $newItinerary;
        }

        // Cập nhật số người trong option_json
        $option['adults'] = (int) $adults;
        $option['children'] = (int) $children;
        $option['total_people'] = $totalPeople;

        // Cập nhật giá trong price_breakdown nếu có
        if (!empty($option['price_breakdown'])) {
            $option['price_breakdown']['adult_price'] = (int) $pricePerAdult;
            $option['price_breakdown']['child_price'] = (int) $pricePerChild;
            // Tính lại tổng giá
            $totalAdultsPrice = $pricePerAdult * (int) $adults;
            $totalChildrenPrice = $pricePerChild * (int) $children;
            $option['price_breakdown']['total_price_adults'] = $totalAdultsPrice;
            $option['price_breakdown']['total_price_children'] = $totalChildrenPrice;
            $option['price_breakdown']['final_total_price'] = $totalAdultsPrice + $totalChildrenPrice;
        }

        // Tính lại estimated_cost
        $estimatedCost = ($pricePerAdult * (int) $adults) + ($pricePerChild * (int) $children);

        // Cập nhật database (chỉ cập nhật các cột có trong bảng)
        $updateData = [
            'destination' => $destination,
            'adults' => (int) $adults,
            'children' => (int) $children,
            'total_people' => $totalPeople,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'estimated_cost' => $estimatedCost,
            'option_json' => json_encode($option, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        $result = DB::table('tbl_custom_tours')
            ->where('id', $customTourId)
            ->update($updateData);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật tour thành công.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại.',
            ], 500);
        }
    }
}

