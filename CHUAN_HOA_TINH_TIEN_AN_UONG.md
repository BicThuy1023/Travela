# 🔧 CHUẨN HÓA TÍNH TIỀN ĂN UỐNG - BUILD TOUR THEO YÊU CẦU

**Ngày sửa:** 2025-12-10  
**Mục tiêu:** Chuẩn hóa cách tính tiền ăn uống dựa trên số bữa thực tế (2N1Đ, 3N2Đ,...) và phân biệt bữa chuẩn vs bữa thêm

---

## 🐛 VẤN ĐỀ ĐÃ SỬA

### **1. Lỗi 500 Internal Server Error - Biến $hotelLevelLower chưa được định nghĩa**

**Vị trí:** `app/Http/Controllers/clients/BuildTourController.php` dòng ~775

**Nguyên nhân:**
- Sau khi refactor sang MealService, dòng `$hotelLevelLower = mb_strtolower($hotelLevelRaw);` bị xóa
- Nhưng biến `$hotelLevelLower` vẫn được sử dụng ở dòng 776-777 để kiểm tra `isUnknownHotelLvl`

**Giải pháp:**
```php
// Trước (LỖI):
$isUnknownHotelLvl = $hotelLevelRaw === '' ||
    str_contains($hotelLevelLower, 'chưa biết') ||  // $hotelLevelLower chưa được định nghĩa
    str_contains($hotelLevelLower, 'unknown');

// Sau (ĐÃ SỬA):
$hotelLevelLower = mb_strtolower($hotelLevelRaw ?? '');
$isUnknownHotelLvl = empty($hotelLevelRaw) ||
    str_contains($hotelLevelLower, 'chưa biết') ||
    str_contains($hotelLevelLower, 'unknown');
```

**Các thay đổi:**
1. Thêm dòng định nghĩa `$hotelLevelLower` trước khi sử dụng
2. Xử lý trường hợp `$hotelLevelRaw` null bằng `?? ''`
3. Đảm bảo `$hotelLevelRaw` có giá trị mặc định: `$hotelLevelRaw = $requestData['hotel_level'] ?? 'Chưa biết';`
4. Cập nhật `MealService` để xử lý null/empty an toàn

---

## ✅ CÁC THAY ĐỔI ĐÃ THỰC HIỆN

### **A. TÍNH GIÁ ĂN UỐNG BAN ĐẦU (DEFAULT)**

#### **1. Quy tắc số bữa chuẩn:**

**Pattern chuẩn:**
- **Ngày đầu:** trưa + tối
- **Ngày giữa (nếu có):** sáng + trưa + tối
- **Ngày cuối:** sáng

**Công thức tổng số bữa:**
```
totalMealsPerPerson = (days - 1) * 2 + 1
```

**Ví dụ:**
- Tour 2N1Đ (2 ngày): `(2-1)*2 + 1 = 3 bữa`
- Tour 3N2Đ (3 ngày): `(3-1)*2 + 1 = 5 bữa`
- Tour 4N3Đ (4 ngày): `(4-1)*2 + 1 = 7 bữa`

#### **2. Hàm mới trong MealService:**

**File:** `app/Services/MealService.php`

**Hàm `getStandardMealsForDay(int $dayId, int $totalDays): array`:**
- Xác định bữa chuẩn cho từng ngày
- Trả về: `['lunch', 'dinner']` (ngày đầu), `['breakfast', 'lunch', 'dinner']` (ngày giữa), `['breakfast']` (ngày cuối)

**Hàm `getTotalStandardMeals(int $days): int`:**
- Tính tổng số bữa chuẩn cho toàn tour
- Công thức: `(days - 1) * 2 + 1`

