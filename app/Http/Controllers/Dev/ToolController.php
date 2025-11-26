<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ToolController extends Controller
{
    /**
     * Cập nhật location_lat, location_lng cho tất cả tour
     * dựa trên chuỗi destination + mapping trong config/tour_cities.php
     */
    public function updateTourLocations()
    {
        $mapping = config('tour_cities'); // lấy mảng từ file config
        $totalUpdated = 0;
        $log = [];

        foreach ($mapping as $keyword => $coords) {
            $affected = DB::table('tbl_tours')
                ->where('destination', 'LIKE', '%' . $keyword . '%')
                ->update([
                    'location_lat' => $coords['lat'],
                    'location_lng' => $coords['lng'],
                ]);

            $totalUpdated += $affected;
            $log[] = "Từ khóa '{$keyword}' → $affected tour";
        }
        
        return response()->json([
            'message' => 'Đã cập nhật tọa độ cho tour xong.',
            'total_updated' => $totalUpdated,
            'detail' => $log,
        ]);
    }
}
