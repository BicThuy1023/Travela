<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\clients\Tours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    /**
     * @var \App\Models\clients\Tours
     */
    protected $tours;

    public function __construct()
    {
        $this->tours = new Tours();
    }

    /**
     * Trang /search: nhận keyword + ngày từ form, truyền cho view
     * Việc tìm kiếm thực tế sẽ do JS gọi API searchToursAjax()
     */
    public function index(Request $request)
    {
        $title     = 'Tìm kiếm';
        $keyword   = $request->input('keyword', '');
        $startDate = $request->input('start_date', '');
        $endDate   = $request->input('end_date', '');

        return view('clients.search', compact('title', 'keyword', 'startDate', 'endDate'));
    }

    /**
     * Tìm kiếm bằng keyword (voice/text) + AI Python (route: /search-voice-text)
     */
    public function searchTours(Request $request)
    {
        $title   = 'Tìm kiếm';
        $keyword = $request->input('keyword');
        $resultTours = [];

        // Gọi API Python đã xử lý để lấy danh sách tour tìm kiếm
        if (!empty($keyword)) {
            try {
                $apiUrl   = 'http://127.0.0.1:5555/api/search-tours';
                $response = Http::get($apiUrl, [
                    'keyword' => $keyword,
                ]);

                if ($response->successful()) {
                    $resultTours = $response->json('related_tours') ?? [];
                }
            } catch (\Exception $e) {
                // Xử lý lỗi khi gọi API
                Log::error('Lỗi khi gọi API tìm kiếm tour (Python): ' . $e->getMessage());
            }
        }

        if (!empty($resultTours)) {
            // Nếu API trả về list id tour liên quan thì dùng hàm này
            $tours = $this->tours->toursSearch($resultTours);
        } else {
            // Fallback: tìm kiếm trực tiếp trong MySQL
            $dataSearch = [
                'keyword' => $keyword,
            ];
            $tours = $this->tours->searchTours($dataSearch);
        }

        return view('clients.search', compact('title', 'tours'));
    }

    /**
     * API cho JS: tìm tour theo keyword (không phân biệt dấu) + ngày + điểm bắt đầu/kết thúc (toạ độ)
     * Route trong api.php: /api/search-tours-js
     */
    public function searchToursAjax(Request $request)
{
    try {
        Log::info('=== SEARCH API CALLED ===');

        $keyword        = $request->get('keyword', '');
        $startDateInput = $request->get('start_date');
        $endDateInput   = $request->get('end_date');

        $filterStartLat = $request->get('start_lat');
        $filterStartLng = $request->get('start_lng');
        $filterEndLat   = $request->get('end_lat');
        $filterEndLng   = $request->get('end_lng');

        Log::info('Params', [
            'keyword'     => $keyword,
            'start_lat'   => $filterStartLat,
            'start_lng'   => $filterStartLng,
            'end_lat'     => $filterEndLat,
            'end_lng'     => $filterEndLng,
            'start_date'  => $startDateInput,
            'end_date'    => $endDateInput,
        ]);

        // Đổi định dạng ngày
        $startDate = null;
        $endDate   = null;

        if ($startDateInput) {
            try {
                $startDate = Carbon::createFromFormat('d/m/Y', $startDateInput)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Invalid start_date format', ['error' => $e->getMessage()]);
            }
        }

        if ($endDateInput) {
            try {
                $endDate = Carbon::createFromFormat('d/m/Y', $endDateInput)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Invalid end_date format', ['error' => $e->getMessage()]);
            }
        }

        // Logic bắt buộc đủ 4 tọa độ
        $hasAnyRouteParam =
            ($filterStartLat !== null && $filterStartLat !== '') ||
            ($filterStartLng !== null && $filterStartLng !== '') ||
            ($filterEndLat   !== null && $filterEndLat   !== '') ||
            ($filterEndLng   !== null && $filterEndLng   !== '');

        $routeFilterEnabled =
            ($filterStartLat !== null && $filterStartLat !== '') &&
            ($filterStartLng !== null && $filterStartLng !== '') &&
            ($filterEndLat   !== null && $filterEndLat   !== '') &&
            ($filterEndLng   !== null && $filterEndLng   !== '');

        Log::info('Route filter detection', [
            'hasAnyRouteParam'   => $hasAnyRouteParam,
            'routeFilterEnabled' => $routeFilterEnabled,
        ]);

        if ($hasAnyRouteParam && !$routeFilterEnabled) {
            Log::warning('Missing one of 4 route params -> return empty');
            return response()->json([
                'success' => true,
                'data'    => [],
            ], 200);
        }

        // Build query
        $query = DB::table('tbl_tours')
            ->where('availability', 1);

        if ($routeFilterEnabled) {
            $query->where('location_lat', $filterStartLat)
                  ->where('location_lng', $filterStartLng)
                  ->where('end_lat', $filterEndLat)
                  ->where('end_lng', $filterEndLng);
        }

        if ($startDate) {
            $query->whereDate('startDate', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('endDate', '<=', $endDate);
        }

        Log::info('SQL built', [
            'sql'      => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $tours = $query->select(
            'tourId',
            'title',
            'destination',
            'time',
            'quantity',
            'priceAdult',
            'startDate',
            'endDate',
            'location_lat',
            'location_lng',
            'end_lat',
            'end_lng'
        )->get();

        Log::info('Tours after DB query', ['count' => $tours->count()]);

        // Lọc keyword không dấu (nếu có)
        if ($keyword !== '') {
            $normalizedKeyword = $this->vn_to_str(mb_strtolower(trim($keyword)));
            $before = $tours->count();

            $tours = $tours->filter(function ($tour) use ($normalizedKeyword) {
                $dest  = $tour->destination ?? '';
                $title = $tour->title ?? '';

                $nd = $this->vn_to_str(mb_strtolower($dest));
                $nt = $this->vn_to_str(mb_strtolower($title));

                return str_contains($nd, $normalizedKeyword)
                    || str_contains($nt, $normalizedKeyword);
            });

            Log::info('Tours after keyword filter', [
                'before'  => $before,
                'after'   => $tours->count(),
                'keyword' => $normalizedKeyword,
            ]);
        }

        // Gắn ảnh
        $tourIds = $tours->pluck('tourId')->all();
        Log::info('Attach images for tourIds', $tourIds);

        if (!empty($tourIds)) {
            $imageList = DB::table('tbl_images')
                ->whereIn('tourId', $tourIds)
                ->select('tourId', 'imageUrl')
                ->orderBy('imageId')
                ->get()
                ->groupBy('tourId');

            $tours = $tours->map(function ($tour) use ($imageList) {
                $tour->images = isset($imageList[$tour->tourId])
                    ? $imageList[$tour->tourId]->pluck('imageUrl')->toArray()
                    : [];
                return $tour;
            });
        }

        // Gắn rating
        $model = new Tours();
        foreach ($tours as $tour) {
            $stats        = $model->reviewStats($tour->tourId);
            $tour->rating = $stats->averageRating ?? 0;
        }

        Log::info('Final result count', ['count' => $tours->count()]);

        return response()->json([
            'success' => true,
            'data'    => $tours->values()->all(),
        ], 200);

    } catch (\Throwable $e) {
        Log::error('searchToursAjax FATAL', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Lỗi server.',
        ], 200);
    }
}

    /**
     * Bỏ dấu tiếng Việt (phục vụ tìm kiếm không dấu)
     */
    private function vn_to_str(string $str): string
    {
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẫ|ẩ|ậ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẫ|Ẩ|Ậ',
            'd' => 'đ',
            'D' => 'Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];

        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }

        return $str;
    }

    /**
     * Tìm tour gần vị trí được chọn trên bản đồ (nearby-tours)
     */
    public function searchNearby(Request $request)
{
    // Toạ độ người dùng chọn trên bản đồ
    $startLat = $request->query('start_lat');
    $startLng = $request->query('start_lng');
    $endLat   = $request->query('end_lat');
    $endLng   = $request->query('end_lng');

    $radiusKm = 50; // bán kính tìm gần (km) – cần chặt thì giảm xuống 30km chẳng hạn

    $tours = collect();

    if ($startLat && $startLng) {

        // Chuẩn bị selectRaw + bindings cho Haversine
        $bindings = [
            $startLat, $startLng, $startLat,   // cho start_distance
        ];

        $select = "
            tbl_tours.*,
            ROUND(
                6371 * acos(
                    cos(radians(?)) *
                    cos(radians(location_lat)) *
                    cos(radians(location_lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(location_lat))
                ),
                1
            ) AS start_distance
        ";

        // Nếu có cả điểm cuối thì tính luôn khoảng cách đến end_lat/end_lng
        if ($endLat && $endLng) {
            $select .= ",
            ROUND(
                6371 * acos(
                    cos(radians(?)) *
                    cos(radians(end_lat)) *
                    cos(radians(end_lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(end_lat))
                ),
                1
            ) AS end_distance
            ";

            $bindings = array_merge($bindings, [
                $endLat, $endLng, $endLat,     // cho end_distance
            ]);
        }

        $query = DB::table('tbl_tours')
            ->where('availability', 1)
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->whereNotNull('end_lat')
            ->whereNotNull('end_lng')
            ->selectRaw($select, $bindings)
            ->having('start_distance', '<=', $radiusKm)
            ->orderBy('start_distance', 'asc');

        // Nếu có chọn điểm cuối thì tour cũng phải kết thúc gần điểm đó
        if ($endLat && $endLng) {
            $query->having('end_distance', '<=', $radiusKm);
        }

        $tours = $query->get();

        // Gắn ảnh
        $tourIds = $tours->pluck('tourId')->all();

        if (!empty($tourIds)) {
            $imageList = DB::table('tbl_images')
                ->whereIn('tourId', $tourIds)
                ->select('tourId', 'imageUrl')
                ->orderBy('imageId')
                ->get()
                ->groupBy('tourId');

            $tours = $tours->map(function ($tour) use ($imageList) {
                $tour->images = isset($imageList[$tour->tourId])
                    ? $imageList[$tour->tourId]->pluck('imageUrl')->toArray()
                    : [];
                return $tour;
            });
        }

        // Gắn rating giống trang home
        $toursModel = new Tours();
        foreach ($tours as $tour) {
            $stats        = $toursModel->reviewStats($tour->tourId);
            $tour->rating = $stats->averageRating ?? 0;
        }
    }

    $title = 'Khám phá tour gần điểm bạn chọn';

    return view('clients.nearby_tours', [
        'title'     => $title,
        'tours'     => $tours,
        'startLat'  => $startLat,
        'startLng'  => $startLng,
        'endLat'    => $endLat,
        'endLng'    => $endLng,
        'isNearby'  => true,
    ]);
}

}