**Hàm `calculateDefaultFoodCost(string $hotelLevelRaw, int $days, int $numAdults, int $numChildren): int`:**
- Tính giá ăn uống mặc định dựa trên hạng KS và số ngày
- Xử lý an toàn trường hợp `$hotelLevelRaw` null hoặc rỗng
- Công thức:
  ```
  foodCostPerDay = 300k/250k/180k (từ hạng KS)
  totalMealsPerPerson = (days - 1) * 2 + 1
  baseMealCost = (foodCostPerDay * days) / totalMealsPerPerson
  foodCostPerPerson = baseMealCost * totalMealsPerPerson
  totalCost = foodCostPerPerson * adults + foodCostPerPerson * 0.7 * children
  ```

#### **3. Sửa BuildTourController:**

**File:** `app/Http/Controllers/clients/BuildTourController.php`

**Thay đổi trong `generateTourOptions()`:**
```php
// Cũ:
$foodCostPerDay = 300000; // hoặc 250000, 180000
$foodCostPerPerson = $foodCostPerDay * $days;

// Mới:
$mealService = new \App\Services\MealService();
$foodTotal = $mealService->calculateDefaultFoodCost($hotelLevelRaw, $days, $adults, $children);
$foodCostPerPerson = $totalPeopleFactor > 0 ? (int) round($foodTotal / $totalPeopleFactor / 1000) * 1000 : 0;
```

**Sửa lỗi `$hotelLevelLower`:**
```php
// Đảm bảo $hotelLevelRaw có giá trị mặc định
$hotelLevelRaw = $requestData['hotel_level'] ?? 'Chưa biết';

// Tạo $hotelLevelLower trước khi sử dụng
$hotelLevelLower = mb_strtolower($hotelLevelRaw ?? '');
$isUnknownHotelLvl = empty($hotelLevelRaw) ||
    str_contains($hotelLevelLower, 'chưa biết') ||
    str_contains($hotelLevelLower, 'unknown');
```

---

### **B. TÍNH GIÁ KHI KHÁCH CHỈNH SỬA MEAL PLAN**

#### **1. Hàm `calculateCustomMealCost()`:**

**File:** `app/Services/MealService.php`

**Logic:**
1. Tính `foodCostPerDay` từ hạng KS (300k/250k/180k) - xử lý null/empty an toàn
2. Tính `totalMealsPerPerson` và `baseMealCost`
3. Với mỗi ngày:
   - Xác định bữa chuẩn (`getStandardMealsForDay()`)
   - Với mỗi bữa trong meal plan:
     - Nếu `self_pay = true` → bỏ qua (không tính tiền)
     - Lấy `multiplier` từ level (budget: 0.8, standard: 1.0, premium: 1.5)
     - Tính: `mealCostPerPerson = baseMealCost * multiplier`
     - Kiểm tra: bữa chuẩn hay bữa thêm?
       - **Bữa chuẩn:** Cộng vào tổng (đã bao gồm trong giá mặc định, chỉ điều chỉnh theo multiplier)
       - **Bữa thêm:** Cộng thêm vào tổng (vì mặc định không có bữa này)
4. Trả về tổng chi phí

---

### **C. NOTE / MÔ TẢ RÕ RÀNG CHO TỪNG BỮA**

#### **1. Sửa `generateMealDescription()`:**

**File:** `app/Services/MealService.php`

**Thay đổi:**
- Thêm tham số `$dayId` và `$totalDays`
- Kiểm tra `isExtraMeal()` để phân biệt bữa chuẩn vs bữa thêm
- Trả về mô tả khác nhau:
  - **Bữa chuẩn:** "... Đã bao gồm trong giá tour."
  - **Bữa thêm:** "... Bữa ăn tùy chọn (+ tính thêm tiền nếu khách chọn)."
  - **Self_pay:** "... Chi phí tự túc, không bao gồm trong giá tour."

#### **2. Sửa view hiển thị lịch trình:**

**File:** `resources/views/clients/build_tour_option_detail.blade.php`

**Thay đổi:**
- Gọi `generateMealDescription()` với đúng tham số (`$dayId`, `$totalDays`)
- Chỉ hiển thị bữa có trong meal plan hoặc là bữa chuẩn

#### **3. Sửa modal chỉnh sửa:**

