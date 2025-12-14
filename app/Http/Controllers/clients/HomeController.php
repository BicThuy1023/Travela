<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\clients\Home;
use App\Models\clients\Tours;
use App\Models\Promotion;
use App\Services\RecommendationService;
use Carbon\Carbon;

class HomeController extends Controller
{
    private $homeTours;
    private $tours;
    private $recommendationService;

    public function __construct()
    {
        parent::__construct();
        $this->homeTours = new Home();
        $this->tours = new Tours();
        $this->recommendationService = new RecommendationService();
    }
    
    public function index()
    {
        $title = 'Trang chủ';
        $tours = $this->homeTours->getHomeTours();

        $userId = $this->getUserId();
        $toursPopular = $this->recommendationService->getTrendingTours();
        $userRecommendations = collect();

        if ($userId) {
            $userRecommendations = $this->recommendationService->getUserRecommendations($userId);
        }

        // Lấy mã khuyến mãi nổi bật (tối đa 4 mã cho modal, hiển thị cả mã hết lượt)
        $today = Carbon::today();
        $promotions = Promotion::where('is_active', 1)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('created_at', 'DESC')
            ->limit(4)
            ->get();

        return view('clients.home', compact('title', 'tours', 'toursPopular', 'userRecommendations', 'promotions'));
    }
}
