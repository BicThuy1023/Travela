<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\clients\SearchController;
use App\Http\Controllers\clients\BuildTourController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API JS tìm tour
Route::get('/search-tours-js', [SearchController::class, 'searchToursAjax'])
    ->name('api.search.tours.js');

Route::get('/destinations', [BuildTourController::class, 'searchDestinations'])
    ->name('api.destinations');

// ================== AI Chatbot Routes ==================
use App\Http\Controllers\AIController;

Route::prefix('ai')->group(function () {
    // Public routes
    Route::post('/chat', [AIController::class, 'chat']);
    Route::get('/popular', [AIController::class, 'getPopularRooms']);
    Route::get('/trending', [AIController::class, 'getTrendingDestinations']);
    
    // Protected routes (require authentication via session)
    // Note: Có thể cần điều chỉnh middleware tùy theo cách auth của project
    Route::get('/recommendations', [AIController::class, 'getRecommendations']);
    Route::get('/personalized-recommendations', [AIController::class, 'getPersonalizedRecommendations']);
});