**File:** `resources/views/clients/build_tour_option_detail.blade.php`

**Thay đổi:**
- Hiển thị badge cho từng bữa:
  - **Bữa chuẩn:** Badge xanh "Đã bao gồm" với icon check
  - **Bữa thêm:** Badge vàng "Bữa thêm" với icon info
- Tooltip giải thích rõ ràng
- **Bữa thêm mặc định tự túc:** Khi bữa là bữa thêm và chưa có trong meal_plan, mặc định checkbox "Tự túc" sẽ được check (self_pay = true)
- **Khách có thể chọn lại:** Khách có thể bỏ check "Tự túc" để bao gồm bữa thêm vào giá tour

---

## 📊 VÍ DỤ TÍNH TOÁN

### **Tour 3N2Đ, 2 người lớn, 1 trẻ em, hạng KS 3-4 sao:**

**1. Tính giá mặc định:**
- `foodCostPerDay = 250,000đ`
- `days = 3`
- `totalMealsPerPerson = (3-1)*2 + 1 = 5 bữa`
- `baseMealCost = (250,000 * 3) / 5 = 150,000đ/bữa`
- `foodCostPerPerson = 150,000 * 5 = 750,000đ`
- `totalCost = 750,000 * 2 + 750,000 * 0.7 * 1 = 2,025,000đ`

**2. Khi khách chỉnh meal plan:**
- Ngày 1: trưa (standard), tối (premium)
- Ngày 2: sáng (standard), trưa (budget), tối (standard)
- Ngày 3: sáng (standard)
- Thêm: trưa ngày 3 (premium) - **bữa thêm**

**Tính toán:**
- Bữa chuẩn (5 bữa):
  - Ngày 1 trưa: `150,000 * 1.0 = 150,000đ`
  - Ngày 1 tối: `150,000 * 1.5 = 225,000đ`
  - Ngày 2 sáng: `150,000 * 1.0 = 150,000đ`
  - Ngày 2 trưa: `150,000 * 0.8 = 120,000đ`
  - Ngày 2 tối: `150,000 * 1.0 = 150,000đ`
  - Ngày 3 sáng: `150,000 * 1.0 = 150,000đ`
  - Tổng bữa chuẩn: `945,000đ/người`
- Bữa thêm (1 bữa):
  - Ngày 3 trưa: `150,000 * 1.5 = 225,000đ/người`
- Tổng: `(945,000 + 225,000) * 2 + (945,000 + 225,000) * 0.7 * 1 = 2,574,000đ`

**So sánh:**
- Giá mặc định: `2,025,000đ`
- Giá sau chỉnh: `2,574,000đ`
- Chênh lệch: `+549,000đ` (do có bữa thêm premium và một số bữa premium)

---

## 🔍 KIỂM TRA SAU KHI SỬA

### **1. Lỗi 500 đã được sửa:**
- [x] `$hotelLevelLower` được định nghĩa trước khi sử dụng
- [x] `$hotelLevelRaw` có giá trị mặc định
- [x] `MealService` xử lý null/empty an toàn

### **2. Tính giá mặc định:**
- [x] Tour 2N1Đ → 3 bữa
- [x] Tour 3N2Đ → 5 bữa
- [x] Tour 4N3Đ → 7 bữa
- [x] Giá tương đương logic cũ (sai số < 10%)

### **3. Tính giá khi chỉnh meal plan:**
- [x] Bữa chuẩn = Tiêu chuẩn → giá ≈ giá mặc định
- [x] Bữa chuẩn = Bình dân → giá giảm
- [x] Bữa chuẩn = Cao cấp → giá tăng (≤ 1.5x)
- [x] Thêm bữa thêm → giá tăng thêm đúng 1 bữa
- [x] Self_pay → không tính tiền

