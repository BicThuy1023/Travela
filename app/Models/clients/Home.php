<?php

namespace App\Models\clients;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Home extends Model
{
    use HasFactory;

    protected $table = 'tbl_tours';

    // public function getHomeTours()
    // {
    //     // Lấy ngày hiện tại
    //     $today = Carbon::now()->toDateString();

    //     // Lấy các tour còn hoạt động (availability = 1) và chưa hết hạn
    //     $tours = DB::table($this->table)
    //         ->where('availability', 1)
    //         ->whereDate('endDate', '>=', $today)
    //         ->orderBy('startDate', 'asc') // sắp xếp gần nhất trước
    //         ->take(8)
    //         ->get();

    //     foreach ($tours as $tour) {
    //         // Lấy danh sách hình ảnh của tour
    //         $tour->images = DB::table('tbl_images')
    //             ->where('tourId', $tour->tourId)
    //             ->pluck('imageUrl');
        
                
    //         // Lấy đánh giá trung bình
    //         $toursModel = new Tours();
    //         $reviewStats = $toursModel->reviewStats($tour->tourId);
    //         $tour->rating = $reviewStats ? $reviewStats->averageRating : 0;
    //     }

    //     return $tours;
    // }

    public function getHomeTours() {
    // Lấy thông tin tour
        $tours = DB::table($this->table)
            ->where('availability', 1) 
            ->take(8)

            ->get();
            
        /** @var \stdClass $tour */
        foreach ($tours as $tour) {
        // Lấy danh sách hình ảnh thuộc về tour
            $tour->images = DB::table('tbl_images')
                ->where('tourId', $tour->tourId) 
                ->pluck('imageUrl');

        // Tạo instance của Tours và gọi reviewStats
            $toursModel = new Tours();
            $tour->rating = $toursModel->reviewStats($tour->tourId)->averageRating;
        }
        return $tours;
    }
}
