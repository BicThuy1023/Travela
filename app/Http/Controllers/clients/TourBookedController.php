<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\clients\Booking;
use App\Models\clients\Tours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TourBookedController extends Controller
{
    private $tour;
    private $booking;

    public function __construct()
    {
        $this->tour = new Tours();
        $this->booking = new Booking();
    }
    public function index(Request $req)
    {
        $title = "Tour đã đặt";

        $bookingId = $req->input('bookingId');
        $checkoutId = $req->input('checkoutId');
        $tour_booked = $this->tour->tourBooked($bookingId, $checkoutId);

        // Kiểm tra nếu không tìm thấy booking
        if (!$tour_booked) {
            return redirect()->route('my-tours')
                ->with('error', 'Không tìm thấy thông tin tour đã đặt. Vui lòng kiểm tra lại.');
        }

        // Tính toán $canCancel: có thể hủy nếu:
        // 1. bookingStatus không phải 'f' (finished) và không phải 'c' (cancelled)
        // 2. Có startDate và startDate >= 3 ngày từ hôm nay
        // KHÔNG phụ thuộc paymentMethod hoặc paymentStatus
        $canCancel = false;
        
        if ($tour_booked) {
            $bookingStatus = $tour_booked->bookingStatus ?? null;
            $startDate = $tour_booked->startDate ?? null;
            $paymentMethod = $tour_booked->paymentMethod ?? null;
            $paymentStatus = $tour_booked->paymentStatus ?? null;
            
            // Log để debug (đặc biệt cho bookingId=73)
            \Log::info('TourBookedController: Checking canCancel', [
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId,
                'bookingStatus' => $bookingStatus,
                'startDate' => $startDate,
                'paymentMethod' => $paymentMethod,
                'paymentStatus' => $paymentStatus,
            ]);
            
            // Kiểm tra booking status: chỉ cho phép hủy nếu chưa hoàn thành và chưa hủy
            if ($bookingStatus !== 'f' && $bookingStatus !== 'c') {
                // Kiểm tra ngày khởi hành
                if (!empty($startDate)) {
                    $today = Carbon::today();
                    $startDateCarbon = Carbon::parse($startDate);
                    
                    // Tính số ngày từ hôm nay đến ngày khởi hành (signed difference)
                    // Nếu startDate trong tương lai: diffInDays sẽ dương
                    // Nếu startDate trong quá khứ: diffInDays sẽ âm
                    $diffInDays = $today->diffInDays($startDateCarbon, false);
                    
                    // Có thể hủy nếu còn >= 3 ngày trước ngày khởi hành
                    $canCancel = $diffInDays >= 3;
                    
                    \Log::info('TourBookedController: Calculated canCancel', [
                        'bookingId' => $bookingId,
                        'today' => $today->format('Y-m-d'),
                        'startDate' => $startDateCarbon->format('Y-m-d'),
                        'diffInDays' => $diffInDays,
                        'canCancel' => $canCancel,
                    ]);
                } else {
                    // Nếu không có startDate (ví dụ: tour đang thỏa thuận), cho phép hủy
                    $canCancel = true;
                    
                    \Log::info('TourBookedController: No startDate, allowing cancel', [
                        'bookingId' => $bookingId,
                    ]);
                }
            } else {
                \Log::info('TourBookedController: Cannot cancel - booking status', [
                    'bookingId' => $bookingId,
                    'bookingStatus' => $bookingStatus,
                ]);
            }
        }

        // dd($tour_booked);
        return view("clients.tour-booked", compact('title', 'tour_booked', 'canCancel', 'bookingId', 'checkoutId'));
    }

    public function cancelBooking(Request $req)
    {
        $tourId = $req->input('tourId');
        $quantityAdults = $req->input('quantity__adults');
        $quantityChildren = $req->input('quantity__children');
        $bookingId = $req->input('bookingId');
        $checkoutId = $req->input('checkoutId');
        $isCustomTour = $req->has('isCustomTour') && $req->isCustomTour == '1';

        // Log để debug
        \Log::info('TourBookedController: cancelBooking called', [
            'bookingId' => $bookingId,
            'checkoutId' => $checkoutId,
            'isCustomTour' => $isCustomTour,
            'tourId' => $tourId,
            'all_request' => $req->all(),
        ]);

        // VALIDATION: Kiểm tra lại điều kiện hủy tour (backend protection)
        $tour_booked = $this->tour->tourBooked($bookingId, $checkoutId);
        
        if (!$tour_booked) {
            \Log::warning('TourBookedController: cancelBooking - tour not found', [
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId,
                'isCustomTour' => $isCustomTour,
                'userId' => $this->getUserId(),
            ]);
            toastr()->error('Không tìm thấy thông tin tour đã đặt!', 'Thông báo');
            return redirect()->route('my-tours');
        }

        // Kiểm tra booking status
        $bookingStatus = $tour_booked->bookingStatus ?? null;
        if ($bookingStatus === 'f' || $bookingStatus === 'c') {
            toastr()->error('Không thể hủy tour này!', 'Thông báo');
            return redirect()->route('my-tours');
        }

        // Kiểm tra ngày khởi hành: phải >= 3 ngày từ hôm nay
        if (!empty($tour_booked->startDate)) {
            $today = Carbon::today();
            $startDate = Carbon::parse($tour_booked->startDate);
            $diffInDays = $today->diffInDays($startDate, false);
            
            if ($diffInDays < 3) {
                toastr()->error('Không thể hủy tour trong vòng 3 ngày trước ngày khởi hành!', 'Thông báo');
                return redirect()->route('my-tours');
            }
        }

        // Lấy userId để kiểm tra ownership (đảm bảo user chỉ hủy tour của chính mình)
        $userId = null;
        if (session()->has('username')) {
            $username = session()->get('username');
            $userModel = new \App\Models\clients\User();
            $userId = $userModel->getUserId($username);
        }
        
        if (!$userId) {
            \Log::warning('TourBookedController: cancelBooking - user not logged in', [
                'bookingId' => $bookingId,
            ]);
            toastr()->error('Bạn cần đăng nhập để hủy tour!', 'Thông báo');
            return redirect()->route('login');
        }
        
        // Kiểm tra ownership: booking phải thuộc về user đang đăng nhập
        $bookingRecord = DB::table('tbl_booking')
            ->where('bookingId', $bookingId)
            ->where('userId', $userId)
            ->first();
            
        if (!$bookingRecord) {
            \Log::warning('TourBookedController: cancelBooking - booking not found or not owned by user', [
                'bookingId' => $bookingId,
                'userId' => $userId,
            ]);
            toastr()->error('Không tìm thấy thông tin tour đã đặt hoặc bạn không có quyền hủy tour này!', 'Thông báo');
            return redirect()->route('my-tours');
        }

        // Chỉ cập nhật quantity cho tour thông thường (không phải custom tour)
        $updateQuantity = true; // Mặc định true cho custom tour
        if (!$isCustomTour && $tourId) {
            $tour = $this->tour->getTourDetail($tourId);
            if ($tour) {
                $currentQuantity = $tour->quantity;
                // Tính toán số lượng trả lại
                $return_quantity = ($quantityAdults ?? 0) + ($quantityChildren ?? 0);
                // Cập nhật lại số lượng mới cho tour
                $newQuantity = $currentQuantity + $return_quantity;
                $updateQuantity = $this->tour->updateTours($tourId, ['quantity' => $newQuantity]);
                
                \Log::info('TourBookedController: cancelBooking - updated tour quantity', [
                    'tourId' => $tourId,
                    'oldQuantity' => $currentQuantity,
                    'returnQuantity' => $return_quantity,
                    'newQuantity' => $newQuantity,
                ]);
            }
        }

        // Hủy booking (áp dụng cho cả tour thông thường và custom tour)
        $updateBooking = $this->booking->cancelBooking($bookingId);
        
        \Log::info('TourBookedController: cancelBooking - result', [
            'bookingId' => $bookingId,
            'isCustomTour' => $isCustomTour,
            'updateQuantity' => $updateQuantity,
            'updateBooking' => $updateBooking,
        ]);

        if ($updateQuantity && $updateBooking) {
            toastr()->success('Hủy tour thành công!', 'Thông báo');
            // Redirect về trang tour-booked để hiển thị trạng thái đã hủy
            if ($checkoutId) {
                return redirect()->route('tour-booked', [
                    'bookingId' => $bookingId,
                    'checkoutId' => $checkoutId
                ]);
            } else {
                return redirect()->route('tour-booked', [
                    'bookingId' => $bookingId
                ]);
            }
        } else {
            toastr()->error('Có lỗi xảy ra khi hủy tour!', 'Thông báo');
            return redirect()->route('my-tours');
        }
    }
}
