<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToursModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_tours';

    public function getAllTours()
    {
        return DB::table($this->table)
            ->select('*')
            ->orderBy('tourId', 'DESC')
            ->get();
    }

    public function createTours($data)
    {
        return DB::table($this->table)->insertGetId($data);
    }

    public function uploadImages($data)
    {
        return DB::table('tbl_images')->insert($data);
    }

    public function uploadTempImages($data)
    {
        return DB::table('tbl_temp_images')->insert($data);
    }

    public function addTimeLine($data)
    {
        return DB::table('tbl_timeline')->insert($data);
    }

    public function updateTour($tourId,$data){
        $updated = DB::table($this->table)
        ->where('tourId',$tourId)
        ->update($data);

        return $updated;
    }
    public function deleteTour($tourId)
    {
        try {
            // Xóa các dữ liệu liên quan (không quan trọng có dữ liệu hay không)
            DB::table('tbl_timeline')->where('tourId', $tourId)->delete();
            DB::table('tbl_images')->where('tourId', $tourId)->delete();
            // Xóa các bản ghi trong bảng promotion_tour nếu có
            DB::table('tbl_promotion_tour')->where('tour_id', $tourId)->delete();

            // Xóa tour chính
            $deleteTour = DB::table($this->table)->where('tourId', $tourId)->delete();

            // Kiểm tra kết quả xóa tour
            if ($deleteTour > 0) {
                return ['success' => true, 'message' => 'Tour đã được xóa thành công.'];
            } else {
                return ['success' => false, 'message' => 'Không tìm thấy tour để xóa hoặc tour đã bị xóa trước đó.'];
            }
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có (ví dụ: foreign key constraint)
            Log::error('Error deleting tour: ' . $e->getMessage(), [
                'tourId' => $tourId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Kiểm tra xem có phải lỗi do foreign key constraint không
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), '1451') !== false) {
                return ['success' => false, 'message' => 'Không thể xóa tour vì tour này đang được sử dụng trong các đơn đặt tour hoặc đánh giá. Vui lòng xóa các dữ liệu liên quan trước.'];
            }
            
            return ['success' => false, 'message' => 'Lỗi khi xóa tour: ' . $e->getMessage()];
        }
    }

    public function getTour($tourId){
        return DB::table($this->table)->where('tourId', $tourId)->first();
    }

    public function getImages($tourId){
        return DB::table('tbl_images')->where('tourId', $tourId)->get();
    }

    public function getTimeLine($tourId){
        return DB::table('tbl_timeline')->where('tourId', $tourId)->get();
    }

    public function deleteData($tourId, $tbl){
        return DB::table($tbl)->where('tourId', $tourId)->delete();
    }

}
