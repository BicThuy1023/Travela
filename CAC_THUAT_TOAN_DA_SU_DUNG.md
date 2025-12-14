# CÁC THUẬT TOÁN ĐÃ SỬ DỤNG TRONG ĐỀ TÀI

## Tổng quan

Đề tài "Xây dựng website đặt tour du lịch tích hợp AI" đã áp dụng nhiều thuật toán khác nhau để xử lý các bài toán như gợi ý tour, tính giá tour, tạo lịch trình, và tìm kiếm. Dưới đây là chi tiết các thuật toán đã được triển khai.

---

## 1. THUẬT TOÁN GỢI Ý TOUR (RECOMMENDATION ALGORITHMS)

### 1.1. Content-Based Filtering (Lọc dựa trên nội dung)

**Mục đích**: Tìm tour tương tự với tour đang xem dựa trên đặc điểm của tour.

**Thuật toán**:
- So sánh **destination** (40% trọng số): Tìm tour có điểm đến tương tự bằng cách phân tách từ khóa và so sánh LIKE
- So sánh **price range** (30% trọng số): Tìm tour có giá trong khoảng ±30% giá tour hiện tại
  ```
  priceMin = currentPrice × 0.7
  priceMax = currentPrice × 1.3
  ```
- So sánh **domain** (10% trọng số): Tìm tour cùng khu vực (Bắc/Trung/Nam)

**Vị trí sử dụng**: 
- File: `app/Services/RecommendationService.php` - Method `getSimilarTours()`
- Trang chi tiết tour - Section "Similar Tours"

**Độ phức tạp**: O(n) với n là số lượng tour trong database

---

### 1.2. Trending Score Algorithm (Thuật toán tính điểm trending)

**Mục đích**: Xác định tour đang hot, được quan tâm nhiều nhất.

**Công thức tính điểm**:
```
Trending Score = (Số booking × 2) + Số lượt xem
```

**Giải thích**:
- Booking có trọng số gấp đôi lượt xem vì booking thể hiện sự quan tâm thực sự
- Sử dụng phép nhân để ưu tiên tour có nhiều booking

**Vị trí sử dụng**:
- File: `app/Services/RecommendationService.php` - Method `getTrendingTours()`
- Homepage - Section "Trending Tours"
- Tours List - Sidebar "Trending tours"

**SQL Query**:
```sql
SELECT *, (COUNT(DISTINCT bookingId) * 2 + COALESCE(views, 0)) as trending_score
FROM tbl_tours
LEFT JOIN tbl_booking ON tbl_tours.tourId = tbl_booking.tourId
GROUP BY tourId
ORDER BY trending_score DESC
LIMIT 6
```

**Độ phức tạp**: O(n log n) do cần JOIN và ORDER BY

---

### 1.3. User-Based Filtering (Lọc dựa trên người dùng)

**Mục đích**: Gợi ý tour cá nhân hóa cho từng user dựa trên lịch sử booking.

**Thuật toán**:
1. Lấy **tour đã đặt gần nhất** của user
2. Phân tích đặc điểm tour đó:
   - Destination (điểm đến)
   - Domain (khu vực: Bắc/Trung/Nam)
3. Tìm tour tương tự (không trùng tour đã đặt) dựa trên:
   - Cùng destination (phân tách keyword và so sánh LIKE)
   - Cùng domain
4. Trả về top 6 tour phù hợp

**Vị trí sử dụng**:
- File: `app/Services/RecommendationService.php` - Method `getUserRecommendations()`
- Homepage - Section "Recommended For You" (chỉ hiện khi user đã đăng nhập)

**Độ phức tạp**: O(n) với n là số lượng tour trong database

**Lưu ý**: Hệ thống ưu tiên gọi API Python Machine Learning, chỉ fallback về thuật toán này khi API không khả dụng.

---

### 1.4. Machine Learning Recommendations (API Python)

**Mục đích**: Sử dụng mô hình Machine Learning để gợi ý tour chính xác hơn.

**Cơ chế**:
- Gọi API Python tại endpoint: `http://127.0.0.1:5555/api/tour-recommendations`
- Model ML phân tích dữ liệu booking, reviews, user preferences
- Trả về danh sách tour được gợi ý dựa trên các thuật toán ML (có thể là Collaborative Filtering, Matrix Factorization, hoặc Deep Learning)

**Fallback**: Khi API không khả dụng, tự động chuyển về các thuật toán filtering trên PHP

**Vị trí sử dụng**: 
- Tất cả các method trong `RecommendationService`
- Timeout: 2 giây để đảm bảo không block quá lâu

---

## 2. THUẬT TOÁN TẠO LỊCH TRÌNH TOUR (ITINERARY GENERATION ALGORITHM)

