<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\clients\Tours;
use App\Models\Promotion;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ToursController extends Controller
{

    private $tours;
    private $recommendationService;

    public function __construct()
    {
        $this->tours = new Tours();
        $this->recommendationService = new RecommendationService();
    }

    public function index(Request $request)
    {
        $title = 'Tours';
        $tours = $this->tours->getAllTours(9);
        $domain = $this->tours->getDomain();
        $domainsCount = [
            'mien_bac' => optional($domain->firstWhere('domain', 'b'))->count,
            'mien_trung' => optional($domain->firstWhere('domain', 't'))->count,
            'mien_nam' => optional($domain->firstWhere('domain', 'n'))->count,
        ];

        if ($request->ajax()) {
            return response()->json([
                'tours' => view('clients.partials.filter-tours', compact('tours'))->render(),
            ]);
        }
        
        $toursPopular = $this->recommendationService->getTrendingTours(3);

        // Lấy mã khuyến mãi đang hoạt động (tối đa 2 mã cho sidebar)
        $today = Carbon::today();
        $promotions = Promotion::where('is_active', 1)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(function($query) {
                $query->where('usage_limit', 0)
                      ->orWhereRaw('usage_count < usage_limit');
            })
            ->orderBy('created_at', 'DESC')
            ->limit(2)
            ->get();

        return view('clients.tours', compact('title', 'tours', 'domainsCount','toursPopular', 'promotions'));
    }

    public function filterTours(Request $req)
    {

        $conditions = [];
        $sorting = [];

        if ($req->filled('minPrice') && $req->filled('maxPrice')) {
            $minPrice = $req->minPrice;
            $maxPrice = $req->maxPrice;
            $conditions[] = ['priceAdult', '>=', $minPrice];
            $conditions[] = ['priceAdult', '<=', $maxPrice];
        }

        if ($req->filled('domain')) {
            $domain = $req->domain;
            $conditions[] = ['domain', '=', $domain];
        }

        if ($req->filled('star')) {
            $star = (float) $req->star;
            // Lọc theo sao: >= star và < star + 1 (ví dụ: 4 sao = >= 4.0 và < 5.0)
            $conditions[] = ['averageRating', '>=', $star];
            $conditions[] = ['averageRating', '<', $star + 1];
        }

        if ($req->filled('time')) {
            $duration = $req->time;
            $time = [
                '3n2d' => '3 ngày 2 đêm',
                '4n3d' => '4 ngày 3 đêm',
                '5n4d' => '5 ngày 4 đêm'
            ];
            $conditions[] = ['time', '=', $time[$duration]];
        }

        if ($req->filled('sorting')) {
            $sortingOption = trim($req->sorting);

            if ($sortingOption == 'new') {
                $sorting = ['tourId', 'DESC'];
            } elseif ($sortingOption == 'old') {
                $sorting = ['tourId', 'ASC'];
            } elseif ($sortingOption == "hight-to-low") {
                $sorting = ['priceAdult', 'DESC'];
            } elseif ($sortingOption == "low-to-high") {
                $sorting = ['priceAdult', 'ASC'];
            }
        }

        $tours = $this->tours->filterTours($conditions, $sorting, 9);

        // Đảm bảo paginator có query string để giữ filter khi chuyển trang
        if ($tours instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $tours->appends($req->query());
        }

        // Trả về view (luôn trả về view, không cần check ajax vì route đã xử lý)
        return view('clients.partials.filter-tours', compact('tours'));

    }
}
