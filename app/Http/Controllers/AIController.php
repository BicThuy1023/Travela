<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\clients\Tours;
use App\Models\clients\Booking;

class AIController extends Controller
{
    /**
     * Chatbot - Get AI response with OpenAI GPT
     * POST /api/ai/chat
     */
    public function chat(Request $request)
    {
        try {
            $message = $request->input('message');
            $context = $request->input('context', []);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng cung cấp tin nhắn'
                ], 400);
            }

            // Kiểm tra OpenAI API key
            $openaiApiKey = env('OPENAI_API_KEY');
            $openaiModel = env('OPENAI_MODEL', 'gpt-4o-mini');

            if (!$openaiApiKey) {
                // Fallback: trả lời theo keyword
                return $this->fallbackResponse($message);
            }

            // System prompt cho trợ lý đặt tour
            $systemPrompt = <<<'PROMPT'
Bạn là trợ lý ảo thông minh của hệ thống đặt tour du lịch với khả năng tìm kiếm và đặt tour thực tế.

🎯 Nhiệm vụ của bạn:
- Tìm kiếm tour phù hợp với yêu cầu của khách (sử dụng function searchTours)
- Hiển thị chi tiết tour với hình ảnh và link (sử dụng function getTourDetails)
- Hỗ trợ đặt tour trực tiếp (sử dụng function createBookingLink)
- Tư vấn về giá tour, điểm đến, chính sách
- Giải đáp thắc mắc và hỗ trợ thanh toán

📋 Quy trình tư vấn:
1. Khi khách hỏi về tour → Hỏi chi tiết: điểm đến, ngày đi, số người, giá
2. Khi có đủ thông tin → Gọi searchTours để tìm tour thực tế
3. Khi khách quan tâm tour cụ thể → Gọi getTourDetails để xem chi tiết
4. Khi khách muốn đặt → Gọi createBookingLink để tạo link đặt tour

💡 Phong cách:
- Thân thiện, nhiệt tình, chuyên nghiệp
- Chủ động hỏi thông tin cần thiết để tìm tour
- Sử dụng emoji phù hợp 🏖️✨
- Luôn đưa ra gợi ý cụ thể với link và hình ảnh

