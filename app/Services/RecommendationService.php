<?php

namespace App\Services;

use App\Models\clients\Tours;
use App\Models\clients\Booking;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    private $toursModel;

    public function __construct()
    {
        $this->toursModel = new Tours();
    }

    public function getSimilarTours($tourId)
    {
        try {
            $apiUrl = 'http://127.0.0.1:5555/api/tour-recommendations';
            $response = Http::timeout(2)->get($apiUrl, [
                'tour_id' => $tourId
            ]);

            if ($response->successful()) {
                $relatedTours = $response->json('related_tours');
                if (!empty($relatedTours) && is_array($relatedTours)) {
                    $tours = $this->toursModel->toursRecommendation($relatedTours);
                    if ($tours->isNotEmpty()) {
                        return $tours->take(5);
                    }
                }
            }
        } catch (Exception $e) {
            Log::info('Python API failed for getSimilarTours, using fallback: ' . $e->getMessage());
        }

        // Fallback: Similar tours by destination/domain/price range
        $currentTour = DB::table('tbl_tours')
            ->where('tourId', $tourId)
            ->where('availability', 1)
            ->first();

        if (!$currentTour) {
            return collect();
        }

        $priceRange = $currentTour->priceAdult ?? 0;
        $priceMin = $priceRange * 0.7;
        $priceMax = $priceRange * 1.3;

        $similarTours = DB::table('tbl_tours')
            ->where('tourId', '!=', $tourId)
            ->where('availability', 1)
            ->where(function ($query) use ($currentTour, $priceMin, $priceMax) {
                if (!empty($currentTour->destination)) {
                    $destKeywords = explode(' ', $currentTour->destination);
                    foreach ($destKeywords as $keyword) {
                        if (strlen($keyword) > 2) {
                            $query->orWhere('destination', 'LIKE', '%' . $keyword . '%');
                        }
                    }
                }
                $query->orWhereBetween('priceAdult', [$priceMin, $priceMax]);
                if (!empty($currentTour->domain)) {
                    $query->orWhere('domain', $currentTour->domain);
                }
            })
            ->limit(5)
            ->get();

        foreach ($similarTours as $tour) {
            $tour->images = DB::table('tbl_images')
                ->where('tourId', $tour->tourId)
                ->pluck('imageUrl');
            $tour->rating = $this->toursModel->reviewStats($tour->tourId)->averageRating;
        }

        return $similarTours;
    }

    public function getTrendingTours($limit = 6)
    {
        try {
            $apiUrl = 'http://127.0.0.1:5555/api/tour-recommendations';
            $response = Http::timeout(2)->get($apiUrl, [
                'type' => 'hot'
            ]);

            if ($response->successful()) {
                $tourIds = $response->json('related_tours') ?? $response->json('recommended_tours') ?? [];
                if (!empty($tourIds) && is_array($tourIds)) {
                    $tours = $this->toursModel->toursRecommendation($tourIds);
                    if ($tours->isNotEmpty()) {
                        return $tours->take($limit);
                    }
                }
            }
        } catch (Exception $e) {
            Log::info('Python API failed for getTrendingTours, using fallback: ' . $e->getMessage());
        }

        // Fallback: Order by bookings + views (if views column exists)
        try {
            $trendingTours = DB::table('tbl_tours')
                ->leftJoin('tbl_booking', 'tbl_tours.tourId', '=', 'tbl_booking.tourId')
                ->select(
                    'tbl_tours.tourId',
                    'tbl_tours.title',
                    'tbl_tours.description',
                    'tbl_tours.priceAdult',
                    'tbl_tours.priceChild',
                    'tbl_tours.time',
                    'tbl_tours.destination',
                    'tbl_tours.quantity',
                    'tbl_tours.domain',
                    'tbl_tours.startDate',
                    'tbl_tours.endDate',
                    'tbl_tours.availability',
                    DB::raw('COUNT(DISTINCT tbl_booking.bookingId) as bookingCount'),
                    DB::raw('COALESCE(tbl_tours.views, 0) as views')
                )
                ->where('tbl_tours.availability', 1)
                ->groupBy(
                    'tbl_tours.tourId',
                    'tbl_tours.title',
                    'tbl_tours.description',
                    'tbl_tours.priceAdult',
                    'tbl_tours.priceChild',
                    'tbl_tours.time',
                    'tbl_tours.destination',
                    'tbl_tours.quantity',
                    'tbl_tours.domain',
                    'tbl_tours.startDate',
                    'tbl_tours.endDate',
                    'tbl_tours.availability',
                    'tbl_tours.views'
                )
                ->orderByRaw('(bookingCount * 2 + COALESCE(tbl_tours.views, 0)) DESC')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            // If views column doesn't exist, use simpler query
            Log::info('Views column not available, using booking count only: ' . $e->getMessage());
            $trendingTours = DB::table('tbl_tours')
                ->leftJoin('tbl_booking', 'tbl_tours.tourId', '=', 'tbl_booking.tourId')
                ->select(
                    'tbl_tours.tourId',
                    'tbl_tours.title',
                    'tbl_tours.description',
                    'tbl_tours.priceAdult',
                    'tbl_tours.priceChild',
                    'tbl_tours.time',
                    'tbl_tours.destination',
                    'tbl_tours.quantity',
                    'tbl_tours.domain',
                    'tbl_tours.startDate',
                    'tbl_tours.endDate',
                    'tbl_tours.availability',
                    DB::raw('COUNT(DISTINCT tbl_booking.bookingId) as bookingCount')
                )
                ->where('tbl_tours.availability', 1)
                ->groupBy(
                    'tbl_tours.tourId',
                    'tbl_tours.title',
                    'tbl_tours.description',
                    'tbl_tours.priceAdult',
                    'tbl_tours.priceChild',
                    'tbl_tours.time',
                    'tbl_tours.destination',
                    'tbl_tours.quantity',
                    'tbl_tours.domain',
                    'tbl_tours.startDate',
                    'tbl_tours.endDate',
                    'tbl_tours.availability'
                )
                ->orderByRaw('bookingCount DESC')
                ->limit($limit)
                ->get();
        }

        foreach ($trendingTours as $tour) {
            $tour->images = DB::table('tbl_images')
                ->where('tourId', $tour->tourId)
                ->pluck('imageUrl');
            $tour->rating = $this->toursModel->reviewStats($tour->tourId)->averageRating;
        }

        return $trendingTours;
    }

    public function getUserRecommendations($userId)
    {
        try {
            $apiUrl = 'http://127.0.0.1:5555/api/user-recommendations';
            $response = Http::timeout(2)->get($apiUrl, [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                $tourIds = $response->json('recommended_tours') ?? [];
                if (!empty($tourIds) && is_array($tourIds)) {
                    $tours = $this->toursModel->toursRecommendation($tourIds);
                    if ($tours->isNotEmpty()) {
                        return $tours;
                    }
                }
            }
        } catch (Exception $e) {
            Log::info('Python API failed for getUserRecommendations, using fallback: ' . $e->getMessage());
        }

        // Fallback: Use last booked tour
        $lastBooking = DB::table('tbl_booking')
            ->where('userId', $userId)
            ->whereNotNull('tourId')
            ->orderBy('bookingDate', 'DESC')
            ->first();

        if (!$lastBooking) {
            return $this->getTrendingTours();
        }

        $lastTour = DB::table('tbl_tours')
            ->where('tourId', $lastBooking->tourId)
            ->first();

        if (!$lastTour) {
            return $this->getTrendingTours();
        }

        $recommendedTours = DB::table('tbl_tours')
            ->where('tourId', '!=', $lastBooking->tourId)
            ->where('availability', 1)
            ->where(function ($query) use ($lastTour) {
                if (!empty($lastTour->destination)) {
                    $destKeywords = explode(' ', $lastTour->destination);
                    foreach ($destKeywords as $keyword) {
                        if (strlen($keyword) > 2) {
                            $query->orWhere('destination', 'LIKE', '%' . $keyword . '%');
                        }
                    }
                }
                if (!empty($lastTour->domain)) {
                    $query->orWhere('domain', $lastTour->domain);
                }
            })
            ->limit(6)
            ->get();

        foreach ($recommendedTours as $tour) {
            $tour->images = DB::table('tbl_images')
                ->where('tourId', $tour->tourId)
                ->pluck('imageUrl');
            $tour->rating = $this->toursModel->reviewStats($tour->tourId)->averageRating;
        }

        return $recommendedTours;
    }
}
