<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BookingModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_booking';

    public function getBooking(){

        $list_booking = DB::table($this->table)
        ->join('tbl_tours', 'tbl_tours.tourId', '=', 'tbl_booking.tourId')
        ->join('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
        ->orderByDesc('bookingDate')
        ->get();

        return $list_booking;
    }

    public function updateBooking($bookingId, $data){
        return DB::table($this->table)
        ->where('bookingId',$bookingId)
        ->update($data);
    }

    public function getInvoiceBooking($bookingId){

        // Kiểm tra xem là tour thông thường hay custom tour
        $booking = DB::table($this->table)
            ->where('bookingId', $bookingId)
            ->first();

        if (!$booking) {
            return null;
        }

        if ($booking->tourId) {
            // Tour thông thường
            $invoice = DB::table($this->table)
                ->join('tbl_tours', 'tbl_tours.tourId', '=', 'tbl_booking.tourId')
                ->join('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
                ->where('tbl_booking.bookingId', $bookingId)
                ->first();
            
            // Với tour thông thường, không có giảm giá từ price_breakdown
            if ($invoice) {
                $invoice->discountAmount = 0;
                $invoice->undiscountedTotal = $invoice->totalPrice ?? 0;
                $invoice->groupDiscountPercent = 0;
            }
        } else {
            // Custom tour
            $invoice = DB::table($this->table)
                ->join('tbl_custom_tours', 'tbl_booking.custom_tour_id', '=', 'tbl_custom_tours.id')
                ->join('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
                ->where('tbl_booking.bookingId', $bookingId)
                ->select(
                    'tbl_booking.*',
                    'tbl_custom_tours.option_json',
                    'tbl_custom_tours.destination',
                    'tbl_custom_tours.days',
                    'tbl_custom_tours.nights',
                    'tbl_custom_tours.start_date',
                    'tbl_custom_tours.end_date',
                    'tbl_checkout.paymentMethod',
                    'tbl_checkout.paymentStatus',
                    'tbl_checkout.transactionId',
                    'tbl_checkout.checkoutId',
                    'tbl_checkout.paymentDate',
                    'tbl_checkout.amount',
                    DB::raw('NULL as tourId'),
                    DB::raw('NULL as priceAdult'),
                    DB::raw('NULL as priceChild')
                )
                ->first();

            if ($invoice) {
                // Parse option_json để lấy thông tin tour
                $option = json_decode($invoice->option_json, true) ?? [];
                $priceBreakdown = $option['price_breakdown'] ?? [];
                
                $invoice->title = $option['title'] ?? 'Tour theo yêu cầu';
                $invoice->priceAdult = $priceBreakdown['adult_price'] ?? 0;
                $invoice->priceChild = $priceBreakdown['child_price'] ?? 0;
                $invoice->time = ($invoice->days ?? 0) . ' ngày ' . ($invoice->nights ?? 0) . ' đêm';
                
                // Lấy thông tin giảm giá từ price_breakdown
                $invoice->discountAmount = $priceBreakdown['discount_amount_total'] ?? 0;
                $invoice->undiscountedTotal = $priceBreakdown['undiscounted_total'] ?? ($invoice->totalPrice ?? 0);
                $invoice->groupDiscountPercent = $priceBreakdown['group_discount_percent'] ?? 0;
                
                // Lấy startDate và endDate từ custom_tours
                $invoice->startDate = $invoice->start_date ?? $invoice->bookingDate ?? now();
                $invoice->endDate = $invoice->end_date ?? null;
                
                // Lấy paymentDate và amount từ tbl_checkout (nếu có)
                $checkout = DB::table('tbl_checkout')
                    ->where('bookingId', $bookingId)
                    ->first();
                $invoice->paymentDate = $checkout->paymentDate ?? $invoice->bookingDate ?? now();
                
                // Đảm bảo amount có giá trị (ưu tiên từ checkout, fallback về totalPrice)
                if (!isset($invoice->amount) || $invoice->amount === null) {
                    $invoice->amount = $invoice->totalPrice ?? 0;
                }
            }
        }

        return $invoice;
    }

    public function updateCheckout($bookingId, $data){
        return DB::table('tbl_checkout')
        ->where('bookingId',$bookingId)
        ->update($data);
    }
}