### 2.1. Place Matching Algorithm (Thuật toán ghép điểm tham quan)

**Mục đích**: Tự động ghép các điểm tham quan vào lịch trình tour dựa trên yêu cầu.

**Thuật toán**:
1. **Ưu tiên điểm bắt buộc (must-visit places)**:
   - Đưa các điểm "must visit" vào trước
   - Kiểm tra thời gian (duration) để đảm bảo không vượt quá giới hạn mỗi ngày
   - Giới hạn: 5-8 giờ/ngày (minHoursPerDay = 5, maxHoursPerDay = 8)

2. **Thêm điểm phụ theo destination**:
   - Sau khi đã xếp các điểm bắt buộc
   - Lấy các điểm cùng destination để điền vào thời gian còn lại
   - Ưu tiên điểm có trong danh sách must-visit

3. **Phân loại điểm tham quan**:
   - **Mandatory activities**: Điểm tham quan chính (avgCost < 600,000 VNĐ hoặc không phải giải trí)
   - **Optional activities**: Hoạt động tự túc (avgCost >= 600,000 VNĐ hoặc category là "giải trí", "vui chơi", "show")
     - Optional activities không được cộng vào giá tour, chỉ để tham khảo

**Vị trí sử dụng**:
- File: `app/Http/Controllers/clients/BuildTourController.php` - Method `generateTourOptions()`

**Độ phức tạp**: O(n × m) với n là số ngày, m là số điểm tham quan

---

### 2.2. Day Planning Algorithm (Thuật toán sắp xếp theo ngày)

**Mục đích**: Chia lịch trình thành các ngày với mô tả chi tiết.

**Thuật toán**:
1. Chia điểm tham quan theo buổi trong ngày:
   - Buổi sáng: Điểm đầu tiên
   - Buổi chiều: Các điểm ở giữa
   - Buổi tối: Điểm cuối cùng

2. Tạo mô tả động:
   - Ngày 1: "Buổi sáng, đoàn tập trung tại điểm hẹn, khởi hành đến [điểm đến]. Đến nơi, nhận phòng khách sạn..."
   - Ngày cuối: "Buổi sáng, quý khách tự do tham quan, mua sắm đặc sản. Đến giờ hẹn, đoàn làm thủ tục trả phòng..."
   - Ngày giữa: "Tiếp tục hành trình khám phá [điểm đến]..."

3. Điều chỉnh theo cường độ (intensity):
   - **Nhẹ**: "Lịch trình được sắp xếp nhẹ nhàng, phù hợp gia đình có trẻ nhỏ..."
   - **Vừa**: "Lịch trình cân bằng giữa tham quan và nghỉ ngơi..."
   - **Dày**: "Lịch trình dày, đi được nhiều điểm trong ngày..."

**Vị trí sử dụng**: 
- Method `generateTourOptions()` trong BuildTourController

---

## 3. THUẬT TOÁN TÍNH GIÁ TOUR (PRICING ALGORITHMS)

### 3.1. Core Cost Calculation (Tính chi phí cơ bản)

**Công thức**:
```
Core Cost Per Person = 
    Mandatory Activities Cost +
    Food Cost Per Person +
    Transport Cost Per Person +
    Hotel Cost Per Person
```

**Chi tiết**:
- **Mandatory Activities Cost**: Tổng chi phí các điểm tham quan bắt buộc
  - Áp dụng hệ số `placeFactor` dựa trên số điểm bắt buộc:
    - ≤ 2 điểm: × 0.9 (rẻ hơn 10%)
    - 3-4 điểm: × 1.0 (giữ nguyên)
    - ≥ 5 điểm: × 1.1 (đắt hơn 10%)

- **Food Cost Per Day**: Phụ thuộc vào hạng khách sạn
  - Resort/4-5 sao: 300,000 VNĐ/ngày
  - 3-4 sao: 250,000 VNĐ/ngày
  - 1-2 sao: 180,000 VNĐ/ngày
  - `Food Cost Per Person = Food Cost Per Day × Số ngày`

- **Transport Cost**: Di chuyển nội bộ
  ```
  Transport Cost = 120,000 + max(0, (số ngày - 2) × 40,000)
  ```

- **Hotel Cost**: Xem mục 3.2

**Vị trí sử dụng**: BuildTourController - `generateTourOptions()`

---

### 3.2. Hotel Cost Estimation Algorithm (Ước lượng chi phí khách sạn)

**Mục đích**: Tính chi phí khách sạn/người/đêm dựa trên hạng khách sạn.

**Bảng giá theo hạng**:
```
Resort/5 sao:       700,000 VNĐ/người/đêm
4-5 sao:            550,000 VNĐ/người/đêm
3-4 sao:            400,000 VNĐ/người/đêm
1-2 sao/Nhà nghỉ:   280,000 VNĐ/người/đêm
```

