<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    /**
     * Áp dụng mã khuyến mãi
     */
    public function applyCode(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'code' => 'required|string',
            'tour_id' => 'required|integer|exists:tbl_tours,tourId',
            'total' => 'required|numeric|min:0',
        ], [
            'code.required' => 'Vui lòng nhập mã khuyến mãi',
            'tour_id.required' => 'Thiếu thông tin tour',
            'tour_id.exists' => 'Tour không tồn tại',
            'total.required' => 'Thiếu thông tin tổng tiền',
            'total.min' => 'Tổng tiền không hợp lệ',
        ]);

        $code = strtoupper(trim($validated['code']));
        $tourId = (int) $validated['tour_id'];
        $total = (float) $validated['total'];
        $userId = $this->getUserId(); // Lấy userId từ session nếu có

        try {
            // 1. Tìm promotion theo code
            $promotion = Promotion::where('code', $code)->first();

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã khuyến mãi không tồn tại'
                ], 400);
            }

            // 2. Kiểm tra is_active
            if (!$promotion->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã khuyến mãi đã bị vô hiệu hóa'
                ], 400);
            }

            // 3. Kiểm tra ngày hiện tại nằm trong khoảng start_date - end_date
            $today = Carbon::today();
            $startDate = Carbon::parse($promotion->start_date);
            $endDate = Carbon::parse($promotion->end_date);

            if ($today->lt($startDate) || $today->gt($endDate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã khuyến mãi chưa có hiệu lực hoặc đã hết hạn'
                ], 400);
            }

            // 4. Kiểm tra usage_limit (tổng lượt dùng toàn hệ thống)
            if ($promotion->usage_limit > 0 && $promotion->usage_count >= $promotion->usage_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã khuyến mãi đã hết lượt sử dụng'
                ], 400);
            }

            // 5. Kiểm tra per_user_limit (số lần / 1 user)
            if ($userId && $promotion->per_user_limit > 0) {
                // Đếm số lần user đã sử dụng mã này
                $userUsageCount = DB::table('tbl_checkout')
                    ->join('tbl_booking', 'tbl_checkout.bookingId', '=', 'tbl_booking.bookingId')
                    ->where('tbl_checkout.promotion_id', $promotion->id)
                    ->where('tbl_booking.userId', $userId)
                    ->count();

                if ($userUsageCount >= $promotion->per_user_limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn đã sử dụng hết lượt áp dụng mã này'
                    ], 400);
                }
            }

            // 6. Kiểm tra min_order_amount
            if ($total < $promotion->min_order_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng tối thiểu phải từ ' . number_format($promotion->min_order_amount, 0, ',', '.') . ' VNĐ'
                ], 400);
            }

            // 7. Kiểm tra apply_type
            if ($promotion->apply_type === 'specific_tours') {
                $tourIds = $promotion->tours->pluck('tourId')->toArray();
                if (!in_array($tourId, $tourIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mã khuyến mãi không áp dụng cho tour này'
                    ], 400);
                }
            }

            // 8. Tính toán giảm giá
            $discount = 0;
            if ($promotion->discount_type === 'percent') {
                $discount = floor($total * $promotion->discount_value / 100);
                // Áp dụng max_discount_amount nếu có
                if ($promotion->max_discount_amount > 0) {
                    $discount = min($discount, $promotion->max_discount_amount);
                }
            } else {
                // fixed
                $discount = $promotion->discount_value;
            }

            // Không cho phép giảm quá tổng tiền
            $discount = min($discount, $total);
            $finalTotal = $total - $discount;

            // 9. Lưu vào session
            session([
                'promotion_id' => $promotion->id,
                'promotion_code' => $promotion->code,
                'discount_amount' => $discount,
                'final_total' => $finalTotal,
            ]);

            return response()->json([
                'success' => true,
                'discount' => $discount,
                'final_total' => $finalTotal,
                'message' => 'Áp dụng mã khuyến mãi thành công'
            ]);

        } catch (Exception $e) {
            Log::error('Error applying promotion code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi áp dụng mã khuyến mãi'
            ], 500);
        }
    }

    /**
     * Hủy áp dụng mã khuyến mãi
     */
    public function removeCode()
    {
        session()->forget(['promotion_id', 'promotion_code', 'discount_amount', 'final_total']);

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy áp dụng mã khuyến mãi'
        ]);
    }

    /**
     * Hiển thị danh sách mã khuyến mãi cho khách hàng
     */
    public function index()
    {
        $title = 'Mã khuyến mãi';
        
        // Lấy các mã khuyến mãi đang hoạt động và còn hiệu lực (hiển thị cả mã hết lượt)
        $today = Carbon::today();
        
        $promotions = Promotion::where('is_active', 1)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with('tours')
            ->orderBy('created_at', 'DESC')
            ->get();

        return view('clients.promotions', compact('title', 'promotions'));
    }
}