### **4. Hiển thị note:**
- [x] Bữa chuẩn: "Đã bao gồm trong giá tour"
- [x] Bữa thêm: "Bữa ăn tùy chọn (+ tính thêm tiền nếu khách chọn)"
- [x] Self_pay: "Chi phí tự túc, không bao gồm trong giá tour"
- [x] Modal có badge rõ ràng
- [x] **Bữa thêm mặc định tự túc:** Khi mở modal, bữa thêm (chưa có trong meal_plan) tự động được check "Tự túc"
- [x] **Khách có thể chọn lại:** Khách có thể bỏ check "Tự túc" để bao gồm bữa thêm vào giá tour

---

## 📋 FILES ĐÃ SỬA

1. **`app/Services/MealService.php`**
   - Thêm `getStandardMealsForDay()`
   - Thêm `getTotalStandardMeals()`
   - Thêm `calculateDefaultFoodCost()` - xử lý null/empty an toàn
   - Thêm `calculateCustomMealCost()` - xử lý null/empty an toàn
   - Thêm `isExtraMeal()`
   - Sửa `generateMealDescription()`

2. **`app/Http/Controllers/clients/BuildTourController.php`**
   - Sửa `generateTourOptions()` để dùng `calculateDefaultFoodCost()`
   - **Sửa lỗi `$hotelLevelLower` chưa được định nghĩa** (dòng ~775)
   - Đảm bảo `$hotelLevelRaw` có giá trị mặc định
   - Sửa `updateMeals()` để dùng `calculateCustomMealCost()`

3. **`resources/views/clients/build_tour_option_detail.blade.php`**
   - Sửa hiển thị mô tả ăn uống trong lịch trình
   - Sửa modal để hiển thị badge "Đã bao gồm" / "Bữa thêm"
   - **Bữa thêm mặc định tự túc:** Bữa thêm (chưa có trong meal_plan) mặc định được set `self_pay = true`
   - **Khách có thể chọn lại:** Khách có thể bỏ check "Tự túc" để bao gồm bữa thêm vào giá tour
   - **Ảnh gallery theo tỉnh thành:** Mỗi tỉnh thành sẽ có ảnh đầu tiên khác nhau trong gallery
     - Lấy tỉnh thành đầu tiên từ `main_destinations`
     - Mapping tỉnh thành -> tên file ảnh (ví dụ: Hà Nội -> hanoi-1.jpg)
     - Nếu không có ảnh tương ứng, fallback về ảnh mặc định (custom-1.jpg)

---

## ⚠️ LƯU Ý

1. **Xử lý null/empty:**
   - Tất cả hàm trong `MealService` đều xử lý trường hợp `$hotelLevelRaw` null hoặc rỗng
   - `BuildTourController` đảm bảo `$hotelLevelRaw` có giá trị mặc định "Chưa biết"

2. **Làm tròn:**
   - Tất cả giá trị được làm tròn đến hàng nghìn

3. **Bữa thêm:**
   - Hiện tại logic tính: bữa thêm cũng cộng vào tổng như bữa chuẩn
   - **Mặc định tự túc:** Bữa thêm (chưa có trong meal_plan) mặc định được set `self_pay = true` (tự túc)
   - **Khách có thể chọn lại:** Khách có thể bỏ check "Tự túc" để bao gồm bữa thêm vào giá tour
   - Có thể điều chỉnh sau nếu cần logic khác (ví dụ: bữa thêm có phụ phí riêng)

---

## 🖼️ TÍNH NĂNG MỚI: ẢNH GALLERY THEO TỈNH THÀNH

**Ngày thêm:** 2025-12-10

### **Mô tả:**
Mỗi tỉnh thành sẽ có ảnh đầu tiên khác nhau trong gallery của trang chi tiết tour (`/build-tour/detail/{id}`).

### **Cách hoạt động:**

1. **Lấy tỉnh thành:**
   - Lấy tỉnh thành đầu tiên từ `$requestData['main_destinations']`
   - Ví dụ: `['Hà Nội', 'Hạ Long']` → lấy "Hà Nội"