⚠️ Lưu ý:
- Khi tìm được tour, LUÔN show chi tiết với hình ảnh và link
- Khi khách muốn đặt, tạo link đặt tour trực tiếp
- Không đưa ra thông tin sai lệch
- Nếu không tìm được tour, gợi ý lựa chọn khác
PROMPT;

            // Build conversation history
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];

            // Thêm context nếu có
            if (is_array($context) && !empty($context)) {
                $messages = array_merge($messages, $context);
            }

            // Thêm tin nhắn của user
            $messages[] = ['role' => 'user', 'content' => $message];

            // Định nghĩa các functions cho AI
            $functions = [
                [
                    'name' => 'searchTours',
                    'description' => 'Tìm kiếm tour du lịch dựa trên tiêu chí của khách hàng',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'destination' => [
                                'type' => 'string',
                                'description' => 'Điểm đến cần tìm tour (Đà Nẵng, Hà Nội, Hồ Chí Minh, Nha Trang, etc.)'
                            ],
                            'minPrice' => [
                                'type' => 'number',
                                'description' => 'Giá tối thiểu (VNĐ)'
                            ],
                            'maxPrice' => [
                                'type' => 'number',
                                'description' => 'Giá tối đa (VNĐ)'
                            ],
                            'keyword' => [
                                'type' => 'string',
                                'description' => 'Từ khóa tìm kiếm (tên tour, mô tả)'
                            ]
                        ],
                        'required' => []
                    ]
                ],
                [
                    'name' => 'getTourDetails',
                    'description' => 'Lấy thông tin chi tiết của một tour cụ thể bao gồm hình ảnh, giá, điểm đến',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tourId' => [
                                'type' => 'string',
                                'description' => 'ID của tour cần xem chi tiết'
                            ]
                        ],
                        'required' => ['tourId']
                    ]
                ],
                [
                    'name' => 'createBookingLink',
                    'description' => 'Tạo link đặt tour trực tiếp cho khách hàng',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tourId' => [
                                'type' => 'string',
                                'description' => 'ID của tour cần đặt'
                            ]
                        ],
                        'required' => ['tourId']
                    ]
                ]
            ];

            // Gọi OpenAI API lần 1
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $openaiModel,
                'messages' => $messages,
                'functions' => $functions,
                'function_call' => 'auto',
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $completion = $response->json();
            $responseMessage = $completion['choices'][0]['message'];

            // Kiểm tra nếu AI muốn gọi function
            if (isset($responseMessage['function_call'])) {
                $functionName = $responseMessage['function_call']['name'];
                $functionArgs = json_decode($responseMessage['function_call']['arguments'], true);

                \Log::info("AI calling function: {$functionName}", $functionArgs);

                $functionResult = null;

                // Thực thi function được yêu cầu
                if ($functionName === 'searchTours') {
                    $functionResult = $this->executeSearchTours($functionArgs);
                } elseif ($functionName === 'getTourDetails') {
                    $functionResult = $this->executeGetTourDetails($functionArgs);
                } elseif ($functionName === 'createBookingLink') {
                    $functionResult = $this->executeCreateBookingLink($functionArgs);
                }

                // Thêm function call và result vào conversation
                $messages[] = $responseMessage;
                $messages[] = [
                    'role' => 'function',
                    'name' => $functionName,
                    'content' => json_encode($functionResult)
                ];

                // Gọi OpenAI lần 2 để lấy câu trả lời cuối
                $secondResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $openaiApiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $openaiModel,
                    'messages' => $messages,
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                ]);

                if (!$secondResponse->successful()) {
                    throw new \Exception('OpenAI API error: ' . $secondResponse->body());
                }

                $secondCompletion = $secondResponse->json();
                $aiResponse = $secondCompletion['choices'][0]['message']['content'];

                return response()->json([
                    'success' => true,
                    'data' => [
                        'response' => $aiResponse,
                        'functionCalled' => $functionName,
                        'functionResult' => $functionResult,
                        'timestamp' => now()->toISOString(),
                        'source' => 'openai',
                        'model' => $openaiModel
                    ]
                ]);
            } else {
                // Không có function call, chỉ trả về câu trả lời của AI
                $aiResponse = $responseMessage['content'];

                return response()->json([
                    'success' => true,
                    'data' => [
                        'response' => $aiResponse,
                        'timestamp' => now()->toISOString(),
                        'source' => 'openai',
                        'model' => $openaiModel
                    ]
                ]);
            }
        } catch (\Exception $error) {
            \Log::error('Chatbot error:', ['error' => $error->getMessage()]);

            // Fallback response khi có lỗi
            return response()->json([
                'success' => true,
                'data' => [
                    'response' => 'Xin lỗi, tôi đang gặp sự cố kỹ thuật. Vui lòng thử lại sau hoặc liên hệ bộ phận hỗ trợ: support@travela.com',
                    'timestamp' => now()->toISOString(),
                    'source' => 'error_fallback'
                ]
            ]);
        }
    }

    /**
     * Fallback response khi không có OpenAI API key
     */
    private function fallbackResponse(string $message): \Illuminate\Http\JsonResponse
    {
        $lowerMessage = mb_strtolower($message);

        if (mb_strpos($lowerMessage, 'đặt tour') !== false || mb_strpos($lowerMessage, 'booking') !== false) {
            $response = 'Để đặt tour, bạn có thể tìm kiếm tour phù hợp, sau đó nhấn nút "Đặt ngay". Bạn cần đăng nhập để hoàn tất đặt tour.';
        } elseif (mb_strpos($lowerMessage, 'thanh toán') !== false || mb_strpos($lowerMessage, 'payment') !== false) {
            $response = 'Chúng tôi hỗ trợ thanh toán qua VNPay và thanh toán tại văn phòng. Sau khi đặt tour, bạn sẽ được chuyển đến trang thanh toán an toàn.';
        } elseif (mb_strpos($lowerMessage, 'hủy') !== false || mb_strpos($lowerMessage, 'cancel') !== false) {
            $response = 'Bạn có thể hủy đặt tour trong mục "Tour đã đặt của tôi". Lưu ý: Không thể hủy trong vòng 3 ngày trước ngày khởi hành.';
        } elseif (mb_strpos($lowerMessage, 'giá') !== false || mb_strpos($lowerMessage, 'price') !== false) {
            $response = 'Giá tour phụ thuộc vào điểm đến, thời gian và số lượng người. Bạn có thể sử dụng bộ lọc để tìm tour theo mức giá phù hợp.';
        } else {
            $response = 'Xin chào! Tôi là trợ lý ảo của hệ thống đặt tour du lịch. Tôi có thể giúp bạn về: đặt tour, thanh toán, hủy đặt tour, và thông tin giá cả. Bạn cần hỗ trợ gì?';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response' => $response,
                'timestamp' => now()->toISOString(),
                'source' => 'fallback'
            ]
        ]);
    }

    /**
     * Helper: Tìm kiếm tour
     */
    private function executeSearchTours(array $params): array
    {
        try {
            $destination = $params['destination'] ?? null;
            $minPrice = $params['minPrice'] ?? null;
            $maxPrice = $params['maxPrice'] ?? null;
            $keyword = $params['keyword'] ?? null;

            $query = DB::table('tbl_tours')
                ->where('availability', 1);

            // Filter theo destination
            if ($destination) {
                $query->where('destination', 'LIKE', '%' . $destination . '%');
            }

            // Filter theo keyword
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('description', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('destination', 'LIKE', '%' . $keyword . '%');
                });
            }

            // Filter theo giá (dùng priceAdult làm giá chính)
            if ($minPrice || $maxPrice) {
                if ($minPrice) {
                    $query->where('priceAdult', '>=', $minPrice);
                }
                if ($maxPrice) {
                    $query->where('priceAdult', '<=', $maxPrice);
                }
            }

            // Lấy tour và sort theo rating
            $tours = $query->orderByDesc('tourId')
                ->limit(5)
                ->get();

            // Lấy thêm thông tin cho mỗi tour
            $toursModel = new Tours();
            $result = [];

            foreach ($tours as $tour) {
                // Lấy hình ảnh
                $images = DB::table('tbl_images')
                    ->where('tourId', $tour->tourId)
                    ->pluck('imageUrl')
                    ->toArray();

                // Lấy rating
                $reviewStats = $toursModel->reviewStats($tour->tourId);
                $rating = $reviewStats ? (float) $reviewStats->averageRating : 0;

                $result[] = [
                    'id' => $tour->tourId,
                    'name' => $tour->title,
                    'destination' => $tour->destination,
                    'price' => (int) $tour->priceAdult,
                    'priceChild' => (int) ($tour->priceChild ?? 0),
                    'image' => !empty($images) ? $images[0] : null,
                    'images' => $images,
                    'rating' => $rating,
                    'time' => $tour->time ?? '',
                    'description' => mb_substr($tour->description ?? '', 0, 150) . '...',
                    'link' => route('tour-detail', ['id' => $tour->tourId])
                ];
            }

            return [
                'success' => true,
                'count' => count($result),
                'tours' => $result
            ];
        } catch (\Exception $error) {
            \Log::error('Search tours error:', ['error' => $error->getMessage()]);
            return [
                'success' => false,
                'message' => 'Không thể tìm kiếm tour lúc này'
            ];
        }
    }

    /**
     * Helper: Lấy chi tiết tour
     */
    private function executeGetTourDetails(array $params): array
    {
        try {
            $tourId = $params['tourId'] ?? null;

            if (!$tourId) {
                return [
                    'success' => false,
                    'message' => 'Thiếu tour ID'
                ];
            }

            $tour = DB::table('tbl_tours')
                ->where('tourId', $tourId)
                ->where('availability', 1)
                ->first();

            if (!$tour) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy tour'
                ];
            }

            // Lấy hình ảnh
            $images = DB::table('tbl_images')
                ->where('tourId', $tour->tourId)
                ->pluck('imageUrl')
                ->toArray();

            // Lấy rating và số lượng review
            $toursModel = new Tours();
            $reviewStats = $toursModel->reviewStats($tour->tourId);
            $rating = $reviewStats ? (float) $reviewStats->averageRating : 0;
            $totalReviews = $reviewStats ? (int) $reviewStats->reviewCount : 0;

            // Lấy timeline
            $timeline = DB::table('tbl_timeline')
                ->where('tourId', $tour->tourId)
                ->get()
                ->map(function ($item) {
                    return [
                        'day' => $item->day ?? '',
                        'title' => $item->title ?? '',
                        'description' => $item->description ?? ''
                    ];
                })
                ->toArray();

            return [
                'success' => true,
                'tour' => [
                    'id' => $tour->tourId,
                    'name' => $tour->title,
                    'description' => $tour->description ?? '',
                    'destination' => $tour->destination ?? '',
                    'price' => (int) $tour->priceAdult,
                    'priceChild' => (int) ($tour->priceChild ?? 0),
                    'images' => $images,
                    'time' => $tour->time ?? '',
                    'rating' => $rating,
                    'totalReviews' => $totalReviews,
                    'timeline' => $timeline,
                    'link' => route('tour-detail', ['id' => $tour->tourId]),
                    'bookingLink' => route('booking', ['id' => $tour->tourId])
                ]
            ];
        } catch (\Exception $error) {
            \Log::error('Get tour details error:', ['error' => $error->getMessage()]);
            return [
                'success' => false,
                'message' => 'Không thể lấy thông tin tour'
            ];
        }
    }

    /**
     * Helper: Tạo link đặt tour
     */
    private function executeCreateBookingLink(array $params): array
    {
        try {
            $tourId = $params['tourId'] ?? null;

            if (!$tourId) {
                return [
                    'success' => false,
                    'message' => 'Thiếu tour ID'
                ];
            }

            $tour = DB::table('tbl_tours')
                ->where('tourId', $tourId)
                ->where('availability', 1)
                ->first();

            if (!$tour) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy tour'
                ];
            }

            return [
                'success' => true,
                'bookingLink' => route('booking', ['id' => $tourId]),
                'tourName' => $tour->title,
                'destination' => $tour->destination ?? '',
                'price' => (int) $tour->priceAdult
            ];
        } catch (\Exception $error) {
            \Log::error('Create booking link error:', ['error' => $error->getMessage()]);
            return [
                'success' => false,
                'message' => 'Không thể tạo link đặt tour'
            ];
        }
    }

    /**
     * Get popular tours
     * GET /api/ai/popular
     */
    public function getPopularRooms(Request $request)
    {
        try {
            $limit = (int) ($request->query('limit', 10));

            $toursModel = new Tours();
            $popularTours = $toursModel->toursPopular($limit);

            return response()->json([
                'success' => true,
                'count' => $popularTours->count(),
                'data' => $popularTours
            ]);
        } catch (\Exception $error) {
            \Log::error('Get popular tours error:', ['error' => $error->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * Get trending destinations
     * GET /api/ai/trending
     */
    public function getTrendingDestinations(Request $request)
    {
        try {
            $limit = (int) ($request->query('limit', 5));

            // Lấy các điểm đến có nhiều booking nhất trong 3 tháng gần đây
            $trendingDestinations = DB::table('tbl_booking')
                ->join('tbl_tours', 'tbl_booking.tourId', '=', 'tbl_tours.tourId')
                ->where('tbl_booking.bookingStatus', 'f') // Đã hoàn thành
                ->where('tbl_booking.created_at', '>=', now()->subMonths(3))
                ->select(
                    'tbl_tours.destination',
                    DB::raw('COUNT(*) as bookings'),
                    DB::raw('AVG(tbl_booking.totalPrice) as averagePrice')
                )
                ->groupBy('tbl_tours.destination')
                ->orderByDesc('bookings')
                ->limit($limit)
                ->get();

            // Fallback: Nếu không có booking, lấy điểm đến có nhiều tour nhất
            if ($trendingDestinations->isEmpty()) {
                $trendingDestinations = DB::table('tbl_tours')
                    ->where('availability', 1)
                    ->select(
                        'destination',
                        DB::raw('COUNT(*) as bookings'),
                        DB::raw('AVG(priceAdult) as averagePrice')
                    )
                    ->groupBy('destination')
                    ->orderByDesc('bookings')
                    ->limit($limit)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'count' => $trendingDestinations->count(),
                'data' => $trendingDestinations
            ]);
        } catch (\Exception $error) {
            \Log::error('Get trending destinations error:', ['error' => $error->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * Get recommendations for user
     * GET /api/ai/recommendations
     */
    public function getRecommendations(Request $request)
    {
        try {
            // Lấy userId từ session (project dùng session-based auth)
            $userId = $request->session()->get('userId');
            
            // Nếu không có userId, thử lấy từ username
            if (!$userId && $request->session()->has('username')) {
                $username = $request->session()->get('username');
                $userModel = new \App\Models\clients\User();
                $userId = $userModel->getUserId($username);
            }

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để xem gợi ý'
                ], 401);
            }

            // Lấy lịch sử booking của user
            $userBookings = DB::table('tbl_booking')
                ->where('userId', $userId)
                ->where('bookingStatus', 'f')
                ->get();

            // TODO: Phân tích preferences và gợi ý tour tương tự
            // Tạm thời trả về tour phổ biến
            $toursModel = new Tours();
            $recommendedTours = $toursModel->toursPopular(10);

            return response()->json([
                'success' => true,
                'count' => $recommendedTours->count(),
                'data' => $recommendedTours
            ]);
        } catch (\Exception $error) {
            \Log::error('Get recommendations error:', ['error' => $error->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * Get personalized recommendations
     * GET /api/ai/personalized-recommendations
     */
    public function getPersonalizedRecommendations(Request $request)
    {
        try {
            // Lấy userId từ session (project dùng session-based auth)
            $userId = $request->session()->get('userId');
            
            // Nếu không có userId, thử lấy từ username
            if (!$userId && $request->session()->has('username')) {
                $username = $request->session()->get('username');
                $userModel = new \App\Models\clients\User();
                $userId = $userModel->getUserId($username);
            }

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để xem gợi ý cá nhân hóa'
                ], 401);
            }

            // Tạm thời trả về tour phổ biến
            $toursModel = new Tours();
            $recommendedTours = $toursModel->toursPopular(6);

            return response()->json([
                'success' => true,
                'message' => 'Gợi ý tour dựa trên sở thích của bạn',
                'isPersonalized' => false,
                'data' => $recommendedTours
            ]);
        } catch (\Exception $error) {
            \Log::error('Get personalized recommendations error:', ['error' => $error->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
}