**Công thức**:
```
Hotel Cost Per Person = Per Night Per Person × Số đêm
```

**Vị trí sử dụng**: 
- File: `app/Http/Controllers/clients/BuildTourController.php`
- Method: `estimateHotelCostPerPerson()`

---

### 3.3. Service Fee Calculation (Tính phí dịch vụ)

**Công thức**:
```
Base Service Fee Rate = (baseBudget <= 2,000,000) ? 8% : 10%
Base Service Fee = Core Cost × Base Service Fee Rate
```

**Phụ thu cao điểm**:
- Cuối tuần (Thứ 6, 7, CN): +2%
- Tháng 1-2 (Tết): +5%
- `Surcharge = Core Cost × High Season Rate`

**Tổng phí dịch vụ**:
```
Service Fee Per Person = Base Service Fee + Private Tour Fee (nếu có) + Surcharge
```

**Vị trí sử dụng**: BuildTourController - `generateTourOptions()`

---

### 3.4. Package Multiplier Algorithm (Hệ số gói tour)

**Mục đích**: Điều chỉnh giá theo gói tour (Tiết kiệm/Tiêu chuẩn/Nâng cao).

**Hệ số**:
- Gói tiết kiệm: × 0.9 (giảm 10%)
- Gói tiêu chuẩn: × 1.0 (giữ nguyên)
- Gói nâng cao: × 1.15 (tăng 15%)

**Công thức**:
```
Cost After Package = Core Cost × Package Multiplier
```

**Vị trí sử dụng**: BuildTourController - `generateTourOptions()`

---

### 3.5. Private Tour Multiplier Algorithm (Hệ số tour cá nhân)

**Mục đích**: Tính phụ thu cho tour cá nhân/đoàn nhỏ.

**Hệ số**:
- 1 người: × 1.5 (phụ thu 50%)
- 2-3 người: × 1.5 (phụ thu 50%)
- 4-9 người: × 1.2 (phụ thu 20%)
- ≥ 10 người: × 1.0 (không phụ thu)

**Công thức**:
```
Private Tour Fee = Core Cost × (Private Multiplier - 1.0)
```

**Vị trí sử dụng**: BuildTourController - `generateTourOptions()`

---

### 3.6. Group Discount Algorithm (Thuật toán giảm giá đoàn)

**Mục đích**: Tính giảm giá theo số lượng khách trong đoàn.

**Bảng giảm giá**:
```
1-3 khách:     Không giảm (× 1.0)
4-5 khách:     Giảm 2% (× 0.98)
6-9 khách:     Giảm 4% (× 0.96)
10-14 khách:   Giảm 6% (× 0.94)
≥ 15 khách:    Giảm 8% (× 0.92)
```

**Công thức**:
```
Price Per Adult = Base Price × Group Discount Factor
Discount Amount = Base Price - Price Per Adult
```

**Lưu ý**: Chỉ áp dụng cho tour đoàn (tour_type = 'group'), tour cá nhân không được giảm.

**Vị trí sử dụng**: 
- File: `app/Http/Controllers/clients/BuildTourController.php`
- Method: `calculateGroupDiscountFactor()`

**Độ phức tạp**: O(1)

---

### 3.7. Child Price Calculation (Tính giá trẻ em)

**Công thức**:
```
Child Factor = 0.75 (trẻ em = 75% giá người lớn)
Price Per Child = Price Per Adult × Child Factor
```

**Tổng giá tour**:
```
Total Price = (Price Per Adult × Số người lớn) + (Price Per Child × Số trẻ em)
```

**Vị trí sử dụng**: BuildTourController - `generateTourOptions()`

---

## 4. THUẬT TOÁN TÌM KIẾM (SEARCH ALGORITHMS)

### 4.1. Keyword Search Algorithm (Tìm kiếm theo từ khóa)

**Mục đích**: Tìm tour dựa trên từ khóa trong tên, mô tả, điểm đến.

**Thuật toán**:
- Sử dụng SQL LIKE với pattern: `%keyword%`
- Tìm kiếm trong các trường:
  - `title` (tên tour)
  - `description` (mô tả)
  - `destination` (điểm đến)

**SQL Query**:
```sql
WHERE title LIKE '%keyword%' 
   OR description LIKE '%keyword%' 
   OR destination LIKE '%keyword%'
```

**Vị trí sử dụng**: 
- File: `app/Http/Controllers/clients/SearchController.php`
- File: `app/Http/Controllers/AIController.php` - Method `executeSearchTours()`

**Độ phức tạp**: O(n) với n là số lượng tour

---

