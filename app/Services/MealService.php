<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class MealService
{
    /**
     * Xác định số bữa chuẩn cho một ngày trong tour
     * 
     * Quy tắc:
     * - Ngày đầu: trưa + tối
     * - Ngày giữa (nếu có): sáng + trưa + tối
     * - Ngày cuối: sáng
     * 
     * @param int $dayId Số thứ tự ngày (1, 2, 3, ...)
     * @param int $totalDays Tổng số ngày tour
     * @return array Danh sách meal type chuẩn ['lunch', 'dinner'] hoặc ['breakfast', 'lunch', 'dinner'] hoặc ['breakfast']
     */
    public function getStandardMealsForDay(int $dayId, int $totalDays): array
    {
        if ($dayId == 1) {
            // Ngày đầu: trưa + tối
            return ['lunch', 'dinner'];
        } elseif ($dayId == $totalDays) {
            // Ngày cuối: sáng
            return ['breakfast'];
        } else {
            // Ngày giữa: sáng + trưa + tối
            return ['breakfast', 'lunch', 'dinner'];
        }
    }

    /**
     * Tính tổng số bữa chuẩn cho toàn tour
     * 
     * Công thức: totalMealsPerPerson = (days - 1) * 2 + 1
     * 
     * @param int $days Tổng số ngày
     * @return int Tổng số bữa
     */
    public function getTotalStandardMeals(int $days): int
    {
        return ($days - 1) * 2 + 1;
    }

    /**
     * Tính giá ăn uống mặc định (default) dựa trên hạng khách sạn và số ngày
     * 
     * @param string $hotelLevelRaw Hạng khách sạn (vd: "Resort / 4-5 sao")
     * @param int $days Tổng số ngày tour
     * @param int $numAdults Số người lớn
     * @param int $numChildren Số trẻ em
     * @return int Tổng chi phí ăn uống mặc định (VNĐ)
     */
    public function calculateDefaultFoodCost(string $hotelLevelRaw, int $days, int $numAdults, int $numChildren): int
    {
        // Tính foodCostPerDay từ hạng khách sạn
        // Xử lý trường hợp hotelLevelRaw null hoặc rỗng
        $hotelLevelRaw = $hotelLevelRaw ?? '';
        $hotelLevelLower = mb_strtolower($hotelLevelRaw);
        
        if (str_contains($hotelLevelLower, 'resort') || str_contains($hotelLevelLower, '4-5') || str_contains($hotelLevelLower, '5')) {
            $foodCostPerDay = 300000;   // resort / 4-5 sao
        } elseif (str_contains($hotelLevelLower, '3-4') || str_contains($hotelLevelLower, '4') || str_contains($hotelLevelLower, '3')) {
            $foodCostPerDay = 250000;   // 3-4 sao
        } else {
            $foodCostPerDay = 180000;   // 1-2 sao / nhà nghỉ
        }

        // Tính tổng số bữa chuẩn
        $totalMealsPerPerson = $this->getTotalStandardMeals($days);

        // Tính baseMealCost: (foodCostPerDay * days) / totalMealsPerPerson
        $baseMealCost = (int) round(($foodCostPerDay * $days) / $totalMealsPerPerson / 1000) * 1000;

        // Tính giá ăn uống mặc định / người (tất cả bữa chuẩn = Tiêu chuẩn, multiplier = 1.0)
        $foodCostPerPerson = $baseMealCost * $totalMealsPerPerson;

        // Tính tổng cho cả đoàn
        $childFactor = 0.7; // Trẻ em = 70% giá người lớn
        $adultCost = $foodCostPerPerson * $numAdults;
        $childCost = (int) round($foodCostPerPerson * $childFactor * $numChildren / 1000) * 1000;

        return (int) round(($adultCost + $childCost) / 1000) * 1000;
    }

    /**
     * Tính giá ăn uống khi khách chỉnh sửa meal plan
     * 
     * Phân biệt:
     * - Bữa chuẩn (đã bao gồm): dùng multiplier, không cộng thêm
     * - Bữa thêm (tùy chọn): cộng thêm tiền
     * - Self_pay: không tính tiền
     * 
     * @param array $mealPlanPerDay Cấu trúc: meal_plan[day_id][breakfast|lunch|dinner][level|type|self_pay]
     * @param int $days Tổng số ngày tour
     * @param int $numAdults Số người lớn
     * @param int $numChildren Số trẻ em
     * @param string $hotelLevelRaw Hạng khách sạn
     * @return int Tổng chi phí ăn uống tùy chỉnh (VNĐ)
     */
    public function calculateCustomMealCost(array $mealPlanPerDay, int $days, int $numAdults, int $numChildren, string $hotelLevelRaw): int
    {
        // Tính foodCostPerDay từ hạng khách sạn
        // Xử lý trường hợp hotelLevelRaw null hoặc rỗng
        $hotelLevelRaw = $hotelLevelRaw ?? '';
        $hotelLevelLower = mb_strtolower($hotelLevelRaw);
        
        if (str_contains($hotelLevelLower, 'resort') || str_contains($hotelLevelLower, '4-5') || str_contains($hotelLevelLower, '5')) {
            $foodCostPerDay = 300000;
        } elseif (str_contains($hotelLevelLower, '3-4') || str_contains($hotelLevelLower, '4') || str_contains($hotelLevelLower, '3')) {
            $foodCostPerDay = 250000;
        } else {
            $foodCostPerDay = 180000;
        }

        // Tính tổng số bữa chuẩn và baseMealCost
        $totalMealsPerPerson = $this->getTotalStandardMeals($days);
        $baseMealCost = (int) round(($foodCostPerDay * $days) / $totalMealsPerPerson / 1000) * 1000;

        $mealConfig = Config::get('meals.levels');
        $childFactor = 0.7;
        $totalCost = 0;

        // Tính chi phí cho từng ngày
        for ($dayId = 1; $dayId <= $days; $dayId++) {
            if (!isset($mealPlanPerDay[$dayId])) {
                continue;
            }

            $dayMeals = $mealPlanPerDay[$dayId];
            $standardMeals = $this->getStandardMealsForDay($dayId, $days);

            // Tính chi phí cho từng bữa trong ngày
            foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
                if (!isset($dayMeals[$mealType])) {
                    continue;
                }

                $meal = $dayMeals[$mealType];

                // Nếu tự túc (self_pay = true) thì bỏ qua
                if (isset($meal['self_pay']) && $meal['self_pay'] === true) {
                    continue;
                }

                // Lấy mức ăn và multiplier
                $level = $meal['level'] ?? 'standard';
                $multiplier = $mealConfig[$level]['multiplier'] ?? 1.0;

                // Tính giá cho 1 bữa/người: baseMealCost * multiplier
                $mealCostPerPerson = (int) round($baseMealCost * $multiplier / 1000) * 1000;

                // Kiểm tra xem bữa này có phải bữa thêm không
                $isExtraMeal = !in_array($mealType, $standardMeals);

                // Tính giá cho cả người lớn + trẻ em
                $adultCost = $mealCostPerPerson * $numAdults;
                $childCost = (int) round($mealCostPerPerson * $childFactor * $numChildren / 1000) * 1000;
                
                $mealTotal = $adultCost + $childCost;

                // Nếu là bữa chuẩn: cộng vào tổng (đã bao gồm trong giá mặc định, chỉ điều chỉnh theo multiplier)
                // Nếu là bữa thêm: cộng thêm vào tổng (vì mặc định không có bữa này)
                $totalCost += $mealTotal;
            }
        }

        return (int) round($totalCost / 1000) * 1000;
    }

    /**
     * Kiểm tra bữa có phải bữa thêm (ngoài pattern chuẩn) không
     * 
     * @param int $dayId Số thứ tự ngày
     * @param string $mealType breakfast|lunch|dinner
     * @param int $totalDays Tổng số ngày tour
     * @return bool true nếu là bữa thêm, false nếu là bữa chuẩn
     */
    public function isExtraMeal(int $dayId, string $mealType, int $totalDays): bool
    {
        $standardMeals = $this->getStandardMealsForDay($dayId, $totalDays);
        return !in_array($mealType, $standardMeals);
    }
    /**
     * Tính tổng chi phí ăn uống dựa trên meal plan
     * 
     * Thuật toán mới: Dùng multiplier và baseMealCost từ hạng khách sạn
     * - baseMealCost = foodCostPerDay / số_bữa_trong_ngày
     * - mealCostPerPerson = baseMealCost * multiplier(level)
     * - Nếu self_pay = true → cost = 0
     *
     * @param array $mealPlan Cấu trúc: meal_plan[day_id][breakfast|lunch|dinner][level|type|self_pay]
     * @param int $adults Số người lớn
     * @param int $children Số trẻ em
     * @param int $foodCostPerDay Chi phí ăn uống/ngày/người từ hạng khách sạn (VNĐ)
     * @param int $days Tổng số ngày tour
     * @return int Tổng chi phí ăn uống (VNĐ)
     */
    public function calculateMealCost(array $mealPlan, int $adults, int $children, int $foodCostPerDay, int $days): int
    {
        $totalCost = 0;
        $mealConfig = Config::get('meals.levels');
        $childFactor = 0.7; // Trẻ em = 70% giá người lớn

        // Tính chi phí cho từng ngày
        for ($dayId = 1; $dayId <= $days; $dayId++) {
            if (!isset($mealPlan[$dayId])) {
                continue;
            }

            $dayMeals = $mealPlan[$dayId];
            
            // Đếm số bữa thực sự có trong ngày (không tính self_pay)
            $mealCount = 0;
            foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
                if (isset($dayMeals[$mealType]) && !($dayMeals[$mealType]['self_pay'] ?? false)) {
                    $mealCount++;
                }
            }

            // Nếu không có bữa nào (tất cả tự túc) hoặc không có meal plan cho ngày này
            if ($mealCount === 0) {
                continue;
            }

            // Tính baseMealCost cho ngày này: foodCostPerDay / số_bữa_trong_ngày
            $baseMealCostPerPerson = (int) round($foodCostPerDay / $mealCount / 1000) * 1000;

            // Tính chi phí cho từng bữa trong ngày
            foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
                if (!isset($dayMeals[$mealType])) {
                    continue;
                }

                $meal = $dayMeals[$mealType];

                // Nếu tự túc (self_pay = true) thì bỏ qua
                if (isset($meal['self_pay']) && $meal['self_pay'] === true) {
                    continue;
                }

                // Lấy mức ăn và multiplier
                $level = $meal['level'] ?? 'standard';
                $multiplier = $mealConfig[$level]['multiplier'] ?? 1.0;

                // Tính giá cho 1 bữa/người: baseMealCost * multiplier
                $mealCostPerPerson = (int) round($baseMealCostPerPerson * $multiplier / 1000) * 1000;

                // Tính giá cho cả người lớn + trẻ em
                $adultCost = $mealCostPerPerson * $adults;
                $childCost = (int) round($mealCostPerPerson * $childFactor * $children / 1000) * 1000;
                
                $totalCost += $adultCost + $childCost;
            }
        }

        return (int) round($totalCost / 1000) * 1000; // Làm tròn đến hàng nghìn
    }

    /**
     * Tính chi phí ăn uống cũ (từ food_per_person trong price_breakdown)
     *
     * @param array $priceBreakdown
     * @param int $adults
     * @param int $children
     * @return int
     */
    public function calculateOldMealCost(array $priceBreakdown, int $adults, int $children): int
    {
        $foodPerPerson = (int) ($priceBreakdown['food_per_person'] ?? 0);
        $childFactor = 0.7;

        $adultCost = $foodPerPerson * $adults;
        $childCost = (int) round($foodPerPerson * $childFactor * $children / 1000) * 1000;

        return $adultCost + $childCost;
    }

    /**
     * Tạo mô tả ăn uống cho một bữa
     *
     * @param array $meal Dữ liệu bữa ăn [level, type, self_pay]
     * @param string $mealType breakfast|lunch|dinner
     * @param int $dayId Số thứ tự ngày
     * @param int $totalDays Tổng số ngày tour
     * @param int $pricePerPerson Giá/người (tùy chọn)
     * @return string Mô tả
     */
    public function generateMealDescription(array $meal, string $mealType, int $dayId, int $totalDays, int $pricePerPerson = 0): string
    {
        $levelLabels = Config::get('meals.levels');
        $typeLabels = Config::get('meals.types');

        $timeLabel = '';
        if ($mealType === 'breakfast') {
            $timeLabel = 'Buổi sáng';
        } elseif ($mealType === 'lunch') {
            $timeLabel = 'Buổi trưa';
        } elseif ($mealType === 'dinner') {
            $timeLabel = 'Buổi tối';
        }

        // Nếu tự túc
        if (isset($meal['self_pay']) && $meal['self_pay'] === true) {
            return "{$timeLabel}: Chi phí tự túc, không bao gồm trong giá tour.";
        }

        // Kiểm tra xem bữa này có phải bữa thêm không
        $isExtraMeal = $this->isExtraMeal($dayId, $mealType, $totalDays);

        // Lấy thông tin level và type
        $level = $meal['level'] ?? 'standard';
        $type = $meal['type'] ?? 'restaurant';

        $levelLabel = $levelLabels[$level]['label'] ?? 'Tiêu chuẩn';
        $typeLabel = $typeLabels[$type] ?? 'Nhà hàng';

        $priceText = '';
        if ($pricePerPerson > 0) {
            $priceText = number_format($pricePerPerson, 0, ',', '.') . 'đ/khách';
        }

        // Nếu là bữa thêm (tùy chọn)
        if ($isExtraMeal) {
            return "{$timeLabel}: Dùng bữa tại {$typeLabel} với gói {$levelLabel}" . 
                   ($priceText ? ", dự kiến {$priceText}" : '') . 
                   ". Bữa ăn tùy chọn (+ tính thêm tiền nếu khách chọn).";
        }

        // Nếu là bữa chuẩn (đã bao gồm)
        return "{$timeLabel}: Dùng bữa tại {$typeLabel} với gói {$levelLabel}" . 
               ($priceText ? ", dự kiến {$priceText}" : '') . 
               ". Đã bao gồm trong giá tour.";
    }

    /**
     * Validate meal plan structure
     *
     * @param array $mealPlan
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateMealPlan(array $mealPlan): array
    {
        $errors = [];
        $validLevels = array_keys(Config::get('meals.levels'));
        $validTypes = array_keys(Config::get('meals.types'));
        $validMeals = ['breakfast', 'lunch', 'dinner'];

        foreach ($mealPlan as $dayId => $dayMeals) {
            foreach ($validMeals as $mealType) {
                if (!isset($dayMeals[$mealType])) {
                    continue;
                }

                $meal = $dayMeals[$mealType];

                // Validate level
                if (isset($meal['level']) && !in_array($meal['level'], $validLevels)) {
                    $errors[] = "Ngày {$dayId}, {$mealType}: Mức ăn không hợp lệ";
                }

                // Validate type
                if (isset($meal['type']) && !in_array($meal['type'], $validTypes)) {
                    $errors[] = "Ngày {$dayId}, {$mealType}: Hình thức không hợp lệ";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

