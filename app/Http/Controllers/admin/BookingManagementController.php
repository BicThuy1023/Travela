<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\BookingModel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BookingManagementController extends Controller
{

    private $booking;

    public function __construct()
    {
        $this->booking = new BookingModel();
    }
    public function index()
    {
        $title = 'Quản lý đặt Tour';

        $list_booking = $this->booking->getBooking();
        $list_booking = $this->updateHideBooking($list_booking);

        // dd($list_booking);

        return view('admin.booking', compact('title', 'list_booking'));
    }

    public function confirmBooking(Request $request)
    {
        $bookingId = $request->bookingId;

        $dataConfirm = [
            'bookingStatus' => 'y'
        ];

        $result = $this->booking->updateBooking($bookingId, $dataConfirm);

        if ($result) {
            $list_booking = $this->booking->getBooking();
            $list_booking = $this->updateHideBooking($list_booking);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'data' => view('admin.partials.list-booking', compact('list_booking'))->render()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại.'
            ], 500);
        }
    }

    public function showDetail($bookingId)
    {
        $title = 'Chi tiết đơn đặt';

        $invoice_booking = $this->booking->getInvoiceBooking($bookingId);
        // dd($invoice_booking);
        $hide='hide';
        if ($invoice_booking->transactionId == null) {
            $invoice_booking->transactionId = 'Thanh toán tại công ty Asia Travel';
        }
        if ($invoice_booking->paymentStatus === 'n') {
            $hide = '';
        }
        return view('admin.booking-detail', compact('title', 'invoice_booking','hide'));
    }


    public function sendPdf(Request $request)
    {
        $bookingId = $request->input('bookingId');
        $email = $request->input('email');
        $title = 'Hóa đơn';
        
        $invoice_booking = $this->booking->getInvoiceBooking($bookingId);
        
        if (!$invoice_booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin đặt tour.',
            ], 404);
        }

        // Đảm bảo transactionId có giá trị
        if (!isset($invoice_booking->transactionId) || $invoice_booking->transactionId == null) {
            $invoice_booking->transactionId = 'Thanh toán tại công ty Asia Travel';
        }
        
        // Đảm bảo paymentDate có giá trị
        if (!isset($invoice_booking->paymentDate) || $invoice_booking->paymentDate == null) {
            $invoice_booking->paymentDate = date('d-m-Y', strtotime($invoice_booking->bookingDate));
        }
        
        // Đảm bảo startDate có giá trị
        if (!isset($invoice_booking->startDate) || $invoice_booking->startDate == null) {
            $invoice_booking->startDate = $invoice_booking->bookingDate;
        }
        
        // Đảm bảo amount có giá trị
        if (!isset($invoice_booking->amount) || $invoice_booking->amount == null) {
            $invoice_booking->amount = $invoice_booking->totalPrice ?? 0;
        }
        
        // Đảm bảo discountAmount và undiscountedTotal có giá trị
        if (!isset($invoice_booking->discountAmount)) {
            $invoice_booking->discountAmount = 0;
        }
        if (!isset($invoice_booking->undiscountedTotal)) {
            $invoice_booking->undiscountedTotal = $invoice_booking->totalPrice ?? 0;
        }

        try {
            // Gửi email thật
            Mail::send('admin.emails.invoice', compact('invoice_booking'), function ($message) use ($invoice_booking) {
                $message->to($invoice_booking->email)
                    ->subject('Hóa đơn đặt tour của khách hàng ' . $invoice_booking->fullName);
            });

            return response()->json([
                'success' => true,
                'message' => 'Hóa đơn đã được gửi qua email thành công.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending invoice email', [
                'bookingId' => $bookingId,
                'email' => $invoice_booking->email ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
            ], 500);
        }

    }

    public function finishBooking(Request $request)
    {
        $bookingId = $request->bookingId;

        $dataConfirm = [
            'bookingStatus' => 'f'
        ];

        $result = $this->booking->updateBooking($bookingId, $dataConfirm);

        if ($result) {
            $list_booking = $this->booking->getBooking();
            $list_booking = $this->updateHideBooking($list_booking);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'data' => view('admin.partials.list-booking', compact('list_booking'))->render()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại.'
            ], 500);
        }
    }

    public function receiviedMoney(Request $request){
        $bookingId = $request->bookingId;

        $dataUpdate = [
            'paymentStatus' => 'y'
        ];

        $result = $this->booking->updateCheckout($bookingId, $dataUpdate);

        if ($result) {

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại.'
            ], 500);
        }
    }

    private function updateHideBooking($list_booking)
    {
        // Lấy ngày hiện tại
        $currentDate = date('Y-m-d');

        foreach ($list_booking as $booking) {
            // So sánh endDate của booking với ngày hiện tại
            if ($booking->endDate < $currentDate) {
                $hide = '';
            } else {
                $hide = 'hide';
            }

            // Gán giá trị $hide vào mỗi booking
            $booking->hide = $hide;
        }

        return $list_booking;
    }

}