### 4.2. Map-Based Search Algorithm (Tìm kiếm dựa trên bản đồ)

**Mục đích**: Tìm tour gần vị trí được chọn trên bản đồ (nearby tours).

**Thuật toán**:
- Sử dụng tọa độ địa lý (latitude, longitude)
- Tính khoảng cách giữa vị trí người dùng và điểm đến tour
- Có thể sử dụng công thức Haversine hoặc tích hợp với Google Maps API

**Vị trí sử dụng**: 
- File: `app/Http/Controllers/clients/SearchController.php`
- Method: `searchNearby()`

---

### 4.3. Multi-Criteria Filtering (Lọc đa tiêu chí)

**Mục đích**: Lọc tour theo nhiều tiêu chí cùng lúc (giá, thời gian, điểm đến, đánh giá).

**Thuật toán**:
- Kết hợp nhiều điều kiện WHERE:
  - Price range: `WHERE priceAdult BETWEEN minPrice AND maxPrice`
  - Destination: `WHERE destination LIKE '%destination%'`
  - Time: `WHERE time LIKE '%keyword%'`
  - Rating: `JOIN với bảng reviews và tính averageRating >= minRating`

**Vị trí sử dụng**: 
- File: `app/Http/Controllers/clients/SearchController.php`
- Trang tìm kiếm tour

**Độ phức tạp**: O(n) với n là số lượng tour

---

## 5. THUẬT TOÁN XỬ LÝ NGÔN NGỮ TỰ NHIÊN (NLP)

### 5.1. OpenAI GPT Function Calling (Chatbot AI)

**Mục đích**: Phân tích câu hỏi của người dùng và gọi function phù hợp.

**Thuật toán**:
- Sử dụng OpenAI GPT model (gpt-4o-mini) với function calling
- Model phân tích intent từ câu hỏi của người dùng
- Tự động gọi các function:
  - `searchTours()`: Khi người dùng hỏi về tour
  - `getTourDetails()`: Khi người dùng muốn xem chi tiết tour
  - `createBookingLink()`: Khi người dùng muốn đặt tour

**Context Management**:
- Lưu 10 tin nhắn gần nhất để hiểu ngữ cảnh
- Conversation context được truyền vào mỗi lần gọi API

**Vị trí sử dụng**: 
- File: `app/Http/Controllers/AIController.php` - Method `chat()`

**Fallback**: Keyword-based response khi OpenAI API không khả dụng

---

## 6. THUẬT TOÁN TỐI ƯU HÓA

### 6.1. Fallback Mechanism (Cơ chế dự phòng)

**Mục đích**: Đảm bảo hệ thống luôn hoạt động ngay cả khi API bên ngoài lỗi.

**Thuật toán**:
1. Thử gọi API chính (Python ML API, OpenAI API)
2. Đặt timeout: 2 giây
3. Nếu thất bại hoặc timeout:
   - Fallback về thuật toán PHP đơn giản hơn
   - Log lỗi để theo dõi

**Vị trí sử dụng**: 
- Tất cả các method trong `RecommendationService`
- `AIController::chat()`

---

### 6.2. Database Query Optimization (Tối ưu truy vấn)

**Các kỹ thuật**:
- Sử dụng INDEX trên các cột thường xuyên query: `tourId`, `destination`, `availability`
- Sử dụng JOIN thay vì multiple queries
- LIMIT để giới hạn kết quả
- SELECT chỉ các cột cần thiết

**Vị trí sử dụng**: Tất cả các controller và service

---

## 7. TÓM TẮT

Đề tài đã sử dụng tổng cộng **15+ thuật toán** chính, bao gồm:

1. **Recommendation Algorithms**: 4 thuật toán (Content-Based Filtering, Trending Score, User-Based Filtering, ML Recommendations)
2. **Itinerary Generation**: 2 thuật toán (Place Matching, Day Planning)
3. **Pricing Algorithms**: 7 thuật toán (Core Cost, Hotel Cost, Service Fee, Package Multiplier, Private Tour Multiplier, Group Discount, Child Price)
4. **Search Algorithms**: 3 thuật toán (Keyword Search, Map-Based Search, Multi-Criteria Filtering)
5. **NLP Algorithms**: 1 thuật toán (OpenAI GPT Function Calling)
6. **Optimization**: 2 thuật toán (Fallback Mechanism, Query Optimization)

Các thuật toán này được tích hợp chặt chẽ với nhau để tạo nên một hệ thống đặt tour thông minh, tự động và hiệu quả.

---

**Lưu ý**: Một số thuật toán ML phức tạp hơn được triển khai ở phía Python API (không nằm trong codebase PHP), nhưng hệ thống đã có cơ chế fallback thông minh để đảm bảo luôn hoạt động ổn định.

