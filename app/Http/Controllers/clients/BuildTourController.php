<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Quy ước:
     *  - < 4 khách  : tour cá nhân (private) → giá/khách cao hơn, không giảm giá đoàn
     *  - >= 4 khách : tour đoàn (group)      → được áp dụng khuyến mãi theo số lượng
     *   (match với hàm calculateGroupDiscountFactor)
     */
    if ($totalPeople >= 4) {
        $normalizedTourType = 'group';
    } else {
        $normalizedTourType = 'private';
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
        'title'                  => 'Gợi ý Tour theo yêu cầu',
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
            'title'         => 'Gợi ý Tour theo yêu cầu',
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

    // option_index hiển thị là 1,2,3... => mảng là 0,1,2...
    $arrayIndex = (int)$index - 1;

    if (!isset($generatedTours[$arrayIndex])) {
        return redirect()->route('build-tour.result')
            ->with('error', 'Phương án tour không tồn tại. Vui lòng chọn lại.');
    }

    $option = $generatedTours[$arrayIndex];

    // Lấy thêm 1 số thông tin tiện cho view
    $totalPeople = max(($requestData['adults'] ?? 0) + ($requestData['children'] ?? 0), 1);
    $tourType    = $option['tour_type'] ?? ($requestData['tour_type'] ?? 'group');
    $tourTypeLabel = $tourType === 'private' ? 'Tour cá nhân' : 'Tour đoàn';

    $discountPercent = (int)($option['group_discount_percent'] ?? 0);

    return view('clients.build_tour_option_detail', [
        'title'           => 'Chi tiết phương án tour',
        'requestData'     => $requestData,
        'requestCode'     => $requestCode,
        'option'          => $option,
        'totalPeople'     => $totalPeople,
        'tourType'        => $tourType,
        'tourTypeLabel'   => $tourTypeLabel,
        'discountPercent' => $discountPercent,
        'optionIndex'     => (int)$index,
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

        if (!is_array($generatedTours) || !isset($generatedTours[$index]) || !$requestData) {
            return redirect()->route('build-tour.result')
                ->with('error', 'Tour bạn chọn không tồn tại hoặc phiên làm việc đã hết hạn.');
        }

        $chosenTour = $generatedTours[$index];

        $userId      = $request->session()->get('userId');
        $adults      = $requestData['adults'] ?? 1;
        $children    = $requestData['children'] ?? 0;
        $totalPeople = $adults + $children;

        // 3. Lưu option đã chọn vào tbl_custom_tours
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
            'option_json'   => json_encode($chosenTour, JSON_UNESCAPED_UNICODE),
            'tour_type'     => $chosenTour['tour_type'] ?? ($requestData['tour_type'] ?? 'group'),
            'estimated_cost'=> $chosenTour['total_price'] ?? 0,
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
    $hotelLevelRaw = $requestData['hotel_level'];
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
$hotelLevelLower = mb_strtolower($hotelLevelRaw);

// Ăn uống: chia 3 mức, khách sạn càng cao thì mức chi cho ăn càng rộng
if (str_contains($hotelLevelLower, 'resort') || str_contains($hotelLevelLower, '4-5') || str_contains($hotelLevelLower, '5')) {
    $foodCostPerDay = 300000;   // resort / 4-5 sao
} elseif (str_contains($hotelLevelLower, '3-4') || str_contains($hotelLevelLower, '4') || str_contains($hotelLevelLower, '3')) {
    $foodCostPerDay = 250000;   // 3-4 sao
} else {
    $foodCostPerDay = 180000;   // 1-2 sao / nhà nghỉ
}
$foodCostPerPerson = $foodCostPerDay * $days;

// Di chuyển nội bộ (không bao gồm vé máy bay), tính hơi “nhẹ” để hợp với tour đoàn
$transportBaseDays      = max($days, 2);
$transportCostPerPerson = 120000 + max(0, $transportBaseDays - 2) * 40000;

// ================== 2.6. Thêm phí dịch vụ & phụ thu cao điểm ==================
// Chi phí "gốc" = tham quan bắt buộc + ăn uống + di chuyển
$coreCostPerPerson = $mandatoryActCost
    + $foodCostPerPerson
    + $transportCostPerPerson;

// Phí dịch vụ / điều hành tour (coi như lợi nhuận, HDV, điều hành...)
// Ngân sách thấp (<= 2tr) thì lấy biên lợi nhuận mỏng hơn
$serviceFeeRate = ($baseBudget <= 2000000) ? 0.08 : 0.10;   // 8% hoặc 10%
$serviceFeePerPerson = (int) round($coreCostPerPerson * $serviceFeeRate / 1000) * 1000;

// Phụ thu cao điểm / cuối tuần (ước tính)
$surchargePerPerson = 0;
$highSeasonRate     = 0.0;

if (!empty($requestData['start_date'])) {
    try {
        $start = new \DateTime($requestData['start_date']);
        $dow   = (int) $start->format('N'); // 1=Mon ... 7=Sun

        // Thứ 6–7–CN: +2%
        if ($dow >= 5) {
            $highSeasonRate += 0.02;
        }

        $month = (int) $start->format('n');
        // T1–T2 (Tết): +2%
        if ($month === 1 || $month === 2) {
            $highSeasonRate += 0.02;
        }
    } catch (\Exception $e) {
        // ignore
    }
}

if ($highSeasonRate > 0) {
    $surchargePerPerson = (int) round($coreCostPerPerson * $highSeasonRate / 1000) * 1000;
}

// Tổng chi phí "gốc + phí dịch vụ + phụ thu", CHƯA gồm khách sạn
$baseCostPerPersonRaw = $coreCostPerPerson
    + $serviceFeePerPerson
    + $surchargePerPerson;


    // ================== 3. Cấu hình gói & hệ số giá ==================
    $isUnknownHotelLvl = $hotelLevelRaw === '' ||
        str_contains($hotelLevelLower, 'chưa biết') ||
        str_contains($hotelLevelLower, 'unknown');

    $packageMeta = [
        1 => ['suffix' => 'Gói tiết kiệm',  'multiplier' => 0.8],
        2 => ['suffix' => 'Gói tiêu chuẩn', 'multiplier' => 1.0],
        3 => ['suffix' => 'Gói nâng cao',   'multiplier' => 1.15],
    ];

    $budgetFloorFactors = [
        1 => 0.8,
        2 => 1.0,
        3 => 1.2,
    ];
    // Trần giá theo ngân sách (vd: tiết kiệm ~<=110%, tiêu chuẩn ~<=130%, nâng cao ~<=160%)
$budgetCeilingFactors = [
    1 => 1.10,
    2 => 1.30,
    3 => 1.60,
];


    if ($isUnknownHotelLvl) {
        $slots = [
            ['hotel_level' => 'Khách sạn 2–3 sao',            'package_index' => 1, 'code_suffix' => 'A'],
            ['hotel_level' => 'Khách sạn 2–3 sao',            'package_index' => 2, 'code_suffix' => 'B'],
            ['hotel_level' => 'Khách sạn 3–4 sao',            'package_index' => 1, 'code_suffix' => 'C'],
            ['hotel_level' => 'Khách sạn 3–4 sao',            'package_index' => 2, 'code_suffix' => 'D'],
            ['hotel_level' => 'Resort / 4–5 sao',             'package_index' => 1, 'code_suffix' => 'E'],
            ['hotel_level' => 'Resort / 4–5 sao',             'package_index' => 2, 'code_suffix' => 'F'],
            ['hotel_level' => 'Resort / 4–5 sao (cao cấp)',   'package_index' => 3, 'code_suffix' => 'G'],
        ];
    } else {
        $slots = [
            ['hotel_level' => $hotelLevelRaw, 'package_index' => 1, 'code_suffix' => 'A'],
            ['hotel_level' => $hotelLevelRaw, 'package_index' => 2, 'code_suffix' => 'B'],
            ['hotel_level' => $hotelLevelRaw, 'package_index' => 3, 'code_suffix' => 'C'],
        ];
    }

    // Tour riêng: áp hệ số
    $privateMultiplier = 1;
    if ($tourType === 'private') {
        if ($totalPeople < 4) {
            $privateMultiplier = 2;
        } elseif ($totalPeople > 10) {
            $privateMultiplier = 1;
        } else {
            $privateMultiplier = 1.5;
        }
    }

    // Giảm giá tour đoàn
    $groupDiscountFactor  = $this->calculateGroupDiscountFactor($totalPeople, $tourType);
    $groupDiscountPercent = (int) round((1 - $groupDiscountFactor) * 100);

    // ================== 4. Tạo danh sách phương án ==================
    $options = [];

    foreach ($slots as $index => $slot) {
        $packageIndex = $slot['package_index'];
        $pkgMeta      = $packageMeta[$packageIndex];

        $optionCode   = $requestCode . '-' . $slot['code_suffix'];
        $optionHotel  = $slot['hotel_level'];

        // Tiền khách sạn theo từng option (giống cũ)
        $hotelCostPerPerson = $this->estimateHotelCostPerPerson($optionHotel, $nights);

        // Chi phí trước khi nhân gói + tour riêng
        $undiscounted = $baseCostPerPersonRaw + $hotelCostPerPerson;

        // Sàn & trần giá theo ngân sách (trước giảm đoàn)
$floorBase = $baseBudget * ($budgetFloorFactors[$packageIndex] ?? 1.0) * $privateMultiplier;

$ceilFactor  = $budgetCeilingFactors[$packageIndex] ?? 1.30;
$ceilingBase = $baseBudget * $ceilFactor * $privateMultiplier;

$undiscountedOption = $undiscounted * $pkgMeta['multiplier'] * $privateMultiplier;

// Giữ giá trong khoảng [floorBase ; ceilingBase]
$undiscountedFinal = min(
    max($undiscountedOption, $floorBase),
    $ceilingBase
);


        // 👉 Giá người lớn sau giảm giá đoàn
        $pricePerAdult = (int) round($undiscountedFinal * $groupDiscountFactor / 1000) * 1000;

        // 👉 Giá trẻ em
        $pricePerChild = (int) round($pricePerAdult * $childFactor / 1000) * 1000;

        // Tổng tiền
        $totalAdultsPrice   = $pricePerAdult * $adults;
        $totalChildrenPrice = $pricePerChild * $children;
        $totalPrice         = $totalAdultsPrice + $totalChildrenPrice;

        // Lịch trình + hotelPerNight như cũ ...
        $itineraryForOption = $this->enrichItineraryForPackage($baseItinerary, $packageIndex, $intensity);

        $hotelPerNight = $nights > 0
            ? (int) round($hotelCostPerPerson / $nights / 1000) * 1000
            : $hotelCostPerPerson;

        // Điều chỉnh optional cho option (giữ nguyên như bạn đang dùng)
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

        // Tạm tính trước ưu đãi (theo 1 người lớn, chưa giảm đoàn)
        $baseSubtotalPerPerson = (int) round($undiscountedFinal / $groupDiscountFactor / 1000) * 1000;

        // ---- BREAKDOWN CHI PHÍ CHO VIEW ----
        $priceBreakdown = [
            'activity_per_person'        => $mandatoryActCost,
            'hotel_per_person'           => $hotelCostPerPerson,
            'hotel_per_night'            => $hotelPerNight,
            'food_per_person'            => $foodCostPerPerson,
            'transport_per_person'       => $transportCostPerPerson,

            // NEW: Phí dịch vụ & phụ thu
            'service_fee_per_person'     => $serviceFeePerPerson,
            'surcharge_per_person'       => $surchargePerPerson,
            'service_fee_rate_percent'   => (int)($serviceFeeRate * 100),
            'high_season_rate_percent'   => (int)($highSeasonRate * 100),

            'base_subtotal_per_person'   => $baseSubtotalPerPerson,
            'package_name'               => $pkgMeta['suffix'],
            'package_multiplier'         => $pkgMeta['multiplier'],
            'private_multiplier'         => $privateMultiplier,
            'group_discount_percent'     => $groupDiscountPercent,
            'group_discount_factor'      => $groupDiscountFactor,

            'adult_price'                => $pricePerAdult,
            'child_price'                => $pricePerChild,
            'child_factor'               => $childFactor,
            'total_price_adults'         => $totalAdultsPrice,
            'total_price_children'       => $totalChildrenPrice,
            'final_total_price'          => $totalPrice,

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
 * Fallback đơn giản nếu chưa có dữ liệu tbl_places
 */
protected function generateSimpleOptionsFallback(array $requestData, string $requestCode): array
{
    $days        = $requestData['days'];
    $nights      = $requestData['nights'];
    $destStr     = implode(' – ', $requestData['main_destinations']);
    $main        = $requestData['main_destinations'][0] ?? 'Hành trình';
    $must        = $requestData['must_visit_places'];
    $adults      = (int) ($requestData['adults'] ?? 1);
    $children    = (int) ($requestData['children'] ?? 0);
    $totalPeople = max($adults + $children, 1);
    $baseBudget  = $requestData['budget_per_person'];
    $hotelLevel  = $requestData['hotel_level'];
    $tourType    = $requestData['tour_type'] ?? 'group';
    $intensity   = $requestData['intensity'];

    // Hệ số giá trẻ em
    $childFactor = 0.75;

    // Gói: 1 tiết kiệm, 2 tiêu chuẩn, 3 nâng cao
    // 👉 Dùng chung cấu hình với generateTourOptions
    $packageMeta = [
        1 => ['suffix' => 'Gói tiết kiệm',  'multiplier' => 0.8],
        2 => ['suffix' => 'Gói tiêu chuẩn', 'multiplier' => 1.0],
        3 => ['suffix' => 'Gói nâng cao',   'multiplier' => 1.15],
    ];

    // Sàn giá tối thiểu dựa theo budget cho từng gói
    $budgetFloorFactors = [
        1 => 0.8,
        2 => 1.0,
        3 => 1.2,
    ];

    $hotelLevelLower   = mb_strtolower($hotelLevel);
    $isUnknownHotelLvl = $hotelLevel === '' ||
        str_contains($hotelLevelLower, 'chưa biết') ||
        str_contains($hotelLevelLower, 'unknown');

    if ($isUnknownHotelLvl) {
        $slots = [
            ['hotel_level' => 'Khách sạn 2–3 sao',             'package_index' => 1, 'code_suffix' => 'A'],
            ['hotel_level' => 'Khách sạn 2–3 sao',             'package_index' => 2, 'code_suffix' => 'B'],
            ['hotel_level' => 'Khách sạn 3–4 sao',             'package_index' => 1, 'code_suffix' => 'C'],
            ['hotel_level' => 'Khách sạn 3–4 sao',             'package_index' => 2, 'code_suffix' => 'D'],
            ['hotel_level' => 'Resort / 4–5 sao',              'package_index' => 1, 'code_suffix' => 'E'],
            ['hotel_level' => 'Resort / 4–5 sao',              'package_index' => 2, 'code_suffix' => 'F'],
            ['hotel_level' => 'Resort / 4–5 sao (cao cấp)',    'package_index' => 3, 'code_suffix' => 'G'],
        ];
    } else {
        $slots = [
            ['hotel_level' => $hotelLevel, 'package_index' => 1, 'code_suffix' => 'A'],
            ['hotel_level' => $hotelLevel, 'package_index' => 2, 'code_suffix' => 'B'],
            ['hotel_level' => $hotelLevel, 'package_index' => 3, 'code_suffix' => 'C'],
        ];
    }

    // Hệ số tour riêng
    $privateMultiplier = 1;
    if ($tourType === 'private') {
        if ($totalPeople < 4) {
            $privateMultiplier = 2;
        } elseif ($totalPeople > 10) {
            $privateMultiplier = 1;
        } else {
            $privateMultiplier = 1.5;
        }
    }

    // Hệ số giảm giá đoàn
    $groupDiscountFactor  = $this->calculateGroupDiscountFactor($totalPeople, $tourType);
    $groupDiscountPercent = (int) round((1 - $groupDiscountFactor) * 100);

    $options = [];
    foreach ($slots as $index => $slot) {
        $packageIndex = $slot['package_index'];
        $pkgMeta      = $packageMeta[$packageIndex];

        $optionCode = $requestCode . '-' . $slot['code_suffix'];

        // Giá từ budget (dạng fallback) – coi như giá người lớn
        $multiplier         = $pkgMeta['multiplier'];
        $floorFactorBase    = $budgetFloorFactors[$packageIndex] ?? 1.0;

        $priceFromDataBase  = $baseBudget * $multiplier * $privateMultiplier;
        $priceFromData      = $priceFromDataBase * $groupDiscountFactor;

        $minFromBudgetBase  = $baseBudget * $floorFactorBase * $privateMultiplier;
        $minFromBudget      = $minFromBudgetBase * $groupDiscountFactor;

        // 👉 Giá người lớn / người
        $pricePerAdult = (int) round(max($priceFromData, $minFromBudget) / 1000) * 1000;

        // 👉 Giá trẻ em
        $pricePerChild = (int) round($pricePerAdult * $childFactor / 1000) * 1000;

        // Tổng
        $totalAdultsPrice   = $pricePerAdult * $adults;
        $totalChildrenPrice = $pricePerChild * $children;
        $totalPrice         = $totalAdultsPrice + $totalChildrenPrice;

        // Tách ước lượng các thành phần chi phí từ pricePerAdult
        // (do không có dữ liệu places nên chia theo tỷ lệ ước lượng)
        $activityCostPerPerson  = (int) round($pricePerAdult * 0.20 / 1000) * 1000;
        $hotelCostPerPerson     = (int) round($pricePerAdult * 0.40 / 1000) * 1000;
        $foodCostPerPerson      = (int) round($pricePerAdult * 0.25 / 1000) * 1000;
        $transportCostPerPerson = (int) round($pricePerAdult * 0.15 / 1000) * 1000;

        $hotelPerNight = $nights > 0
            ? (int) round($hotelCostPerPerson / $nights / 1000) * 1000
            : $hotelCostPerPerson;

        $priceBreakdown = [
            'activity_per_person'       => $activityCostPerPerson,
            'hotel_per_person'          => $hotelCostPerPerson,
            'hotel_per_night'           => $hotelPerNight,
            'food_per_person'           => $foodCostPerPerson,
            'transport_per_person'      => $transportCostPerPerson,
            'base_subtotal_per_person'  => $activityCostPerPerson + $hotelCostPerPerson + $foodCostPerPerson + $transportCostPerPerson,
            'package_name'              => $pkgMeta['suffix'],
            'package_multiplier'        => $pkgMeta['multiplier'],
            'private_multiplier'        => $privateMultiplier,
            'group_discount_percent'    => $groupDiscountPercent,
            'group_discount_factor'     => $groupDiscountFactor,

            'adult_price'               => $pricePerAdult,
            'child_price'               => $pricePerChild,
            'child_factor'              => $childFactor,
            'total_price_adults'        => $totalAdultsPrice,
            'total_price_children'      => $totalChildrenPrice,
            'final_total_price'         => $totalPrice,
        ];

        // Lịch trình đơn giản nhưng gói nâng cao sẽ chi tiết hơn
        $itinerary = [];
        for ($d = 1; $d <= $days; $d++) {
            $dayLabel = 'Ngày ' . $d;
            if ($d == 1) {
                $desc = 'Đón khách tại điểm hẹn, di chuyển đến ' . $main . ', nhận phòng và tham quan xung quanh.';
            } elseif ($d == $days) {
                $desc = 'Tự do tham quan, mua sắm. Trả phòng và khởi hành về điểm ban đầu.';
            } else {
                $slice = array_slice($must, ($d - 2) * 2, 2);
                $desc = empty($slice)
                    ? 'Tham quan các điểm nổi bật, nghỉ ngơi và khám phá ẩm thực địa phương.'
                    : 'Tham quan: ' . implode(', ', $slice) . '.';
            }

            // thêm mô tả cho gói 2,3
            if ($packageIndex === 2) {
                $desc .= ' Lịch trình tiêu chuẩn: sắp xếp 2–3 điểm tham quan chính, phù hợp gia đình/nhóm nhỏ.';
            } elseif ($packageIndex === 3) {
                $desc .= ' Lịch trình nâng cao: thêm điểm tham quan/hoạt động trải nghiệm, thời lượng trong ngày có thể 7–9 giờ dành cho khách thích đi nhiều.';
            }

            $itinerary[] = [
                'day'         => $dayLabel,
                'description' => $desc,
            ];
        }

        $options[] = [
            'option_index'           => $index + 1,
            'code'                   => $optionCode,
            'title'                  => sprintf('%s %dN%dĐ – %s', $destStr, $days, $nights, $pkgMeta['suffix']),
            'hotel_level'            => $slot['hotel_level'],
            'intensity'              => $intensity,
            'tour_type'              => $tourType, // 'group' / 'private'
            'days'                   => $days,
            'nights'                 => $nights,
            'total_people'           => $totalPeople,

            'price_per_adult'        => $pricePerAdult,
            'price_per_child'        => $pricePerChild,
            'total_price_adults'     => $totalAdultsPrice,
            'total_price_children'   => $totalChildrenPrice,
            'total_price'            => $totalPrice,

            'highlights'             => $must,
            'itinerary'              => $itinerary,
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

public function checkout()
{
    // Lấy tour đã chọn từ SESSION
    $chosenTour = Session::get('chosen_tour');

    if (!$chosenTour) {
        return redirect()->route('build-tour.form')
            ->with('error', 'Bạn chưa chọn phương án tour!');
    }

    $title = "Đặt tour theo yêu cầu";
    $user = auth()->user();

    return view('clients.build_tour_checkout', compact('chosenTour', 'user', 'title'));
}
public function submitCheckout(Request $request)
{
    $request->validate([
        'full_name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'email' => 'required|email|max:255',
        'note' => 'nullable|string|max:1000',
    ]);

    $chosenTour = Session::get('chosen_tour');

    if (!$chosenTour) {
        return redirect()->route('build-tour.form')
            ->with('error', 'Không tìm thấy phương án tour!');
    }

    // Tạo booking như tour bình thường
    $booking = DB::table('tbl_booking')->insert([
        'user_id' => auth()->id(),
        'tour_code' => $chosenTour['code'] ?? null,
        'tour_title' => $chosenTour['title'] ?? 'Tour theo yêu cầu',
        'start_date' => $chosenTour['start_date'] ?? null,
        'total_price' => $chosenTour['total_price'] ?? 0,

        'full_name' => $request->full_name,
        'phone' => $request->phone,
        'email' => $request->email,
        'note' => $request->note,

        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return redirect()->route('tour-booked')
        ->with('success', 'Đặt tour theo yêu cầu thành công!');
}


}
