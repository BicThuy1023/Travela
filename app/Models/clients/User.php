<?php

namespace App\Models\clients;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    use HasFactory;

    protected $table = 'tbl_users';

    public function getUserId($username)
    {
        return DB::table($this->table)
            ->select('userId')
            ->where('username', $username)
            ->value('userId');
    }

    public function getUser($id)
    {
        return DB::table($this->table)
            ->where('userId', $id)
            ->first();
    }

    public function updateUser($id, $data)
    {
        return DB::table($this->table)
            ->where('userId', $id)   
            ->update($data);
    }

    public function getMyTours($id)
    {
        /** @var \Illuminate\Support\Collection<int, object> $myTours */
        // Lấy cả tour thông thường và custom tours
        $regularTours = DB::table('tbl_booking')
            ->join('tbl_tours', 'tbl_booking.tourId', '=', 'tbl_tours.tourId')
            ->join('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
            ->where('tbl_booking.userId', $id)
            ->whereNotNull('tbl_booking.tourId') // Chỉ lấy tour thông thường
            ->select(
                'tbl_booking.*',
                'tbl_tours.tourId',
                'tbl_tours.title',
                'tbl_tours.description',
                'tbl_tours.destination',
                'tbl_tours.time',
                'tbl_checkout.checkoutId',
                'tbl_checkout.paymentStatus',
                'tbl_checkout.paymentMethod'
            )
            ->orderByDesc('tbl_booking.bookingDate')
            ->get();

        $customTours = DB::table('tbl_booking')
            ->join('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
            ->join('tbl_custom_tours', 'tbl_booking.custom_tour_id', '=', 'tbl_custom_tours.id')
            ->where('tbl_booking.userId', $id)
            ->whereNotNull('tbl_booking.custom_tour_id') // Chỉ lấy custom tours
            ->select(
                'tbl_booking.*',
                'tbl_custom_tours.option_json',
                'tbl_custom_tours.destination',
                'tbl_checkout.checkoutId',
                'tbl_checkout.paymentStatus',
                'tbl_checkout.paymentMethod'
            )
            ->orderByDesc('tbl_booking.bookingDate')
            ->get();

        // Xử lý custom tours để lấy title, description, destination từ option_json
        foreach ($customTours as $tour) {
            $option = json_decode($tour->option_json, true) ?? [];
            $tour->tourId = null; // Đánh dấu là custom tour
            $tour->title = $option['title'] ?? 'Tour theo yêu cầu';
            $tour->description = 'Tour được thiết kế theo yêu cầu của bạn';
            $tour->destination = $option['destination'] ?? ($tour->destination ?? 'Đang cập nhật');
            $tour->time = ($option['days'] ?? 0) . 'N' . ($option['nights'] ?? 0) . 'Đ';
        }

        // Gộp cả hai loại tour
        $myTours = $regularTours->merge($customTours)->sortByDesc('bookingDate')->take(10);

        foreach ($myTours as $tour) {
            /** @var object $tour */
            
            // Rating chỉ áp dụng cho tour thông thường
            if ($tour->tourId) {
                $tour->rating = DB::table('tbl_reviews')
                    ->where('tourId', $tour->tourId)
                    ->where('userId', $id)
                    ->value('rating');

                $images = DB::table('tbl_images')
                    ->where('tourId', $tour->tourId)
                    ->pluck('imageUrl');
                $tour->images = $images->isNotEmpty() ? $images->toArray() : ['default.jpg'];
            } else {
                // Custom tour: dùng ảnh mặc định
                $tour->rating = null;
                $tour->images = ['custom-1.jpg']; // Ảnh mặc định cho custom tour (chỉ tên file, không có đường dẫn)
            }
        }

        return $myTours;
    }
}
