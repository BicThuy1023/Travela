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
     * API cho JS: tìm tour theo keyword (không phân biệt dấu) + ngày
     * Route trong api.php: /api/search-tours-js
     */
    public function searchToursAjax(Request $request)
{
    try {
        $keyword        = $request->get('keyword', '');
        $startDateInput = $request->get('start_date'); // d/m/Y
        $endDateInput   = $request->get('end_date');   // d/m/Y

        // Chuẩn hóa keyword: lowercase + bỏ dấu
        $normalizedKeyword = $this->vn_to_str(mb_strtolower(trim($keyword)));

        // Đổi định dạng ngày
        $startDate = null;
        $endDate   = null;

        if (!empty($startDateInput)) {
            try {
                $startDate = Carbon::createFromFormat('d/m/Y', $startDateInput)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('searchToursAjax: start_date không đúng định dạng', [
                    'value' => $startDateInput,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($endDateInput)) {
            try {
                $endDate = Carbon::createFromFormat('d/m/Y', $endDateInput)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('searchToursAjax: end_date không đúng định dạng', [
                    'value' => $endDateInput,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Lấy tất cả tour còn hoạt động
        $tours = DB::table('tbl_tours')
            ->where('availability', 1)
            ->get();

        // Lọc theo ngày (nếu có)
        if (!empty($startDate)) {
            $tours = $tours->filter(function ($tour) use ($startDate) {
                return $tour->startDate >= $startDate;
            });
        }

        if (!empty($endDate)) {
            $tours = $tours->filter(function ($tour) use ($endDate) {
                return $tour->endDate <= $endDate;
            });
        }

        // Lọc theo keyword (không dấu) theo destination + title
        if ($normalizedKeyword !== '') {
            $tours = $tours->filter(function ($tour) use ($normalizedKeyword) {
                $dest  = $tour->destination ?? '';
                $title = $tour->title ?? '';

                $normalizedDest  = $this->vn_to_str(mb_strtolower($dest));
                $normalizedTitle = $this->vn_to_str(mb_strtolower($title));

                return str_contains($normalizedDest, $normalizedKeyword)
                    || str_contains($normalizedTitle, $normalizedKeyword);
            });
        }

        // Gắn hình ảnh cho từng tour
        $tourIds = $tours->pluck('tourId')->all();

        if (!empty($tourIds)) {
            $imageList = DB::table('tbl_images')
                ->whereIn('tourId', $tourIds)
                ->select('tourId', 'imageURL')
                ->orderBy('imageId')
                ->get()
                ->groupBy('tourId');

            $tours = $tours->map(function ($tour) use ($imageList) {
                $tour->images = isset($imageList[$tour->tourId])
                    ? $imageList[$tour->tourId]->pluck('imageURL')->toArray()
                    : [];

                return $tour;
            });
        } else {
            // Không có tour nào, vẫn trả mảng rỗng
            $tours = collect();
        }
         // ➜ TÍNH RATING CHO TỪNG TOUR (dùng model Tours như trang home)
        $toursModel = new Tours();
        foreach ($tours as $tour) {
            $stats = $toursModel->reviewStats($tour->tourId);
            $tour->rating = $stats->averageRating ?? 0; // nếu null thì 0
        }

        return response()->json([
            'success' => true,
            'data'    => $tours->values()->all(),
        ], 200);

    } catch (\Throwable $e) {
        // Nếu có lỗi bất ngờ, log lại và trả JSON chuẩn (status 200 để JS không bị lỗi .json())
        Log::error('searchToursAjax error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi tìm kiếm tour.',
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
    public function searchNearby(Request $request)
{
    $lat = $request->query('lat');
    $lng = $request->query('lng');
    $keyword   = $request->query('keyword', null);
    $startDate = $request->query('start_date', null);
    $endDate   = $request->query('end_date', null);

    $tours  = collect();
    $radius = 50; // km

    if ($lat && $lng) {
        $tours = DB::table('tbl_tours')
            ->where('availability', 1)
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(location_lat)) *
                    cos(radians(location_lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(location_lat))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        // Gắn ảnh
        $tourIds = $tours->pluck('tourId')->all();

        if (!empty($tourIds)) {
            $imageList = DB::table('tbl_images')
                ->whereIn('tourId', $tourIds)
                ->select('tourId', 'imageURL')
                ->orderBy('imageId')
                ->get()
                ->groupBy('tourId');

            $tours = $tours->map(function ($tour) use ($imageList) {
                $tour->images = isset($imageList[$tour->tourId])
                    ? $imageList[$tour->tourId]->pluck('imageURL')->toArray()
                    : [];

                return $tour;
            });
        }

        // Gắn rating giống trang home
        $toursModel = new Tours();
        foreach ($tours as $tour) {
            $stats = $toursModel->reviewStats($tour->tourId);
            $tour->rating = $stats->averageRating ?? 0;
        }
    }

    // *** QUAN TRỌNG: truyền $title cho view ***
    $title = 'Khám phá tour du lịch gần bạn';

    return view('clients.search', [
        'title'    => $title,   // <- thêm dòng này
        'tours'    => $tours,
        'lat'      => $lat,
        'lng'      => $lng,
        'keyword'  => $keyword,
        'startDate'=> $startDate,
        'endDate'  => $endDate,
        'isNearby' => true,     // flag báo là search theo bản đồ
    ]);
}

}