2. **Mapping tỉnh thành -> ảnh:**
   - Tạo mapping các tỉnh thành phổ biến với tên file ảnh
   - Ví dụ:
     - Hà Nội → `hanoi-1.jpg`
     - Hồ Chí Minh → `hochiminh-1.jpg`
     - Đà Nẵng → `danang-1.jpg`
     - Hạ Long → `halong-1.jpg`
     - Hội An → `hoian-1.jpg`
     - Huế → `hue-1.jpg`
     - Nha Trang → `nhatrang-1.jpg`
     - Phú Quốc → `phuquoc-1.jpg`
     - Sapa → `sapa-1.jpg`
     - ...

3. **Hiển thị ảnh:**
   - **Ảnh đầu tiên:** Theo tỉnh thành (nếu có file) hoặc ảnh mặc định (`custom-1.jpg`)
   - **Ảnh 2, 3:** Luôn dùng ảnh mặc định (`custom-2.jpg`, `custom-3.jpg`)

4. **Fallback:**
   - Nếu file ảnh tỉnh thành không tồn tại → dùng `custom-1.jpg`
   - Nếu không có tỉnh thành → dùng `custom-1.jpg`

### **Cấu trúc file ảnh:**
```
public/clients/assets/images/custom-tour/
├── custom-1.jpg      (Ảnh mặc định - fallback)
├── custom-2.jpg      (Ảnh mặc định - luôn dùng)
├── custom-3.jpg      (Ảnh mặc định - luôn dùng)
├── hanoi-1.jpg       (Ảnh Hà Nội - nếu có)
├── hochiminh-1.jpg   (Ảnh Hồ Chí Minh - nếu có)
├── danang-1.jpg      (Ảnh Đà Nẵng - nếu có)
├── halong-1.jpg      (Ảnh Hạ Long - nếu có)
└── ...
```

### **Cách thêm ảnh mới cho tỉnh thành:**
1. Đặt file ảnh vào `public/clients/assets/images/custom-tour/`
2. Đặt tên theo format: `{tên-tỉnh-thành}-1.jpg` (lowercase, không dấu, không space)
   - Ví dụ: `hanoi-1.jpg`, `hochiminh-1.jpg`, `danang-1.jpg`
3. Thêm mapping vào `$destinationImageMap` trong view `build_tour_option_detail.blade.php`:
   ```php
   'tên tỉnh thành' => 'tên-file-không-extension',
   ```

### **Code đã sửa:**

**File:** `resources/views/clients/build_tour_option_detail.blade.php`

```php
// Lấy tỉnh thành đầu tiên
$mainDestinations = $requestData['main_destinations'] ?? [];
$firstDestination = !empty($mainDestinations) ? $mainDestinations[0] : '';

// Mapping tỉnh thành -> tên file ảnh
$destinationImageMap = [
    'hà nội' => 'hanoi',
    'hồ chí minh' => 'hochiminh',
    'đà nẵng' => 'danang',
    // ...
];

// Tìm ảnh tương ứng
$imagePrefix = 'custom'; // Mặc định
$normalizedDestination = mb_strtolower(trim($firstDestination));
foreach ($destinationImageMap as $key => $value) {
    if (str_contains($normalizedDestination, $key) || str_contains($key, $normalizedDestination)) {
        $imagePrefix = $value;
        break;
    }
}

// Kiểm tra file tồn tại
$customImagePath = public_path("clients/assets/images/custom-tour/{$imagePrefix}-1.jpg");
$firstImage = file_exists($customImagePath) 
    ? asset("clients/assets/images/custom-tour/{$imagePrefix}-1.jpg")
    : asset('clients/assets/images/custom-tour/custom-1.jpg');

// Gallery images
$galleryImages = [
    $firstImage, // Ảnh đầu theo tỉnh thành
    asset('clients/assets/images/custom-tour/custom-2.jpg'),
    asset('clients/assets/images/custom-tour/custom-3.jpg'),
];
```

---

**Tài liệu được cập nhật lần cuối:** 2025-12-10
