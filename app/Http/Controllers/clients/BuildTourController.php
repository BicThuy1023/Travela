<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\clients\User;
use App\Services\MealService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class BuildTourController extends Controller
{
    /**
     * STEP 1 + 2: Hiển thị form thiết kế tour
     */
    public function showForm()
    {
        $title = 'Thiết kế Tour theo yêu cầu';

        return view('clients.build_tour', compact('title'));
    }

    /**
     * STEP 3 (POST): Nhận dữ liệu form, lưu yêu cầu và sinh tour theo yêu cầu
     */
    public function submit(Request $request)
{
    // 1. VALIDATE (nới lỏng để không bị văng về Step 1)
    $validated = $request->validate([
        'main_destinations'     => 'required|string',   // JSON string
        'must_visit_places'     => 'nullable|array',
        'must_visit_places.*'   => 'string',
        'start_date'            => 'required|date',
        'end_date'              => 'required|date|after_or_equal:start_date',
        'budget_per_person'     => 'required|integer|min:500000',
        'adults'                => 'required|integer|min:1',
        'children'              => 'nullable|integer|min:0',
        'hotel_level'           => 'nullable|string',
        // 'tour_type'          => 'nullable|string',
        'intensity'             => 'nullable|string',
        'interests'             => 'nullable|array',
        'interests.*'           => 'string',
        'note'                  => 'nullable|string',
        'days'                  => 'sometimes|nullable|integer|min:1',
        'nights'                => 'sometimes|nullable|integer|min:0',
    ]);

    // 1.1. KIỂM TRA NGÀY KHỞI HÀNH PHẢI CÁCH HÔM NAY ÍT NHẤT 3 NGÀY
    $startDate = Carbon::parse($validated['start_date']);
    $minStartDate = Carbon::now()->addDays(3)->startOfDay();
    
    if ($startDate->lt($minStartDate)) {
        return back()->withInput()->with('error', 'Ngày khởi hành dự kiến phải cách hôm nay ít nhất 3 ngày. Vui lòng chọn ngày khác.');
    }

    // 2. PARSE main_destinations
    $mainDestinations = json_decode($validated['main_destinations'], true);
    if (!is_array($mainDestinations) || count($mainDestinations) < 1) {
        return back()->withInput()->with('error', 'Vui lòng chọn ít nhất 1 điểm đến chính.');
    }

    // 3. TÍNH NGÀY / ĐÊM
    $start    = new \DateTime($validated['start_date']);
    $end      = new \DateTime($validated['end_date']);
    $interval = $start->diff($end);

    $days   = ($interval->days ?? 0) + 1;
    $nights = max($days - 1, 0);

    $daysFinal   = $validated['days']   ?? $days;
    $nightsFinal = $validated['nights'] ?? $nights;

    // 4. TỰ ĐỘNG XÁC ĐỊNH LOẠI TOUR THEO SỐ LƯỢNG KHÁCH
    $adults      = (int) $validated['adults'];
    $children    = (int) ($validated['children'] ?? 0);
    $totalPeople = max($adults + $children, 1);

    /*
     * Quy ước mới cho tour tự thiết kế:
     *  - 1 khách  : tour cá nhân (private) → hệ số riêng
     *  - >= 2 khách : tour đoàn (group)    → được áp dụng khuyến mãi theo số lượng
     *   (match với hàm calculateGroupDiscountFactor)
     */
    if ($totalPeople === 1) {
        $normalizedTourType = 'private';
    } else {
        $normalizedTourType = 'group';
    }

    // 5. GOM DATA
    $requestData = [
        'main_destinations'   => $mainDestinations,
        'must_visit_places'   => $validated['must_visit_places'] ?? [],
        'start_date'          => $validated['start_date'],
        'end_date'            => $validated['end_date'],
        'days'                => $daysFinal,
        'nights'              => $nightsFinal,
        'budget_per_person'   => $validated['budget_per_person'],
        'adults'              => $adults,
        'children'            => $children,

        // nếu không chọn → "Chưa biết"
        'hotel_level'         => $validated['hotel_level'] ?? 'Chưa biết',

        // luôn chỉ 'group' hoặc 'private' (tự động)
        'tour_type'           => $normalizedTourType,

        // nếu không gửi → Vừa
        'intensity'           => $validated['intensity'] ?? 'Vừa',

        'interests'           => $validated['interests'] ?? [],
        'note'                => $validated['note'] ?? '',
    ];

    // 6. Thông tin khách (sau này login)
    $customerName  = null;
    $customerPhone = null;
    $customerEmail = null;

    // 7. MÃ YÊU CẦU
    $requestCode = 'BT' . now()->format('YmdHis');

    // 8. LƯU vào tbl_custom_tour_requests
    DB::table('tbl_custom_tour_requests')->insert([
        'request_code'      => $requestCode,
        'user_id'           => null,

        'customer_name'     => $customerName,
        'customer_phone'    => $customerPhone,
        'customer_email'    => $customerEmail,

        'main_destinations' => json_encode($requestData['main_destinations'], JSON_UNESCAPED_UNICODE),
        'must_visit_places' => json_encode($requestData['must_visit_places'], JSON_UNESCAPED_UNICODE),
        'start_date'        => $requestData['start_date'],
        'end_date'          => $requestData['end_date'],
        'days'              => $requestData['days'],
        'nights'            => $requestData['nights'],
        'budget_per_person' => $requestData['budget_per_person'],
        'adults'            => $requestData['adults'],
        'children'          => $requestData['children'],
        'hotel_level'       => $requestData['hotel_level'],
        'tour_type'         => $requestData['tour_type'],
        'intensity'         => $requestData['intensity'],
        'interests'         => json_encode($requestData['interests'], JSON_UNESCAPED_UNICODE),
        'note'              => $requestData['note'],

        'status'            => 'pending',
        'admin_note'        => null,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    // 9. SINH TOUR ẢO (đã tự áp dụng khuyến mãi đoàn + breakdown giá ở các hàm generateTourOptions)
    $generatedTours = $this->generateTourOptions($requestData, $requestCode);

    // 10. LƯU SESSION để load lại Step 3 khi login
    session([
        'build_tour.requestData'    => $requestData,
        'build_tour.requestCode'    => $requestCode,
        'build_tour.generatedTours' => $generatedTours,
    ]);

    // 11. TRẢ VỀ VIEW KẾT QUẢ
    return view('clients.build_tour_result', [
        'title'                  => 'Thiết kế Tour theo yêu cầu',
        'requestData'            => $requestData,
        'requestCode'            => $requestCode,
        'generatedTours'         => $generatedTours,
        'build_tour_last_request'=> $requestData,
    ]);
}

    /**
     * STEP 3 (GET): Hiển thị lại kết quả từ SESSION
     */
    public function showResult(Request $request)
    {
        $requestData    = $request->session()->get('build_tour.requestData');
        $requestCode    = $request->session()->get('build_tour.requestCode');
        $generatedTours = $request->session()->get('build_tour.generatedTours');

        if (!$requestData || !$generatedTours) {
            return redirect()->route('build-tour.form')
                ->with('error', 'Vui lòng nhập yêu cầu trước.');
        }

        return view('clients.build_tour_result', [
            'title'         => 'Thiết kế Tour theo yêu cầu',
            'requestData'   => $requestData,
            'requestCode'   => $requestCode,
            'generatedTours'=> $generatedTours,
        ]);
    }
    /**
 * Xem chi tiết 1 phương án tour: lịch trình + cách tính giá
 */
public function showOptionDetail($index, Request $request)
{
    // Lấy lại dữ liệu từ session
    $requestData    = $request->session()->get('build_tour.requestData');
    $requestCode    = $request->session()->get('build_tour.requestCode');
    $generatedTours = $request->session()->get('build_tour.generatedTours');

    if (!$requestData || !$generatedTours) {
        return redirect()->route('build-tour.form')
            ->with('error', 'Phiên làm việc đã hết hạn, vui lòng thiết kế tour lại.');
    }

    // LUÔN tìm option theo option_index để đảm bảo đúng phương án được chọn
    // Không dựa vào array index vì có thể bị sắp xếp lại hoặc không khớp
    $option = null;
    foreach ($generatedTours as $tour) {
        if (isset($tour['option_index']) && (int)$tour['option_index'] === (int)$index) {
            $option = $tour;
            break;
        }
    }

    // Nếu không tìm thấy option theo option_index, fallback về array index (để tương thích ngược)
    if (!$option) {
        $arrayIndex = (int)$index - 1;
        if (isset($generatedTours[$arrayIndex])) {
            $option = $generatedTours[$arrayIndex];
        }
    }

    if (!$option) {
        return redirect()->route('build-tour.result')
            ->with('error', 'Phương án tour không tồn tại. Vui lòng chọn lại.');
    }

    // Lấy thêm 1 số thông tin tiện cho view
    $totalPeople = max(($requestData['adults'] ?? 0) + ($requestData['children'] ?? 0), 1);
    $tourType    = $option['tour_type'] ?? ($requestData['tour_type'] ?? 'group');
    $tourTypeLabel = $tourType === 'private' ? 'Tour cá nhân' : 'Tour đoàn';

    $discountPercent = (int)($option['group_discount_percent'] ?? 0);

    // Đảm bảo optionIndex khớp với option_index trong data
    $optionIndex = isset($option['option_index']) ? (int)$option['option_index'] : (int)$index;

    return view('clients.build_tour_option_detail', [
        'title'           => 'Chi tiết phương án tour',
        'requestData'     => $requestData,
        'requestCode'     => $requestCode,
        'option'          => $option,
        'totalPeople'     => $totalPeople,
        'tourType'        => $tourType,
        'tourTypeLabel'   => $tourTypeLabel,
        'discountPercent' => $discountPercent,
        'optionIndex'     => $optionIndex,
    ]);
}

    /**
     * Khi khách bấm "Chọn tour này"
     */
    public function chooseTour($index, Request $request)
{
    // 🔥 KIỂM TRA LOGIN THEO SESSION CỦA BẠN (userId / username)
    if (!$request->session()->has('userId') && !$request->session()->has('username')) {

        // Lưu URL muốn quay lại sau khi login
        $request->session()->put('url.intended', route('build-tour.result'));

        return redirect()
            ->route('login')
            ->with('info', 'Vui lòng đăng nhập để tiếp tục.');
    }

    // Đã đăng nhập
    $generatedTours = $request->session()->get('build_tour.generatedTours');
    $requestData    = $request->session()->get('build_tour.requestData');
    $requestCode    = $request->session()->get('build_tour.requestCode');

    if (!is_array($generatedTours) || !$requestData) {
        return redirect()
            ->route('build-tour.result')
            ->with('error', 'Tour bạn chọn không tồn tại hoặc phiên làm việc đã hết hạn.');
    }

    // LUÔN tìm option theo option_index để đảm bảo đúng phương án được chọn
    // Không dựa vào array index vì có thể bị sắp xếp lại hoặc không khớp
    $chosenTour = null;
    foreach ($generatedTours as $tour) {
        if (isset($tour['option_index']) && (int)$tour['option_index'] === (int)$index) {
            $chosenTour = $tour;
            break;
        }
    }
    
    // Nếu không tìm thấy option theo option_index, fallback về array index (để tương thích ngược)
    if (!$chosenTour) {
        $arrayIndex = (int)$index - 1;
        if (isset($generatedTours[$arrayIndex])) {
            $chosenTour = $generatedTours[$arrayIndex];
        }
    }

    if (!$chosenTour) {
        return redirect()
            ->route('build-tour.result')
            ->with('error', 'Tour bạn chọn không tồn tại hoặc phiên làm việc đã hết hạn.');
    }

    $userId      = $request->session()->get('userId');
    $adults      = $requestData['adults'] ?? 1;
    $children    = $requestData['children'] ?? 0;
    $totalPeople = $adults + $children;

    // 2a. Lấy giá optional activities từ request (nếu có)
    $optionalActivitiesTotal = (int) ($request->input('optional_activities_total', 0));
    $finalTotalPriceFromForm = (int) ($request->input('final_total_price', 0));

    // 2b. Đồng bộ lại tổng tiền từ breakdown để tránh lệch
    // Đảm bảo price_breakdown luôn có đầy đủ giá trị và đồng bộ với total_price
    if (isset($chosenTour['price_breakdown']['final_total_price'])) {
        $chosenTour['total_price'] = $chosenTour['price_breakdown']['final_total_price'];
        // Đồng bộ lại các giá trị khác từ breakdown để đảm bảo nhất quán
        if (isset($chosenTour['price_breakdown']['adult_price'])) {
            $chosenTour['price_per_adult'] = $chosenTour['price_breakdown']['adult_price'];
        }
        if (isset($chosenTour['price_breakdown']['child_price'])) {
            $chosenTour['price_per_child'] = $chosenTour['price_breakdown']['child_price'];
        }
        if (isset($chosenTour['price_breakdown']['total_price_adults'])) {
            $chosenTour['total_price_adults'] = $chosenTour['price_breakdown']['total_price_adults'];
        }
        if (isset($chosenTour['price_breakdown']['total_price_children'])) {
            $chosenTour['total_price_children'] = $chosenTour['price_breakdown']['total_price_children'];
        }
    }
    
    // 2c. Tính tổng giá cuối cùng: giá tour gốc + optional activities
    $baseTourPrice = $chosenTour['total_price'] ?? 0;
    $finalTotalPrice = $baseTourPrice + $optionalActivitiesTotal;
    
    // Nếu form gửi final_total_price và khác với tính toán, ưu tiên giá từ form
    if ($finalTotalPriceFromForm > 0 && abs($finalTotalPriceFromForm - $finalTotalPrice) < 1000) {
        $finalTotalPrice = $finalTotalPriceFromForm;
    }
    
    // Lưu thông tin optional activities vào chosenTour để hiển thị sau
    if ($optionalActivitiesTotal > 0) {
        $chosenTour['optional_activities_total'] = $optionalActivitiesTotal;
        // Cập nhật price_breakdown để bao gồm optional
        if (isset($chosenTour['price_breakdown'])) {
            $chosenTour['price_breakdown']['optional_activities_total'] = $optionalActivitiesTotal;
            $chosenTour['price_breakdown']['final_total_price'] = $finalTotalPrice;
        }
    }

    // 3. Lưu option đã chọn vào tbl_custom_tours
    // Lấy ngày khởi hành từ requestData (step 1)
    $startDate = $requestData['start_date'] ?? null;
    $endDate   = null;

    if ($startDate && !empty($chosenTour['days'])) {
        $endDate = Carbon::parse($startDate)
            ->addDays($chosenTour['days'] - 1)
            ->format('Y-m-d');
    }

    $customTourId = DB::table('tbl_custom_tours')->insertGetId([
        'user_id'       => $userId,
        'request_code'  => $requestCode,
        'option_code'   => $chosenTour['code'] ?? null,
        'destination'   => implode(' – ', $requestData['main_destinations'] ?? []),
        'days'          => $chosenTour['days'] ?? 0,
        'nights'        => $chosenTour['nights'] ?? 0,
        'hotel_level'   => $chosenTour['hotel_level'] ?? ($requestData['hotel_level'] ?? ''),
        'intensity'     => $chosenTour['intensity'] ?? ($requestData['intensity'] ?? ''),
        'total_people'  => $totalPeople,
        'adults'        => $adults,
        'children'      => $children,
        'tour_type'     => $chosenTour['tour_type'] ?? ($requestData['tour_type'] ?? 'group'),

        // 🔹 LƯU NGÀY ĐI / NGÀY VỀ
        'start_date'    => $startDate,
        'end_date'      => $endDate,

        // 🔹 LƯU FULL JSON PHƯƠNG ÁN
        'option_json'   => json_encode($chosenTour, JSON_UNESCAPED_UNICODE),

        // 🔹 GIÁ TRONG DB = GIÁ HỆ THỐNG TÍNH
        'estimated_cost'=> $finalTotalPrice,

        'status'        => 'pending',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    return redirect()
        ->route('custom-tours.checkout', ['id' => $customTourId])
        ->with('success', 'Bạn đã chọn tour: ' . ($chosenTour['title'] ?? $chosenTour['code']));
}

/**
 * Sinh phương án tour “ảo” từ yêu cầu (dùng dữ liệu tbl_places)
 */

protected function generateTourOptions(array $requestData, string $requestCode): array
{
    $days          = $requestData['days'];
    $nights        = $requestData['nights'];
    $destStr       = implode(' – ', $requestData['main_destinations']);
    $main          = $requestData['main_destinations'][0] ?? 'Hành trình';
    $must          = $requestData['must_visit_places'];
    $adults        = (int) ($requestData['adults'] ?? 1);
    $children      = (int) ($requestData['children'] ?? 0);
    $totalPeople   = max($adults + $children, 1);
    $baseBudget    = $requestData['budget_per_person'];
    $hotelLevelRaw = $requestData['hotel_level'] ?? 'Chưa biết'; // Đảm bảo có giá trị mặc định
    $intensity     = $requestData['intensity'];
    $tourType      = $requestData['tour_type'] ?? 'group';   // 'group' / 'private'

    // Hệ số giá trẻ em (vd: 75% giá người lớn)
    $childFactor = 0.75;

    // ========== Hệ số theo SỐ ĐIỂM BẮT BUỘC ==========
    $mustCount = is_array($must) ? count($must) : 0;
    if ($mustCount <= 2) {
        $placeFactor = 0.9;   // chọn ít điểm → rẻ hơn ~10%
    } elseif ($mustCount <= 4) {
        $placeFactor = 1.0;   // 3–4 điểm → giữ nguyên
    } else {
        $placeFactor = 1.1;   // >=5 điểm → đắt hơn ~10%
    }

        // ================== 1. Lấy dữ liệu điểm tham quan từ tbl_places ==================
    $placesQuery = DB::table('tbl_places')
        ->select('id', 'name', 'destination', 'category', 'avgCost', 'durationHour');

    if (!empty($requestData['main_destinations'])) {
        $placesQuery->whereIn('destination', $requestData['main_destinations']);
    }

    if (!empty($must)) {
        $placesQuery->orWhereIn('name', $must);
    }

    $allPlaces = $placesQuery->orderBy('destination')->get();

    // Nếu chưa có dữ liệu place => fallback đơn giản
    if ($allPlaces->isEmpty()) {
        return $this->generateSimpleOptionsFallback($requestData, $requestCode);
    }

    // Nhóm theo điểm đến để chia theo ngày
    $placesByDestination = $allPlaces->groupBy('destination');

    // ================== 2. Ghép lịch trình + phân tách chi phí tham quan ==================
    $maxHoursPerDay = 8;
    $minHoursPerDay = 5;

    $unusedMust         = $must;          // mảng tên string
    $baseItinerary      = [];
    $mandatoryActCost   = 0;              // 💰 điểm tham quan chính (đã bao gồm trong giá tour)
    $usedPlaceIds       = [];
    $optionalActivities = [];             // danh sách hoạt động tự túc (để hiển thị)

    // Helper: xác định điểm tham quan là "hoạt động trải nghiệm tự túc"
    $isOptionalFn = function ($placeRow) {
        $cat  = mb_strtolower($placeRow->category ?? '');
        $cost = (int) $placeRow->avgCost;

        // Ví dụ: VinWonders, SunWorld, show... hoặc chi phí cao
        return $cost >= 600000
            || str_contains($cat, 'giải trí')
            || str_contains($cat, 'vui chơi')
            || str_contains($cat, 'show');
    };

    for ($d = 1; $d <= $days; $d++) {
        $dayLabel  = 'Ngày ' . $d;
        $dayPlaces = [];
        $dayHours  = 0;

        // Ưu tiên nhét các điểm "must visit" vào trước
        while (!empty($unusedMust) && $dayHours < $maxHoursPerDay) {
            $mustName = array_shift($unusedMust);

            $placeRow = $allPlaces->first(function ($p) use ($mustName) {
                return $p->name === $mustName;
            });

            // Nếu điểm must chưa có trong tbl_places => giả định 2h, 0đ
            if (!$placeRow) {
                $fakeDuration = 2;
                if ($dayHours + $fakeDuration > $maxHoursPerDay) {
                    array_unshift($unusedMust, $mustName);
                    break;
                }

                $dayPlaces[] = [
                    'name'         => $mustName,
                    'durationHour' => 2,
                    'avgCost'      => 0,
                    'is_optional'  => false,
                ];

                $dayHours += $fakeDuration;
                continue;
            }

            $duration = $placeRow->durationHour ?? 2;
            if ($dayHours + $duration > $maxHoursPerDay) {
                array_unshift($unusedMust, $mustName);
                break;
            }

            $cost = (int) $placeRow->avgCost;

            $dayPlaces[] = [
                'id'           => $placeRow->id,
                'name'         => $placeRow->name,
                'durationHour' => $duration,
                'avgCost'      => $cost,
                'is_optional'  => $isOptionalFn($placeRow),
            ];

            $dayHours      += $duration;
            $usedPlaceIds[] = $placeRow->id;

            if ($cost > 0) {
                if ($isOptionalFn($placeRow)) {
                    // Hoạt động tự túc: chỉ đưa vào danh sách optional, KHÔNG cộng vào mandatoryActCost
                    $optionalActivities[] = [
                        'id'               => $placeRow->id,
                        'label'            => $placeRow->name,
                        'note'             => 'Trải nghiệm/hoạt động tự chọn, chi phí tự túc.',
                        'price_per_person' => $cost,
                        'included'         => false,
                    ];
                } else {
                    // Điểm tham quan chính → cộng vào chi phí tour
                    $mandatoryActCost += $cost;
                }
            }
        }

        // Nếu còn thời gian thì thêm các điểm khác cùng destination
        $destForThisDay   = $requestData['main_destinations'][min($d - 1, count($requestData['main_destinations']) - 1)];
        $candidatePlaces  = $placesByDestination->get($destForThisDay, collect());

        foreach ($candidatePlaces as $placeRow) {
            if (in_array($placeRow->id, $usedPlaceIds)) {
                continue;
            }

            $duration = $placeRow->durationHour ?? 2;
            if ($dayHours + $duration > $maxHoursPerDay) {
                continue;
            }

            $cost = (int) $placeRow->avgCost;

            $dayPlaces[] = [
                'id'           => $placeRow->id,
                'name'         => $placeRow->name,
                'durationHour' => $duration,
                'avgCost'      => $cost,
                'is_optional'  => $isOptionalFn($placeRow),
            ];

            $dayHours      += $duration;
            $usedPlaceIds[] = $placeRow->id;

            if ($cost > 0) {
                if ($isOptionalFn($placeRow)) {
                    $optionalActivities[] = [
                        'id'               => $placeRow->id,
                        'label'            => $placeRow->name,
                        'note'             => 'Trải nghiệm/hoạt động tự chọn, chi phí tự túc.',
                        'price_per_person' => $cost,
                        'included'         => false,
                    ];
                } else {
                    $mandatoryActCost += $cost;
                }
            }

            if ($dayHours >= $minHoursPerDay) {
                break;
            }
        }

        $placeNames = array_map(
    fn($p) => $p['name'],
    array_filter($dayPlaces, fn($p) => empty($p['is_optional']))
);
// Tên điểm OPTIONAL để thêm vào mô tả
$optionalNames = array_map(
    fn($p) => $p['name'],
    array_filter($dayPlaces, fn($p) => !empty($p['is_optional']))
);

        // Mô tả ngày
if ($d == 1) {
    // Ngày đầu tiên
    $prefix = 'Buổi sáng, đoàn tập trung tại điểm hẹn, khởi hành đến ' . $main . '. '
        . 'Đến nơi, hướng dẫn viên hỗ trợ nhận phòng khách sạn ' . $hotelLevelRaw
        . ' và nghỉ ngơi ngắn trước khi bắt đầu chương trình tham quan. ';
} elseif ($d == $days) {
    // Ngày cuối cùng
    $prefix = 'Buổi sáng, quý khách tự do tham quan, mua sắm đặc sản địa phương. '
        . 'Đến giờ hẹn, đoàn làm thủ tục trả phòng, khởi hành về lại điểm xuất phát, kết thúc chương trình. ';
} else {
    // Các ngày ở giữa
    $prefix = 'Tiếp tục hành trình khám phá ' . $main . '. ';
}

$desc = $prefix;

if (!empty($placeNames)) {
    $placeNames = array_values($placeNames);
    // Chia buổi sáng / chiều / tối
    $morningPlaces   = [];
    $afternoonPlaces = [];
    $eveningPlaces   = [];

    if (count($placeNames) === 1) {
        $morningPlaces = [$placeNames[0]];
    } elseif (count($placeNames) === 2) {
        $morningPlaces   = [$placeNames[0]];
        $afternoonPlaces = [$placeNames[1]];
    } else {
        $morningPlaces   = [$placeNames[0]];
        $eveningPlaces   = [end($placeNames)];
        $afternoonPlaces = array_slice($placeNames, 1, -1);
    }

    if ($morningPlaces) {
        $desc .= 'Buổi sáng: đoàn tham quan ' . implode(', ', $morningPlaces)
            . ', lắng nghe thuyết minh và chụp hình lưu niệm. ';
    }
    if ($afternoonPlaces) {
        $desc .= 'Buổi chiều: tiếp tục khám phá ' . implode(', ', $afternoonPlaces)
            . ', trải nghiệm văn hoá địa phương và nghỉ ngơi thư giãn. ';
    }
    if ($eveningPlaces) {
        $desc .= 'Buổi tối: tự do dạo chơi, có thể ghé ' . implode(', ', $eveningPlaces)
            . ', thưởng thức ẩm thực đặc sản và ngắm cảnh về đêm. ';
    }
}

// Thêm câu mô tả theo cường độ
if ($intensity === 'Nhẹ') {
    $desc .= 'Lịch trình được sắp xếp nhẹ nhàng, phù hợp gia đình có trẻ nhỏ hoặc người lớn tuổi.';
} elseif ($intensity === 'Vừa') {
    $desc .= 'Lịch trình cân bằng giữa tham quan và nghỉ ngơi, giúp quý khách giữ sức khoẻ trong suốt hành trình.';
} elseif ($intensity === 'Dày') {
    $desc .= 'Lịch trình dày, đi được nhiều điểm trong ngày, phù hợp du khách thích khám phá và trải nghiệm. ';
} else {
    $desc .= 'Lịch trình được sắp xếp linh hoạt theo nhu cầu của đoàn. ';
}


        $baseItinerary[] = [
            'day'            => $dayLabel,
            'description'    => $desc,
            'places'         => $placeNames,
            'estimatedHours' => $dayHours,
        ];
    }

    // ================== THÊM NOTE CHI PHÍ TỰ TÚC VÀO NGÀY CUỐI ==================
    if (!empty($optionalActivities) && !empty($baseItinerary)) {
        // Lấy danh sách tên hoạt động optional (không trùng)
        $optionalNames = [];
        foreach ($optionalActivities as $opt) {
            $label = $opt['label'] ?? ($opt['name'] ?? null);
            if ($label && !in_array($label, $optionalNames, true)) {
                $optionalNames[] = $label;
            }
        }

        if (!empty($optionalNames)) {
            $note = ' Các trải nghiệm như ' . implode(', ', $optionalNames)
                  . ' là dịch vụ tự chọn, chi phí tự túc, không bao gồm trong giá tour.';

            // Gắn vào mô tả ngày cuối (thường là ngày có hoạt động này)
            $lastIndex = count($baseItinerary) - 1;
            $baseItinerary[$lastIndex]['description'] =
                rtrim($baseItinerary[$lastIndex]['description']) . $note;
        }
    }

    // Áp hệ số số lượng điểm bắt buộc
    $mandatoryActCost = (int) round($mandatoryActCost * $placeFactor / 1000) * 1000;


    // ================== 2.4. Nếu chưa có hoạt động tuỳ chọn, sinh 1–2 trải nghiệm tự túc ==================
    if (empty($optionalActivities)) {
        $mainLower = mb_strtolower($main);

        $optionalActivities[] = [
            'id'               => null,
            'label'            => 'Trải nghiệm đặc sắc tại điểm đến (chi phí tự túc)',
           // 'note'             => 'Chi phí tự túc, không bao gồm trong giá tour.',
            'price_per_person' => 150000,
            'included'         => false,
        ];

        if (str_contains($mainLower, 'đà lạt') || str_contains($mainLower, 'da lat')) {
            $optionalActivities[0]['label']            = 'Vé combo tham quan + cà phê view Đà Lạt';
            $optionalActivities[0]['note']             = 'Chi phí tự túc, áp dụng cho khách thích check-in & trải nghiệm cà phê view đẹp.';
            $optionalActivities[0]['price_per_person'] = 200000;
        } elseif (str_contains($mainLower, 'gia lai')) {
            $optionalActivities[0]['label']            = 'Trải nghiệm hồ T\'Nưng / Biển Hồ – cà phê phố núi';
            $optionalActivities[0]['note']             = 'Chi phí tự túc, bao gồm vé tham quan & 1 phần nước.';
            $optionalActivities[0]['price_per_person'] = 150000;
        } elseif (str_contains($mainLower, 'vũng tàu') || str_contains($mainLower, 'vung tau')) {
            $optionalActivities[0]['label']            = 'Tắm biển + trò chơi biển Vũng Tàu';
            $optionalActivities[0]['note']             = 'Chi phí tự túc cho các trò chơi cano, moto nước…';
            $optionalActivities[0]['price_per_person'] = 250000;
        } elseif (str_contains($mainLower, 'huế') || str_contains($mainLower, 'hue')) {
            $optionalActivities[0]['label']            = 'Tắm bùn I-Resort';
            $optionalActivities[0]['price_per_person'] = 250000;
        } elseif (str_contains($mainLower, 'nha trang')) {
            $optionalActivities[0]['label']            = 'Lặn ngắm san hô / tàu đáy kính Hòn Mun';
            $optionalActivities[0]['note']             = 'Chi phí tự túc, áp dụng cho khách thích trải nghiệm biển.';
            $optionalActivities[0]['price_per_person'] = 250000;
        } elseif (str_contains($mainLower, 'đà nẵng')) {
            $optionalActivities[0]['label']            = 'Cáp treo Bà Nà Hills';
            $optionalActivities[0]['note']             = 'Vé cáp treo + khu vui chơi, chi phí tự túc.';
            $optionalActivities[0]['price_per_person'] = 900000;
        } elseif (str_contains($mainLower, 'phú quốc')) {
            $optionalActivities[0]['label']            = 'Cáp treo Hòn Thơm / Công viên nước Aquatopia';
            $optionalActivities[0]['price_per_person'] = 950000;
        }
    }

    // ================== 2.5. Ước lượng ăn uống + di chuyển ==================
    // Sử dụng MealService để tính giá ăn uống mặc định dựa trên số bữa thực tế
    $mealService = new MealService();
    $foodTotal = $mealService->calculateDefaultFoodCost($hotelLevelRaw, $days, $adults, $children);
    
    // Tính foodCostPerPerson để hiển thị (chia cho tổng số người với hệ số trẻ em)
    $totalPeopleFactor = $adults + ($children * 0.7);
    $foodCostPerPerson = $totalPeopleFactor > 0 ? (int) round($foodTotal / $totalPeopleFactor / 1000) * 1000 : 0;

    // Di chuyển nội bộ (không bao gồm vé máy bay)
    $transportBaseDays      = max($days, 2);
    $transportCostPerPerson = 120000 + max(0, $transportBaseDays - 2) * 40000;

    // ================== 2.6. Phí dịch vụ & phụ thu cao điểm ==================
    // Phí dịch vụ / điều hành tour (coi như lợi nhuận, HDV, chi phí vận hành)
    $serviceFeeRate = ($baseBudget <= 2000000) ? 0.08 : 0.10;   

    // Phụ thu cuối tuần / Tết (nếu có ngày khởi hành)
    $highSeasonRate = 0.0;
    if (!empty($requestData['start_date'])) {
        try {
            $start = new \DateTime($requestData['start_date']);
            $dow   = (int) $start->format('N'); // 1=Mon ... 7=Sun

            // Thứ 7–CN: +2%
            if ($dow >= 6) {
                $highSeasonRate += 0.02;
            }

            $month = (int) $start->format('n');
            // T1–T2 (Tết): +2%
            if ($month === 1 || $month === 2) {
                $highSeasonRate += 0.05;
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    // ================== 3. Cấu hình gói & hệ số giá ==================
    // Tạo $hotelLevelLower một cách an toàn để kiểm tra
    $hotelLevelLower = mb_strtolower($hotelLevelRaw ?? '');
    $isUnknownHotelLvl = empty($hotelLevelRaw) ||
        str_contains($hotelLevelLower, 'chưa biết') ||
        str_contains($hotelLevelLower, 'unknown');

    // 3 gói: tiết kiệm / tiêu chuẩn / nâng cao
    $packageMeta = [
        1 => ['suffix' => 'Gói tiết kiệm',  'multiplier' => 0.9],
        2 => ['suffix' => 'Gói tiêu chuẩn', 'multiplier' => 1.0],
        3 => ['suffix' => 'Gói nâng cao',   'multiplier' => 1.15],
    ];

    if ($isUnknownHotelLvl) {
        $slots = [
            ['hotel_level' => 'Khách sạn 2–3 sao',          'package_index' => 1, 'code_suffix' => 'A'],
            ['hotel_level' => 'Khách sạn 2–3 sao',          'package_index' => 2, 'code_suffix' => 'B'],
            ['hotel_level' => 'Khách sạn 3–4 sao',          'package_index' => 1, 'code_suffix' => 'C'],
            ['hotel_level' => 'Khách sạn 3–4 sao',          'package_index' => 2, 'code_suffix' => 'D'],
            ['hotel_level' => 'Resort / 4–5 sao',           'package_index' => 1, 'code_suffix' => 'E'],
            ['hotel_level' => 'Resort / 4–5 sao',           'package_index' => 2, 'code_suffix' => 'F'],
            ['hotel_level' => 'Resort / 4–5 sao (cao cấp)', 'package_index' => 3, 'code_suffix' => 'G'],
        ];
    } else {
        $slots = [
            ['hotel_level' => $hotelLevelRaw, 'package_index' => 1, 'code_suffix' => 'A'],
            ['hotel_level' => $hotelLevelRaw, 'package_index' => 2, 'code_suffix' => 'B'],
            ['hotel_level' => $hotelLevelRaw, 'package_index' => 3, 'code_suffix' => 'C'],
        ];
    }

    // Hệ số tour tự thiết kế (áp dụng cho TẤT CẢ tour tự thiết kế)
    // Tour cá nhân = 1 người, Tour đoàn = 2 người trở lên
    $privateMultiplier = 1.0;
    
    // Áp dụng hệ số cho tất cả tour tự thiết kế (không phân biệt private hay group)
    if ($totalPeople === 1) {
        // Tour cá nhân (1 người)
        $privateMultiplier = 1.5;   // 1 người → đắt hơn
    } elseif ($totalPeople >= 2 && $totalPeople <= 3) {
        // Tour đoàn 2-3 người
        $privateMultiplier = 1.5;   // 2-3 người → phụ thu nhẹ
    } elseif ($totalPeople >= 4 && $totalPeople <= 9) {
        // Tour đoàn 4-9 người
        $privateMultiplier = 1.2;   // 4-9 người → không phụ thu
        } else {
        // Tour đoàn >= 10 người
        $privateMultiplier = 1.0;   // >= 10 người → không phụ thu
    }

    // Giảm giá tour đoàn (đã có sẵn hàm calculateGroupDiscountFactor)
    $groupDiscountFactor  = $this->calculateGroupDiscountFactor($totalPeople, $tourType);
    $groupDiscountPercent = (int) round((1 - $groupDiscountFactor) * 100);

    // ================== 4. Tạo danh sách phương án ==================
    $options = [];

    foreach ($slots as $index => $slot) {
        $packageIndex = $slot['package_index'];
        $pkgMeta      = $packageMeta[$packageIndex];

        $optionCode  = $requestCode . '-' . $slot['code_suffix'];
        $optionHotel = $slot['hotel_level'];

        // 1️⃣ Khách sạn / người (dùng helper cũ)
        $hotelCostPerPerson = $this->estimateHotelCostPerPerson($optionHotel, $nights);

        // 2️⃣ Core cost / người (đúng nghĩa: khách sạn + ăn + đi lại + vé tham quan)
        $coreCostPerPerson = $mandatoryActCost      // vé tham quan chính (đã áp placeFactor phía trên)
            + $foodCostPerPerson                    // ăn uống
            + $transportCostPerPerson               // di chuyển nội bộ
            + $hotelCostPerPerson;                  // khách sạn

        // 3️⃣ Phí dịch vụ cơ bản (tính trên coreCost, chưa tính hệ số tour riêng)
        $baseServiceFeePerPerson = (int) round($coreCostPerPerson * $serviceFeeRate / 1000) * 1000;
        
        // 4️⃣ Phụ thu cao điểm (nếu có)
        $surchargePerPerson  = ($highSeasonRate > 0)
            ? (int) round($coreCostPerPerson * $highSeasonRate / 1000) * 1000
            : 0;

        // 5️⃣ Phí tour tự thiết kế (tính như một phần của phí dịch vụ)
        // Phí tour tự thiết kế = Core cost × (privateMultiplier - 1) nếu có hệ số > 1.0
        // Áp dụng cho TẤT CẢ tour tự thiết kế (không phân biệt private hay group)
        $privateTourFeePerPerson = 0;
        if ($privateMultiplier > 1.0) {
            $privateTourFeePerPerson = (int) round($coreCostPerPerson * ($privateMultiplier - 1.0) / 1000) * 1000;
        }

        // 6️⃣ Tổng phí dịch vụ / điều hành tour (bao gồm phí dịch vụ + phí tour riêng)
        $serviceFeePerPerson = $baseServiceFeePerPerson + $privateTourFeePerPerson;

        // 7️⃣ Áp hệ số gói (KHÔNG áp hệ số tour riêng nữa vì đã tính vào phí dịch vụ)
        $coreCostAfterPackage = (int) round($coreCostPerPerson * $pkgMeta['multiplier'] / 1000) * 1000;
        $serviceFeeAfterPackage = (int) round($serviceFeePerPerson * $pkgMeta['multiplier'] / 1000) * 1000;
        $surchargeAfterPackage = ($surchargePerPerson > 0)
            ? (int) round($surchargePerPerson * $pkgMeta['multiplier'] / 1000) * 1000
            : 0;

        // 8️⃣ Tổng chi phí gốc / người (sau hệ số gói, trước giảm đoàn)
        $baseCostPerPerson = $coreCostAfterPackage
            + $serviceFeeAfterPackage
            + $surchargeAfterPackage;

        // Đây là giá gốc / người TRƯỚC khi giảm ưu đãi đoàn
        $baseBeforeDiscountPerPerson = $baseCostPerPerson;
        
        // Giữ lại giá trị để hiển thị trong breakdown
        // coreCostAfterMultiplier sẽ được tính lại từ tổng 4 mục sau hệ số gói
        $serviceFeeAfterMultiplier = $serviceFeeAfterPackage;
        $surchargeAfterMultiplier = $surchargeAfterPackage;

        // 6️⃣ Áp ưu đãi tour đoàn (chiết khấu % theo số khách)
        $pricePerAdult = (int) round(
            $baseBeforeDiscountPerPerson * $groupDiscountFactor / 1000
        ) * 1000;
        $discountAmountPerAdult = $baseBeforeDiscountPerPerson - $pricePerAdult;

        // 7️⃣ Giá trẻ em
        $pricePerChild = (int) round($pricePerAdult * $childFactor / 1000) * 1000;

        // 8️⃣ Tổng tiền
        $totalAdultsPrice   = $pricePerAdult * $adults;
        $totalChildrenPrice = $pricePerChild * $children;
        $totalPrice         = $totalAdultsPrice + $totalChildrenPrice;

        // Tổng tour nếu KHÔNG giảm đoàn
        $undiscountedTotal   = (int) round($baseBeforeDiscountPerPerson * $totalPeople / 1000) * 1000;
        $discountAmountTotal = $undiscountedTotal - $totalPrice;

        // 9️⃣ Lịch trình & optional theo từng gói
        $itineraryForOption = $this->enrichItineraryForPackage($baseItinerary, $packageIndex, $intensity);

        $hotelPerNight = $nights > 0
            ? (int) round($hotelCostPerPerson / $nights / 1000) * 1000
            : $hotelCostPerPerson;

        // Optional activities: chỉ để hiển thị, không cộng vào giá tour
        $optionalsForOption = [];
        foreach ($optionalActivities as $opt) {
            $priceOpt = (int) round(($opt['price_per_person'] ?? 0) / 1000) * 1000;
            $optionalsForOption[] = [
                'id'               => $opt['id'] ?? null,
                'label'            => $opt['label'] ?? ($opt['name'] ?? 'Hoạt động'),
                'note'             => $opt['note'] ?? 'Chi phí tự túc, không bắt buộc tham gia.',
                'price_per_person' => $priceOpt,
                'included'         => $opt['included'] ?? false,
            ];
        }

        // 🔍 BREAKDOWN cho view
        // Tính các giá trị sau hệ số gói (không nhân hệ số tour riêng) để hiển thị
        $hotelCostAfterPackage = (int) round($hotelCostPerPerson * $pkgMeta['multiplier'] / 1000) * 1000;
        $foodCostAfterPackage = (int) round($foodCostPerPerson * $pkgMeta['multiplier'] / 1000) * 1000;
        $activityCostAfterPackage = (int) round($mandatoryActCost * $pkgMeta['multiplier'] / 1000) * 1000;
        $transportCostAfterPackage = (int) round($transportCostPerPerson * $pkgMeta['multiplier'] / 1000) * 1000;
        
        // Tổng chi phí dịch vụ gốc = tổng 4 mục sau hệ số gói (chưa nhân hệ số tour riêng)
        $coreCostAfterMultiplier = $hotelCostAfterPackage + $foodCostAfterPackage + $activityCostAfterPackage + $transportCostAfterPackage;
        
        $priceBreakdown = [
            // Chi phí cơ bản / người (sau hệ số gói, chưa nhân hệ số tour riêng)
            'activity_per_person'        => $activityCostAfterPackage,
            'hotel_per_person'           => $hotelCostAfterPackage,
            'hotel_per_night'            => $hotelPerNight,
            'food_per_person'            => $foodCostAfterPackage,
            'transport_per_person'       => $transportCostAfterPackage,

            // Phí dịch vụ & phụ thu (giá trị gốc trước khi nhân hệ số - để tham khảo)
            'base_service_fee_per_person' => $baseServiceFeePerPerson,
            'private_tour_fee_per_person' => $privateTourFeePerPerson,
            'service_fee_per_person'     => $serviceFeePerPerson,
            'surcharge_per_person'       => $surchargePerPerson,
            // Phí dịch vụ & phụ thu SAU KHI nhân hệ số gói (để hiển thị trong breakdown)
            'service_fee_after_multiplier' => $serviceFeeAfterMultiplier,
            'surcharge_after_multiplier'   => $surchargeAfterMultiplier,
            'core_cost_after_multiplier'   => $coreCostAfterMultiplier,
            'service_fee_rate_percent'   => (int) ($serviceFeeRate * 100),
            'high_season_rate_percent'   => (int) ($highSeasonRate * 100),
            'private_multiplier'         => $privateMultiplier,
            'is_private_tour'            => ($tourType === 'private'),

            // Tổng / người
            'core_cost_per_person'            => $coreCostPerPerson,
            'base_before_discount_per_person' => $baseBeforeDiscountPerPerson,
            'discount_amount_per_adult'       => $discountAmountPerAdult,

            // Thông tin gói + hệ số
            'package_name'               => $pkgMeta['suffix'],
            'package_multiplier'         => $pkgMeta['multiplier'],
            'private_multiplier'         => $privateMultiplier,
            'group_discount_percent'     => $groupDiscountPercent,
            'group_discount_factor'      => $groupDiscountFactor,

            // Giá cuối cùng
            'adult_price'                => $pricePerAdult,
            'child_price'                => $pricePerChild,
            'child_factor'               => $childFactor,
            'total_price_adults'         => $totalAdultsPrice,
            'total_price_children'       => $totalChildrenPrice,
            'final_total_price'          => $totalPrice,

            // Tổng tour (trước & sau ưu đãi)
            'undiscounted_total'         => $undiscountedTotal,
            'discount_amount_total'      => $discountAmountTotal,

            'optionals'                  => $optionalsForOption,
        ];

        $title = sprintf('%s %dN%dĐ – %s', $destStr, $days, $nights, $pkgMeta['suffix']);

        $options[] = [
            'option_index'           => $index + 1,
            'code'                   => $optionCode,
            'title'                  => $title,
            'hotel_level'            => $optionHotel,
            'intensity'              => $intensity,
            'tour_type'              => $tourType,
            'days'                   => $days,
            'nights'                 => $nights,
            'total_people'           => $totalPeople,

            'price_per_adult'        => $pricePerAdult,
            'price_per_child'        => $pricePerChild,
            'total_price_adults'     => $totalAdultsPrice,
            'total_price_children'   => $totalChildrenPrice,
            'total_price'            => $totalPrice,

            'highlights'             => $must,
            'itinerary'              => $itineraryForOption,
            'group_discount_percent' => $groupDiscountPercent,
            'price_breakdown'        => $priceBreakdown,
        ];
    }

    return $options;
}

    /**
     * Tăng độ chi tiết lịch trình cho gói tiêu chuẩn / nâng cao
     */
    protected function enrichItineraryForPackage(array $baseItinerary, int $packageIndex, string $intensity)
{
    $result = [];

    foreach ($baseItinerary as $day) {

        // Giữ nguyên mô tả được tạo từ generateTourOptions
        $desc = $day['description'] ?? '';

        // Thêm tuỳ chỉnh theo gói
        if ($packageIndex === 2) {
            $desc .= ' Lịch trình tiêu chuẩn: sắp xếp 2–3 điểm tham quan chính.';
        } elseif ($packageIndex === 3) {
            $desc .= ' Lịch trình nâng cao: đi được nhiều điểm hơn, trải nghiệm phong phú.';
        }

        $result[] = [
            'day' => $day['day'],
            'description' => $desc,
            'places' => $day['places'] ?? [],
            'estimatedHours' => $day['estimatedHours'] ?? null,
        ];
    }

    return $result;
}

/**
 * Ước lượng chi phí khách sạn / NGƯỜI dựa trên level khách sạn & số đêm
 * Giả định ở 2–3 khách / phòng nên đơn giá / người thấp hơn đơn giá phòng.
 */
protected function estimateHotelCostPerPerson(string $hotelLevel, int $nights): int
{
    if ($nights <= 0) {
        return 0;
    }

    $hotelLevelLower = mb_strtolower($hotelLevel);

    // Đơn giá ước lượng theo NGƯỜI / ĐÊM
    if (str_contains($hotelLevelLower, 'resort') || str_contains($hotelLevelLower, '5')) {
        $perNightPerPerson = 700000;  // resort / 5 sao
    } elseif (str_contains($hotelLevelLower, '4-5') || str_contains($hotelLevelLower, '4')) {
        $perNightPerPerson = 550000;  // 4 sao
    } elseif (str_contains($hotelLevelLower, '3-4') || str_contains($hotelLevelLower, '3')) {
        $perNightPerPerson = 400000;  // 3 sao / 3-4 sao trung bình
    } else {
        $perNightPerPerson = 280000;  // 1–2 sao / nhà nghỉ
    }

    return $perNightPerPerson * $nights;
}


/**
 * Tính hệ số giảm giá cho tour đoàn theo số lượng khách
 *  - Tour cá nhân: không giảm
 *  - 1–3 khách: không giảm
 *  - 4–5 khách: -2%
 *  - 6–9 khách: -4%
 *  - 10–14 khách: -6%
 *  - >=15 khách: -8%
 */
protected function calculateGroupDiscountFactor(int $totalPeople, string $tourType): float
{
    // Chỉ áp dụng giảm giá cho tour đoàn
    if ($tourType !== 'group') {
        return 1.0;
    }

    if ($totalPeople >= 15) {
        return 0.92;   // giảm 8%
    }

    if ($totalPeople >= 10) {
        return 0.94;   // giảm 6%
    }

    if ($totalPeople >= 6) {
        return 0.96;   // giảm 4%
    }

    if ($totalPeople >= 4) {
        return 0.98;   // giảm 2%
    }

    return 1.0;        // < 4 người: giữ nguyên
}

    /**
     * API autocomplete điểm đến
     */
    public function searchDestinations(Request $request)
    {
        $q = trim($request->query('q', ''));

        $builder = DB::table('tbl_destinations')
            ->select('id', 'name', 'popular_places');

        if ($q !== '') {
            $like = '%' . $q . '%';

            $builder->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                      ->orWhere('slug', 'like', $like);
            });
        }

        $rows = $builder->orderBy('name')->limit(10)->get();

        $destinations = $rows->map(function ($row) {
            return [
                'id'   => $row->id,
                'name' => $row->name,
                'popular_places' => $row->popular_places
                    ? array_map('trim', explode('|', $row->popular_places))
                    : [],
            ];
        });

        return response()->json($destinations);
    }

public function buildPriceBreakdown($tour, $userOptions)
{
    $breakdown = [];

    $nights = $tour->nights;
    $days   = $tour->days;

    // ===== 1. Khách sạn =====
    $hotelClass = $userOptions['hotel_class']; // 3 / 4 / 5
    $perNightMap = [
        3 => 400000,
        4 => 550000,
        5 => 700000,
    ];

    $unitNight = $perNightMap[$hotelClass] ?? 400000;

    $breakdown['hotel'] = [
        'label'    => "Khách sạn {$hotelClass} sao",
        'quantity' => $nights,
        'unit'     => $unitNight,
        'total'    => $unitNight * $nights,
    ];

    // ===== 2. Vé tham quan (ước lượng trung bình) =====
    $ticketUnit = 120000;
    $ticketQty  = count($userOptions['places']);

    $breakdown['tickets'] = [
        'label'    => 'Vé tham quan',
        'quantity' => $ticketQty,
        'unit'     => $ticketUnit,
        'total'    => $ticketUnit * $ticketQty,
    ];

    // ===== 3. Ăn uống =====
    $foodUnit = 200000; // 1 bữa chính / người / ngày (có thể tùy chỉnh theo class)
    $breakdown['foods'] = [
        'label'    => 'Ăn uống',
        'quantity' => $days,
        'unit'     => $foodUnit,
        'total'    => $foodUnit * $days,
    ];

    // ===== 4. Di chuyển =====
    $transportBase = 150000;
    if (!empty($userOptions['private_car'])) {
        $transportBase += 250000;
    }

    $breakdown['transport'] = [
        'label'    => 'Di chuyển nội bộ',
        'quantity' => 1,
        'unit'     => $transportBase,
        'total'    => $transportBase,
    ];

    // ===== 5. Dịch vụ thêm =====
    $extraTotal = 0;
    $extraList  = [];

    foreach ($userOptions['extra_services'] as $service) {
        $extraList[] = [
            'label' => $service['name'],
            'price' => $service['price'],
        ];
        $extraTotal += $service['price'];
    }

    // Tổng giá 1 người lớn (trước / sau thuế, bạn có thể cộng thêm 8–10% nếu muốn)
    $adultTotal = array_sum(array_column($breakdown, 'total')) + $extraTotal;

    // Trẻ em (75%)
    $childTotal = (int) round($adultTotal * 0.75 / 1000) * 1000;

    return [
        'breakdown'    => $breakdown,
        'extra'        => $extraList,
        'adult_price'  => (int) round($adultTotal / 1000) * 1000,
        'child_price'  => $childTotal,
    ];
}

public function checkoutCustomTour($id, Request $request)
{
    // 1. Lấy phương án tour đã lưu trong tbl_custom_tours
    $customTour = DB::table('tbl_custom_tours')->where('id', $id)->first();

    if (!$customTour) {
        return redirect()
            ->route('build-tour.result')
            ->with('error', 'Phương án tour đã chọn không tồn tại hoặc đã bị xoá.');
    }

    // 2. Giải mã option_json để lấy chi tiết lịch trình, giá...
    $option = json_decode($customTour->option_json, true) ?? [];

    // 3. Lấy price_breakdown từ JSON nếu có
    $priceSummary = $option['price_breakdown'] ?? [];

    // 4. Gộp dữ liệu vào $chosenTour để đẩy ra view
    $chosenTour = $option;

    // Đảm bảo price_breakdown luôn có đầy đủ giá trị và đồng bộ với total_price
    // Ưu tiên tổng tiền từ breakdown, fallback sang estimated_cost nếu thiếu
    if (!empty($priceSummary) && isset($priceSummary['final_total_price'])) {
        $chosenTour['total_price'] = $priceSummary['final_total_price'];
        // Đồng bộ lại các giá trị khác từ breakdown để đảm bảo nhất quán
        if (isset($priceSummary['adult_price'])) {
            $chosenTour['price_per_adult'] = $priceSummary['adult_price'];
        }
        if (isset($priceSummary['child_price'])) {
            $chosenTour['price_per_child'] = $priceSummary['child_price'];
        }
        if (isset($priceSummary['total_price_adults'])) {
            $chosenTour['total_price_adults'] = $priceSummary['total_price_adults'];
        }
        if (isset($priceSummary['total_price_children'])) {
            $chosenTour['total_price_children'] = $priceSummary['total_price_children'];
        }
    } else {
        $chosenTour['total_price'] = $customTour->estimated_cost ?? 0;
    }
    
    // Đảm bảo optional_activities_total được truyền vào priceSummary nếu có
    if (isset($chosenTour['optional_activities_total']) && $chosenTour['optional_activities_total'] > 0) {
        if (!isset($priceSummary['optional_activities_total'])) {
            $priceSummary['optional_activities_total'] = $chosenTour['optional_activities_total'];
        }
    }
    
    // Đảm bảo price_breakdown luôn được truyền vào view (ưu tiên từ option)
    if (empty($priceSummary) && isset($chosenTour['price_breakdown']) && !empty($chosenTour['price_breakdown'])) {
        $priceSummary = $chosenTour['price_breakdown'];
    }

    // Bổ sung các field lấy từ DB
    $chosenTour['adults']        = $customTour->adults;
    $chosenTour['children']      = $customTour->children;
    $chosenTour['total_people']  = $customTour->total_people;
    $chosenTour['destination']   = $customTour->destination;
    $chosenTour['days']          = $customTour->days;
    $chosenTour['nights']        = $customTour->nights;
    $chosenTour['hotel_level']   = $customTour->hotel_level;
    $chosenTour['tour_type']     = $customTour->tour_type;

    // Ngày đi / về: dùng đúng dữ liệu đã lưu trong DB
    $chosenTour['start_date']    = $customTour->start_date;
    $chosenTour['end_date']      = $customTour->end_date;

    $title = 'Đặt tour theo yêu cầu';
    
    // Lấy thông tin user để tự động điền form
    $user = null;
    if (session()->has('username')) {
        $userId = $request->session()->get('userId');
        if (!$userId) {
            $username = session()->get('username');
            $userModel = new User();
            $userId = $userModel->getUserId($username);
            $request->session()->put('userId', $userId);
        }
        if ($userId) {
            $userModel = new User();
            $user = $userModel->getUser($userId);
        }
    }

    // Lưu id custom tour vào session để dùng lại khi submit
    Session::put('custom_tour_checkout_id', $customTour->id);
    $customTourId = $customTour->id;

    return view('clients.build_tour_checkout', compact(
        'chosenTour',
        'priceSummary',
        'user',
        'title',
        'customTourId'
    ));
}


public function submitCustomTourBooking($id, Request $request)
{
    // 1. Validate dữ liệu form
    $request->validate([
        'full_name' => 'required|string|max:255',
        'phone'     => 'required|string|max:20',
        'email'     => 'required|email|max:255',
        'address'   => 'nullable|string|max:255',
        'note'      => 'nullable|string|max:1000',
    ], [
        'full_name.required' => 'Vui lòng nhập họ tên.',
        'phone.required'     => 'Vui lòng nhập số điện thoại.',
        'email.required'     => 'Vui lòng nhập email.',
        'email.email'        => 'Email không đúng định dạng.',
    ]);

    // 2. Lấy lại custom tour từ DB
    $customTour = DB::table('tbl_custom_tours')->where('id', $id)->first();

    if (!$customTour) {
        return redirect()
            ->route('build-tour.result')
            ->with('error', 'Không tìm thấy phương án tour. Vui lòng chọn lại.');
    }

    // 3. Lấy userId theo session (đúng với chooseTour)
    $userId = $request->session()->get('userId');

    // 4. Số người & tổng tiền
    $numAdults   = $customTour->adults ?? $customTour->total_people ?? 1;
    $numChildren = $customTour->children ?? 0;

    // Lấy lại JSON để ưu tiên final_total_price
    $option       = json_decode($customTour->option_json, true) ?? [];
    $priceSummary = $option['price_breakdown'] ?? [];

    $totalPrice = $priceSummary['final_total_price']
        ?? ($customTour->estimated_cost ?? 0);

    // 5. Insert vào tbl_booking
    // Với custom tour, không insert tourId (để NULL) vì chỉ cần custom_tour_id
    $bookingData = [
        'custom_tour_id' => $customTour->id,
        'userId'         => $userId,
        'fullName'       => $request->full_name,
        'email'          => $request->email,
        'phoneNumber'    => $request->phone,
        'address'        => $request->address ?? '',
        'bookingDate'    => now(),
        'numAdults'      => $numAdults,
        'numChildren'    => $numChildren,
        'totalPrice'     => $totalPrice,
        // theo hệ thống của bạn: 'b' = booked (đặt mới), 'y' = confirmed
        'bookingStatus'  => 'b',
    ];
    
    // Chỉ thêm paymentMethod và paymentStatus nếu cột tồn tại trong bảng
    // Kiểm tra bằng cách thử insert và catch exception, hoặc chỉ insert các cột cơ bản
    try {
        // Thử insert với paymentMethod/paymentStatus
        if ($request->has('payment')) {
            $bookingData['paymentMethod'] = $request->payment;
        }
        $bookingData['paymentStatus'] = 'n'; // 'n' = chưa thanh toán
        
        $bookingId = DB::table('tbl_booking')->insertGetId($bookingData);
    } catch (QueryException $e) {
        // Nếu lỗi do cột không tồn tại, thử lại không có paymentMethod/paymentStatus
        if (str_contains($e->getMessage(), 'Unknown column')) {
            unset($bookingData['paymentMethod']);
            unset($bookingData['paymentStatus']);
            $bookingId = DB::table('tbl_booking')->insertGetId($bookingData);
        } else {
            // Nếu lỗi khác, throw lại
            throw $e;
        }
    }

    // 6. Tạo checkout cho booking (nếu chưa có) - đặc biệt cho thanh toán tại văn phòng
    $paymentMethod = $request->input('payment', 'office-payment'); // Mặc định là thanh toán tại văn phòng
    
    try {
        $checkoutId = DB::table('tbl_checkout')->insertGetId([
            'bookingId' => $bookingId,
            'paymentMethod' => $paymentMethod,
            'amount' => $totalPrice,
            'paymentStatus' => 'n', // 'n' = chưa thanh toán (sẽ thanh toán tại văn phòng)
        ]);
    } catch (QueryException $e) {
        // Nếu có lỗi (ví dụ cột không tồn tại), bỏ qua
        $checkoutId = null;
    }

    // 7. Xoá session id checkout (nếu muốn)
    Session::forget('custom_tour_checkout_id');

    // 8. Redirect đến trang tour-booked với bookingId
    return redirect()->route('tour-booked', ['bookingId' => $bookingId])
        ->with('success', 'Bạn đã đặt tour theo yêu cầu thành công! Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.');
}

    /**
     * Cập nhật meal plan cho phương án tour
     */
    public function updateMeals($index, Request $request)
    {
        $mealService = new MealService();
        
        // Lấy dữ liệu từ session
        $requestData = $request->session()->get('build_tour.requestData');
        $generatedTours = $request->session()->get('build_tour.generatedTours');
        
        if (!$requestData || !$generatedTours) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên làm việc đã hết hạn, vui lòng thiết kế tour lại.'
            ], 400);
        }

        // Tìm option theo index
        $option = null;
        foreach ($generatedTours as $tour) {
            if (isset($tour['option_index']) && (int)$tour['option_index'] === (int)$index) {
                $option = $tour;
                break;
            }
        }

        if (!$option) {
            $arrayIndex = (int)$index - 1;
            if (isset($generatedTours[$arrayIndex])) {
                $option = $generatedTours[$arrayIndex];
            }
        }

        if (!$option) {
            return response()->json([
                'success' => false,
                'message' => 'Phương án tour không tồn tại.'
            ], 404);
        }

        // Validate meal plan
        // Thử lấy từ nhiều nguồn
        $mealPlan = $request->input('meal_plan', []);
        
        // Nếu không có trong input, thử lấy từ JSON
        if (empty($mealPlan) && $request->isJson()) {
            $jsonData = $request->json()->all();
            $mealPlan = $jsonData['meal_plan'] ?? [];
        }
        
        // Log để debug
        Log::info('Update meal plan request', [
            'index' => $index,
            'meal_plan_received' => $mealPlan,
            'meal_plan_count' => count($mealPlan),
            'request_all' => $request->all(),
            'request_json' => $request->json()->all(),
            'content_type' => $request->header('Content-Type'),
            'is_json' => $request->isJson(),
            'method' => $request->method()
        ]);
        
        // Nếu meal_plan rỗng, trả về lỗi
        if (empty($mealPlan)) {
            Log::error('Meal plan is empty', [
                'request_all' => $request->all(),
                'request_json' => $request->json()->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu meal plan không được gửi. Vui lòng thử lại.',
                'debug' => [
                    'request_all' => $request->all(),
                    'request_json' => $request->json()->all()
                ]
            ], 400);
        }
        
        $validation = $mealService->validateMealPlan($mealPlan);
        
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu meal plan không hợp lệ: ' . implode(', ', $validation['errors'])
            ], 400);
        }

        // Lấy số người
        $adults = (int) ($requestData['adults'] ?? 1);
        $children = (int) ($requestData['children'] ?? 0);
        $days = $option['days'] ?? $requestData['days'] ?? 1;
        
        // Lấy price_breakdown
        $priceBreakdown = $option['price_breakdown'] ?? [];
        $packageMultiplier = $priceBreakdown['package_multiplier'] ?? 1.0;
        
        // Lấy hotel level
        $hotelLevelRaw = $requestData['hotel_level'] ?? '';

        // Tính chi phí ăn uống mới (dùng thuật toán mới với multiplier, phân biệt bữa chuẩn và bữa thêm)
        $newMealCost = $mealService->calculateCustomMealCost($mealPlan, $days, $adults, $children, $hotelLevelRaw);
        
        // Tính chi phí ăn uống cũ
        $oldMealCost = $mealService->calculateOldMealCost($priceBreakdown, $adults, $children);
        
        // Lưu giá trị cũ để tính chênh lệch
        $oldTotalPrice = (int) ($option['total_price'] ?? 0);

        // Cập nhật meal_plan vào option
        $option['meal_plan'] = $mealPlan;

        // Tính giá ăn uống mới / người (sau hệ số gói)
        // Tính giá ăn uống / người (trước hệ số gói)
        $baseFoodPerPerson = $newMealCost / max($adults + $children * 0.7, 1);
        $baseFoodPerPerson = (int) round($baseFoodPerPerson / 1000) * 1000;
        
        // Áp dụng hệ số gói
        $newFoodPerPerson = (int) round($baseFoodPerPerson * $packageMultiplier / 1000) * 1000;
        
        if (!isset($option['price_breakdown'])) {
            $option['price_breakdown'] = [];
        }
        
        // Lấy giá trị cũ
        $oldFoodPerPerson = $priceBreakdown['food_per_person'] ?? 0;
        $oldBaseFoodPerPerson = $oldFoodPerPerson / $packageMultiplier;
        
        // Tính chênh lệch giá ăn uống (sau hệ số gói)
        $foodPriceDiff = $newFoodPerPerson - $oldFoodPerPerson;
        
        // Cập nhật meal_plan và food_per_person
        $option['price_breakdown']['food_per_person'] = $newFoodPerPerson;
        $option['price_breakdown']['meal_plan'] = $mealPlan;
        $option['meal_plan'] = $mealPlan;
        
        // Tính lại core_cost_after_multiplier (tổng 4 mục sau hệ số gói)
        $hotelCost = $priceBreakdown['hotel_per_person'] ?? 0;
        $activityCost = $priceBreakdown['activity_per_person'] ?? 0;
        $transportCost = $priceBreakdown['transport_per_person'] ?? 0;
        $newCoreCostAfterMultiplier = $hotelCost + $newFoodPerPerson + $activityCost + $transportCost;
        
        $option['price_breakdown']['core_cost_after_multiplier'] = $newCoreCostAfterMultiplier;
        
        // Tính lại base_before_discount_per_person
        $serviceFeeAfterMultiplier = $priceBreakdown['service_fee_after_multiplier'] ?? 0;
        $surchargeAfterMultiplier = $priceBreakdown['surcharge_after_multiplier'] ?? 0;
        $newBaseBeforeDiscount = $newCoreCostAfterMultiplier + $serviceFeeAfterMultiplier + $surchargeAfterMultiplier;
        
        $option['price_breakdown']['base_before_discount_per_person'] = $newBaseBeforeDiscount;
        
        // Tính lại giá người lớn và trẻ em (áp dụng giảm giá đoàn)
        $groupDiscountFactor = $priceBreakdown['group_discount_factor'] ?? 1.0;
        $newPricePerAdult = (int) round($newBaseBeforeDiscount * $groupDiscountFactor / 1000) * 1000;
        $childFactor = $priceBreakdown['child_factor'] ?? 0.75;
        $newPricePerChild = (int) round($newPricePerAdult * $childFactor / 1000) * 1000;
        
        // Cập nhật giá
        $option['price_per_adult'] = $newPricePerAdult;
        $option['price_per_child'] = $newPricePerChild;
        $option['total_price_adults'] = $newPricePerAdult * $adults;
        $option['total_price_children'] = $newPricePerChild * $children;
        $option['total_price'] = $option['total_price_adults'] + $option['total_price_children'];
        
        // Cập nhật price_breakdown
        $option['price_breakdown']['adult_price'] = $newPricePerAdult;
        $option['price_breakdown']['child_price'] = $newPricePerChild;
        $option['price_breakdown']['total_price_adults'] = $option['total_price_adults'];
        $option['price_breakdown']['total_price_children'] = $option['total_price_children'];
        $option['price_breakdown']['final_total_price'] = $option['total_price'];
        
        // Tính discount amount
        $undiscountedTotal = (int) round($newBaseBeforeDiscount * ($adults + $children) / 1000) * 1000;
        $discountAmountTotal = $undiscountedTotal - $option['total_price'];
        $option['price_breakdown']['undiscounted_total'] = $undiscountedTotal;
        $option['price_breakdown']['discount_amount_total'] = $discountAmountTotal;

        // Cập nhật lại generatedTours trong session
        $found = false;
        foreach ($generatedTours as $idx => $tour) {
            if (isset($tour['option_index']) && (int)$tour['option_index'] === (int)$index) {
                $generatedTours[$idx] = $option;
                $found = true;
                break;
            }
        }
        
        // Fallback nếu không tìm thấy theo option_index
        if (!$found) {
            $arrayIndex = (int)$index - 1;
            if (isset($generatedTours[$arrayIndex])) {
                $generatedTours[$arrayIndex] = $option;
                $found = true;
            }
        }

        if (!$found) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật tour trong session. Vui lòng thử lại.'
            ], 500);
        }

        // Lưu lại vào session
        $request->session()->put('build_tour.generatedTours', $generatedTours);
        
        // Log để debug
        Log::info('Meal plan updated', [
            'index' => $index,
            'option_index' => $option['option_index'] ?? null,
            'meal_plan_keys' => array_keys($mealPlan),
            'new_total_price' => $option['total_price'],
            'old_total_price' => $oldTotalPrice
        ]);

        // Tính chênh lệch giá
        $priceDiff = $option['total_price'] - $oldTotalPrice;
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ăn uống thành công!',
            'data' => [
                'meal_plan' => $mealPlan,
                'new_total_price' => $option['total_price'],
                'old_total_price' => $oldTotalPrice,
                'price_diff' => $priceDiff,
                'price_per_adult' => $newPricePerAdult,
                'price_per_child' => $newPricePerChild,
                'food_per_person' => $newFoodPerPerson,
            ]
        ]);
    }

}
