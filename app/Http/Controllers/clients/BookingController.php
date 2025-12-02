<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\clients\Booking;
use App\Models\clients\Checkout;
use App\Models\clients\Tours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BookingController extends Controller
{
    private $tour;
    private $booking;
    private $checkout;

    public function __construct()
    {
        parent::__construct(); // Gọi constructor của Controller để khởi tạo $user
        $this->tour = new Tours();
        $this->booking = new Booking();
        $this->checkout = new Checkout();
    }

    public function index($id, Request $request)
    {
        $title = 'Đặt Tour';
        $tour = $this->tour->getTourDetail($id);
        $transIdMomo = null; // Initialize the variable
        
        // Tính giá người lớn và trẻ em
        $adultPrice = (int) ($tour->priceAdult ?? 0);
        $childPrice = (int) ($tour->priceChild ?? 0);
        
        // Nếu không có giá trẻ em, tính 75% giá người lớn (làm tròn đến hàng nghìn)
        if ($childPrice == 0 && $adultPrice > 0) {
            $childPrice = (int) round($adultPrice * 0.75 / 1000) * 1000;
        }
        
        // Lấy số lượng từ query params hoặc old input, mặc định 1 người lớn, 0 trẻ em
        $adults = (int) ($request->query('adults') ?? old('numAdults', 1));
        $children = (int) ($request->query('children') ?? old('numChildren', 0));
        
        // Đảm bảo tối thiểu 1 người lớn
        if ($adults < 1) {
            $adults = 1;
        }
        if ($children < 0) {
            $children = 0;
        }
        
        // Tính tổng tiền ban đầu
        $totalPrice = ($adults * $adultPrice) + ($children * $childPrice);
        
        // Lấy thông tin user để tự động điền form
        $user = null;
        if (session()->has('username')) {
            $userId = $this->getUserId();
            if ($userId) {
                $user = $this->user->getUser($userId);
            }
        }
        
        return view('clients.booking', compact(
            'title', 
            'tour', 
            'transIdMomo', 
            'user',
            'adultPrice',
            'childPrice',
            'adults',
            'children',
            'totalPrice'
        ));
    }

    public function createBooking(Request $req)
    {
        try {
            // Validate dữ liệu đầu vào
            $validated = $req->validate([
                'fullName' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'numAdults' => 'required|integer|min:1',
                'numChildren' => 'required|integer|min:0',
                'tourId' => 'required|integer|exists:tbl_tours,tourId',
                'totalPrice' => 'required|numeric|min:1',
                'payment' => 'required|in:office-payment,paypal-payment,momo-payment',
            ], [
                'fullName.required' => 'Vui lòng nhập họ và tên',
                'email.required' => 'Vui lòng nhập email',
                'email.email' => 'Email không hợp lệ',
                'tel.required' => 'Vui lòng nhập số điện thoại',
                'address.required' => 'Vui lòng nhập địa chỉ',
                'numAdults.required' => 'Vui lòng chọn số lượng người lớn',
                'numAdults.min' => 'Số lượng người lớn phải lớn hơn 0',
                'numChildren.min' => 'Số lượng trẻ em không được âm',
                'tourId.required' => 'Thiếu thông tin tour',
                'tourId.exists' => 'Tour không tồn tại',
                'totalPrice.required' => 'Thiếu thông tin tổng tiền',
                'totalPrice.min' => 'Tổng tiền phải lớn hơn 0',
                'payment.required' => 'Vui lòng chọn phương thức thanh toán',
            ]);

            // Lấy dữ liệu từ request
            $address = $req->input('address');
            $email = $req->input('email');
            $fullName = $req->input('fullName');
            $numAdults = (int) $req->input('numAdults');
            $numChildren = (int) $req->input('numChildren');
            $paymentMethod = $req->input('payment'); // Đọc từ radio button 'payment'
            $tel = $req->input('tel');
            $totalPrice = (float) $req->input('totalPrice');
            $tourId = (int) $req->input('tourId');
            $userId = $this->getUserId();

            // Kiểm tra userId (có thể null nếu user chưa đăng nhập)
            // Nếu hệ thống yêu cầu đăng nhập, uncomment dòng sau:
            // if (!$userId) {
            //     return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để đặt tour');
            // }

            // Kiểm tra tour còn chỗ không
            $tour = $this->tour->getTourDetail($tourId);
            if (!$tour) {
                toastr()->error('Tour không tồn tại!');
                return redirect()->back()->withInput();
            }

            $totalPeople = $numAdults + $numChildren;
            if ($tour->quantity < $totalPeople) {
                toastr()->error('Tour không còn đủ chỗ! Số chỗ còn lại: ' . $tour->quantity);
                return redirect()->back()->withInput();
            }

            /**
             * Xử lý booking và checkout
             */
            $dataBooking = [
                'tourId' => $tourId,
                'userId' => $userId, // Có thể null nếu cho phép đặt tour không cần đăng nhập
                'address' => $address,
                'fullName' => $fullName,
                'email' => $email,
                'numAdults' => $numAdults,
                'numChildren' => $numChildren,
                'phoneNumber' => $tel,
                'totalPrice' => $totalPrice,
                'bookingDate' => now(), // Thêm bookingDate
                'bookingStatus' => 'b', // 'b' = booked (đặt mới)
            ];

            $bookingId = $this->booking->createBooking($dataBooking);

            if (!$bookingId) {
                toastr()->error('Có lỗi xảy ra khi tạo booking!');
                return redirect()->back()->withInput();
            }

            // Xử lý checkout
            $dataCheckout = [
                'bookingId' => $bookingId,
                'paymentMethod' => $paymentMethod,
                'amount' => $totalPrice,
                'paymentStatus' => ($paymentMethod === 'paypal-payment' || $paymentMethod === 'momo-payment') ? 'y' : 'n',
            ];

            if ($paymentMethod === 'paypal-payment' && $req->has('transactionIdPaypal')) {
                $dataCheckout['transactionId'] = $req->transactionIdPaypal;
            } elseif ($paymentMethod === 'momo-payment' && $req->has('transactionIdMomo')) {
                $dataCheckout['transactionId'] = $req->transactionIdMomo;
            }

            $checkoutId = $this->checkout->createCheckout($dataCheckout);

            if (!$checkoutId) {
                // Nếu tạo checkout thất bại, vẫn giữ booking nhưng log lỗi
                \Log::error('Failed to create checkout', [
                    'bookingId' => $bookingId,
                    'dataCheckout' => $dataCheckout
                ]);
                toastr()->warning('Đặt tour thành công nhưng có lỗi khi tạo checkout. Vui lòng liên hệ hỗ trợ.');
            }

            /**
             * Update quantity mới cho tour đó, trừ số lượng
             */
            $newQuantity = max(0, $tour->quantity - $totalPeople);
            $dataUpdate = [
                'quantity' => $newQuantity
            ];

            $this->tour->updateTours($tourId, $dataUpdate);

            toastr()->success('Đặt tour thành công!');
            return redirect()->route('tour-booked', [
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId ?? null,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Log lỗi
            \Log::error('Error creating booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $req->all()
            ]);

            toastr()->error('Có lỗi xảy ra khi đặt tour. Vui lòng thử lại sau!');
            return redirect()->back()->withInput();
        }
    }

    public function createMomoPayment(Request $request)
    {
        session()->put('tourId', $request->tourId);
        if ($request->has('customTourId')) {
            session()->put('customTourId', $request->customTourId);
            
            // Lưu thông tin form vào session để tự động lưu booking sau khi thanh toán thành công
            if ($request->has('form_data')) {
                $formData = json_decode($request->form_data, true);
                if ($formData) {
                    session()->put('momo_pending_booking_data', [
                        'custom_tour_id' => $request->customTourId,
                        'full_name' => $formData['full_name'] ?? '',
                        'email' => $formData['email'] ?? '',
                        'phone' => $formData['phone'] ?? '',
                        'address' => $formData['address'] ?? '',
                        'note' => $formData['note'] ?? '',
                        'amount' => $request->amount ?? 0,
                    ]);
                }
            }
        }
        
        // Nếu là test mode, chỉ lưu session và trả về success
        if ($request->has('test_mode') && $request->test_mode) {
            return response()->json(['success' => true, 'message' => 'Form data saved to session']);
        }
        
        try {
            // Lấy giá từ request, nếu không có thì mặc định 0
            $amount = (int) ($request->amount ?? 0);
            
            if ($amount <= 0) {
                return response()->json(['error' => 'Số tiền thanh toán không hợp lệ'], 400);
            }
    
            // Các thông tin cần thiết của MoMo
            $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
            $partnerCode = "MOMOBKUN20180529"; // mã partner của bạn
            $accessKey = "klm05TvNBzhg7h7j"; // access key của bạn
            $secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa"; // secret key của bạn
    
            $orderInfo = "Thanh toán đơn hàng";
            $requestId = time();
            $orderId = time();
            $extraData = "";
            
            // Xác định redirect URL dựa trên loại tour
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            
            if ($request->has('customTourId')) {
                $redirectUrl = route('custom-tours.checkout', ['id' => $request->customTourId]); // URL chuyển hướng cho custom tour
                $ipnUrl = $baseUrl . '/momo-ipn'; // URL IPN riêng để xử lý webhook
            } else {
                $redirectUrl = $baseUrl . "/booking"; // URL chuyển hướng cho tour thông thường
                $ipnUrl = $baseUrl . "/momo-ipn"; // URL IPN riêng để xử lý webhook
            }
            $requestType = 'payWithATM'; // Kiểu yêu cầu
    
            // Tạo rawHash và chữ ký theo cách thủ công
            $rawHash = "accessKey=" . $accessKey . 
                       "&amount=" . $amount . 
                       "&extraData=" . $extraData . 
                       "&ipnUrl=" . $ipnUrl . 
                       "&orderId=" . $orderId . 
                       "&orderInfo=" . $orderInfo . 
                       "&partnerCode=" . $partnerCode . 
                       "&redirectUrl=" . $redirectUrl . 
                       "&requestId=" . $requestId . 
                       "&requestType=" . $requestType;
    
            // Tạo chữ ký
            $signature = hash_hmac("sha256", $rawHash, $secretKey);
    
            // Dữ liệu gửi đến MoMo
            $data = [
                'partnerCode' => $partnerCode,
                'partnerName' => "Test", // Tên đối tác
                'storeId' => "MomoTestStore", // ID cửa hàng
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'lang' => 'vi',
                'extraData' => $extraData,
                'requestType' => $requestType,
                'signature' => $signature
            ];
    
            // Gửi yêu cầu POST đến MoMo để tạo yêu cầu thanh toán
            $response = Http::post($endpoint, $data);
    
            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['payUrl'])) {
                    return response()->json(['payUrl' => $body['payUrl']]);
                } else {
                    // Trả về thông tin lỗi trong response nếu không có 'payUrl'
                    return response()->json(['error' => 'Invalid response from MoMo', 'details' => $body], 400);
                }
            } else {
                // Trả về thông tin lỗi trong response nếu lỗi kết nối
                return response()->json(['error' => 'Lỗi kết nối với MoMo', 'details' => $response->body()], 500);
            }
        } catch (\Exception $e) {
            // Trả về chi tiết ngoại lệ trong response
            return response()->json(['error' => 'Đã xảy ra lỗi', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
    

    public function handlePaymentMomoCallback(Request $request)
    {
        // MoMo có thể gọi callback qua GET (redirect) hoặc POST (IPN)
        $resultCode = $request->input('resultCode') ?? $request->query('resultCode');
        $transIdMomo = $request->input('transId') ?? $request->query('transId');
        $orderId = $request->input('orderId') ?? $request->query('orderId');
        $amount = $request->input('amount') ?? $request->query('amount');
        $message = $request->input('message') ?? $request->query('message');
        
        // Log để debug
        \Log::info('MoMo Callback Received', [
            'resultCode' => $resultCode,
            'transId' => $transIdMomo,
            'orderId' => $orderId,
            'amount' => $amount,
            'message' => $message,
            'all_params' => $request->all(),
        ]);
        
        // Kiểm tra nếu là custom tour
        $customTourId = session()->get('customTourId');
        
            if ($customTourId) {
            // Xử lý callback cho custom tour
            
            // resultCode: '0' = thành công, khác = thất bại hoặc đang xử lý
            if ($resultCode == '0') {
                // Thanh toán thành công - TỰ ĐỘNG LƯU BOOKING
                $pendingBookingData = session()->get('momo_pending_booking_data');
                
                if ($pendingBookingData && $pendingBookingData['custom_tour_id'] == $customTourId) {
                    // Tự động lưu booking
                    $bookingResult = $this->autoSaveBookingAfterMomoSuccess($customTourId, $pendingBookingData, $transIdMomo, $orderId);
                    
                    if ($bookingResult['success']) {
                        // Xóa session data
                        session()->forget('customTourId');
                        session()->forget('momo_pending_booking_data');
                        
                        // Redirect đến trang thành công
                        return redirect()
                            ->route('tour-booked', [
                                'bookingId' => $bookingResult['bookingId'],
                                'checkoutId' => $bookingResult['checkoutId']
                            ])
                            ->with('success', 'Thanh toán MoMo thành công! Đơn hàng của bạn đã được tạo.');
                    } else {
                        // Nếu lưu booking thất bại, vẫn redirect về checkout với thông báo
                        return redirect()
                            ->route('custom-tours.checkout', ['id' => $customTourId])
                            ->with('success', 'Thanh toán MoMo thành công! Vui lòng hoàn tất đặt tour.')
                            ->with('momo_trans_id', $transIdMomo)
                            ->with('momo_order_id', $orderId)
                            ->with('warning', 'Đã thanh toán thành công nhưng có lỗi khi lưu đơn hàng. Vui lòng liên hệ hỗ trợ.');
                    }
                } else {
                    // Không có thông tin form trong session, redirect về checkout để user nhập lại
                    return redirect()
                        ->route('custom-tours.checkout', ['id' => $customTourId])
                        ->with('success', 'Thanh toán MoMo thành công! Vui lòng hoàn tất đặt tour.')
                        ->with('momo_trans_id', $transIdMomo)
                        ->with('momo_order_id', $orderId);
                }
            } elseif ($resultCode == '1006' || $resultCode == '1005') {
                // Giao dịch đang xử lý (pending) - VẪN LƯU BOOKING với paymentStatus = 'n'
                $pendingBookingData = session()->get('momo_pending_booking_data');
                
                if ($pendingBookingData && $pendingBookingData['custom_tour_id'] == $customTourId) {
                    // Lưu booking với trạng thái chờ thanh toán
                    $bookingResult = $this->saveBookingWithPendingPayment($customTourId, $pendingBookingData, $transIdMomo, $orderId);
                    
                    if ($bookingResult['success']) {
                        session()->forget('customTourId');
                        session()->forget('momo_pending_booking_data');
                        
                        return redirect()
                            ->route('tour-booked', [
                                'bookingId' => $bookingResult['bookingId'],
                                'checkoutId' => $bookingResult['checkoutId']
                            ])
                            ->with('info', 'Giao dịch đang được xử lý. Đơn hàng của bạn đã được tạo với trạng thái "Chờ thanh toán". MoMo sẽ gửi thông báo khi hoàn tất. Nếu tài khoản đã bị trừ tiền, tiền sẽ được hoàn lại trong vòng 48 giờ nếu giao dịch không thành công.');
                    }
                }
                
                return redirect()
                    ->route('custom-tours.checkout', ['id' => $customTourId])
                    ->with('info', 'Giao dịch đang được xử lý. MoMo sẽ gửi thông báo khi hoàn tất. Nếu tài khoản đã bị trừ tiền, tiền sẽ được hoàn lại trong vòng 48 giờ nếu giao dịch không thành công.')
                    ->with('momo_trans_id', $transIdMomo)
                    ->with('momo_order_id', $orderId)
                    ->with('momo_status', 'pending');
            } else {
                // Thanh toán thất bại hoặc bị hủy - VẪN LƯU BOOKING với paymentStatus = 'n'
                $pendingBookingData = session()->get('momo_pending_booking_data');
                
                if ($pendingBookingData && $pendingBookingData['custom_tour_id'] == $customTourId) {
                    // Lưu booking với trạng thái chờ thanh toán
                    $bookingResult = $this->saveBookingWithPendingPayment($customTourId, $pendingBookingData, $transIdMomo, $orderId);
                    
                    if ($bookingResult['success']) {
                        session()->forget('customTourId');
                        session()->forget('momo_pending_booking_data');
                        
                        $errorMsg = $this->getMomoErrorMessage($resultCode, $message);
                        
                        return redirect()
                            ->route('tour-booked', [
                                'bookingId' => $bookingResult['bookingId'],
                                'checkoutId' => $bookingResult['checkoutId']
                            ])
                            ->with('warning', 'Thanh toán MoMo không thành công. Đơn hàng của bạn đã được tạo với trạng thái "Chờ thanh toán". Bạn có thể thanh toán lại sau. ' . $errorMsg);
                    }
                }
                
                // Nếu không lưu được booking, redirect về checkout
                $errorMsg = $this->getMomoErrorMessage($resultCode, $message);
                return redirect()
                    ->route('custom-tours.checkout', ['id' => $customTourId])
                    ->with('error', $errorMsg)
                    ->with('momo_result_code', $resultCode)
                    ->with('momo_message', $message);
            }
        }
        
        // Xử lý callback cho tour thông thường
        $tourId = session()->get('tourId'); 
        $tour = $this->tour->getTourDetail($tourId);
        session()->forget('tourId');
        
        // Handle the payment response
        if ($resultCode == '0') {
            $title = 'Đã thanh toán';
            return view('clients.booking', compact('title', 'tour', 'transIdMomo'));
        } elseif ($resultCode == '1006' || $resultCode == '1005') {
            $title = 'Giao dịch đang xử lý';
            return view('clients.booking', compact('title', 'tour', 'transIdMomo'))
                ->with('info', 'Giao dịch đang được xử lý. MoMo sẽ gửi thông báo khi hoàn tất.');
        } else {
            // Payment failed, handle the error accordingly
            $errorMsg = $this->getMomoErrorMessage($resultCode, $message);
            $title = 'Thanh toán thất bại';
            return view('clients.booking', compact('title', 'tour'))
                ->with('error', $errorMsg);
        }
    }
    
    /**
     * Lấy thông báo lỗi thân thiện dựa trên resultCode của MoMo
     */
    private function getMomoErrorMessage($resultCode, $message = null)
    {
        $errorMessages = [
            '1001' => 'Thẻ/Tài khoản không hợp lệ hoặc đã bị khóa.',
            '1002' => 'Thẻ/Tài khoản không đủ số dư để thanh toán.',
            '1003' => 'Giao dịch bị từ chối do nhà phát hành thẻ/ngân hàng.',
            '1004' => 'Thông tin thẻ không đúng hoặc thẻ đã hết hạn.',
            '1005' => 'Giao dịch đang được xử lý, vui lòng đợi.',
            '1006' => 'Giao dịch đang được xử lý, vui lòng đợi.',
            '1007' => 'Giao dịch bị hủy bởi người dùng.',
            '1008' => 'Giao dịch hết hạn.',
            '1009' => 'Giao dịch bị từ chối do vượt quá hạn mức.',
            '1010' => 'Thẻ không hỗ trợ thanh toán online.',
            '1011' => 'Ngân hàng từ chối giao dịch do lý do bảo mật.',
        ];
        
        // Nếu có message từ MoMo, ưu tiên dùng message đó
        if ($message && !empty($message)) {
            // Kiểm tra nếu message chứa thông tin cụ thể
            if (stripos($message, 'từ chối') !== false || stripos($message, 'issuer') !== false) {
                return 'Giao dịch bị từ chối do nhà phát hành tài khoản thanh toán. Vui lòng kiểm tra lại thông tin thẻ hoặc liên hệ ngân hàng để được hỗ trợ.';
            }
            return $message;
        }
        
        // Nếu có error message tương ứng với resultCode
        if (isset($errorMessages[$resultCode])) {
            return $errorMessages[$resultCode];
        }
        
        // Mặc định
        return 'Thanh toán MoMo thất bại. Vui lòng thử lại hoặc liên hệ hỗ trợ nếu vấn đề vẫn tiếp tục.';
    }
    
    /**
     * Lưu booking với trạng thái chờ thanh toán (khi thanh toán thất bại hoặc đang xử lý)
     */
    private function saveBookingWithPendingPayment($customTourId, $bookingData, $transIdMomo = null, $orderId = null)
    {
        try {
            // 1. Lấy custom tour từ DB
            $customTour = DB::table('tbl_custom_tours')->where('id', $customTourId)->first();
            
            if (!$customTour) {
                return ['success' => false, 'message' => 'Không tìm thấy custom tour'];
            }
            
            // 2. Lấy userId từ session
            $userId = session()->get('userId');
            if (!$userId) {
                return ['success' => false, 'message' => 'Không tìm thấy userId'];
            }
            
            // 3. Số người & tổng tiền
            $numAdults = $customTour->adults ?? $customTour->total_people ?? 1;
            $numChildren = $customTour->children ?? 0;
            
            // Lấy lại JSON để ưu tiên final_total_price
            $option = json_decode($customTour->option_json, true) ?? [];
            $priceSummary = $option['price_breakdown'] ?? [];
            
            $totalPrice = $priceSummary['final_total_price'] ?? ($customTour->estimated_cost ?? 0);
            
            // 4. Insert vào tbl_booking
            // Với custom tour, không insert tourId (để NULL) vì database không cho phép NULL
            // Chỉ insert custom_tour_id
            $bookingDataInsert = [
                'custom_tour_id' => $customTour->id,
                'userId' => $userId,
                'fullName' => $bookingData['full_name'] ?? '',
                'email' => $bookingData['email'] ?? '',
                'phoneNumber' => $bookingData['phone'] ?? '',
                'address' => $bookingData['address'] ?? '',
                'bookingDate' => now(),
                'numAdults' => $numAdults,
                'numChildren' => $numChildren,
                'totalPrice' => $totalPrice,
                'bookingStatus' => 'b', // Đợi xác nhận
            ];
            
            // Chỉ thêm tourId nếu có giá trị (không phải custom tour)
            // Với custom tour, không thêm tourId vào array để database tự set NULL hoặc default
            
            $bookingId = DB::table('tbl_booking')->insertGetId($bookingDataInsert);
            
            // 5. Insert vào tbl_checkout với paymentStatus = 'n' (chưa thanh toán)
            $checkoutId = DB::table('tbl_checkout')->insertGetId([
                'bookingId' => $bookingId,
                'paymentMethod' => 'momo-payment',
                'amount' => $totalPrice,
                'paymentStatus' => 'n', // Chưa thanh toán
                'transactionId' => $transIdMomo ?? $orderId ?? null,
            ]);
            
            \Log::info('Saved booking with pending payment', [
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId,
                'customTourId' => $customTourId,
                'paymentStatus' => 'n',
            ]);
            
            return [
                'success' => true,
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId,
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error saving booking with pending payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Tự động lưu booking sau khi thanh toán MoMo thành công
     */
    private function autoSaveBookingAfterMomoSuccess($customTourId, $bookingData, $transIdMomo, $orderId)
    {
        try {
            // 1. Lấy custom tour từ DB
            $customTour = DB::table('tbl_custom_tours')->where('id', $customTourId)->first();
            
            if (!$customTour) {
                return ['success' => false, 'message' => 'Không tìm thấy custom tour'];
            }
            
            // 2. Lấy userId từ session
            $userId = session()->get('userId');
            if (!$userId) {
                return ['success' => false, 'message' => 'Không tìm thấy userId'];
            }
            
            // 3. Số người & tổng tiền
            $numAdults = $customTour->adults ?? $customTour->total_people ?? 1;
            $numChildren = $customTour->children ?? 0;
            
            // Lấy lại JSON để ưu tiên final_total_price
            $option = json_decode($customTour->option_json, true) ?? [];
            $priceSummary = $option['price_breakdown'] ?? [];
            
            $totalPrice = $priceSummary['final_total_price'] ?? ($customTour->estimated_cost ?? 0);
            
            // 4. Insert vào tbl_booking
            // Với custom tour, không insert tourId (để NULL) vì database không cho phép NULL
            // Chỉ insert custom_tour_id
            $bookingDataInsert = [
                'custom_tour_id' => $customTour->id,
                'userId' => $userId,
                'fullName' => $bookingData['full_name'] ?? '',
                'email' => $bookingData['email'] ?? '',
                'phoneNumber' => $bookingData['phone'] ?? '',
                'address' => $bookingData['address'] ?? '',
                'bookingDate' => now(),
                'numAdults' => $numAdults,
                'numChildren' => $numChildren,
                'totalPrice' => $totalPrice,
                'bookingStatus' => 'b',
            ];
            
            // Chỉ thêm tourId nếu có giá trị (không phải custom tour)
            // Với custom tour, không thêm tourId vào array để database tự set NULL hoặc default
            
            $bookingId = DB::table('tbl_booking')->insertGetId($bookingDataInsert);
            
            // 5. Insert vào tbl_checkout với paymentStatus = 'y' (đã thanh toán)
            $checkoutId = DB::table('tbl_checkout')->insertGetId([
                'bookingId' => $bookingId,
                'paymentMethod' => 'momo-payment',
                'amount' => $totalPrice,
                'paymentStatus' => 'y', // Đã thanh toán
                'transactionId' => $transIdMomo ?? $orderId,
            ]);
            
            \Log::info('Auto saved booking after MoMo success', [
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId,
                'customTourId' => $customTourId,
                'transId' => $transIdMomo,
            ]);
            
            return [
                'success' => true,
                'bookingId' => $bookingId,
                'checkoutId' => $checkoutId,
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error auto saving booking after MoMo success', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Xử lý IPN (Instant Payment Notification) từ MoMo
     * MoMo sẽ gọi endpoint này khi trạng thái thanh toán thay đổi
     */
    public function handleMomoIPN(Request $request)
    {
        // MoMo IPN thường gửi qua POST
        $resultCode = $request->input('resultCode');
        $transIdMomo = $request->input('transId');
        $orderId = $request->input('orderId');
        $amount = $request->input('amount');
        $message = $request->input('message');
        
        // Log IPN để debug
        \Log::info('MoMo IPN Received', [
            'resultCode' => $resultCode,
            'transId' => $transIdMomo,
            'orderId' => $orderId,
            'amount' => $amount,
            'message' => $message,
            'all_params' => $request->all(),
        ]);
        
        // Xác thực signature từ MoMo (nếu cần)
        // TODO: Thêm logic xác thực signature
        
        // Xử lý theo resultCode
        if ($resultCode == '0') {
            // Thanh toán thành công - có thể tự động cập nhật booking
            // TODO: Tự động cập nhật trạng thái booking nếu cần
            \Log::info('MoMo IPN: Payment successful', ['orderId' => $orderId, 'transId' => $transIdMomo]);
        } elseif ($resultCode == '1006' || $resultCode == '1005') {
            // Đang xử lý
            \Log::info('MoMo IPN: Payment pending', ['orderId' => $orderId, 'transId' => $transIdMomo]);
        } else {
            // Thất bại
            \Log::warning('MoMo IPN: Payment failed', ['orderId' => $orderId, 'resultCode' => $resultCode, 'message' => $message]);
        }
        
        // Trả về response cho MoMo
        return response()->json(['status' => 'success'], 200);
    }
    
    /**
     * Endpoint để test giả lập thanh toán thành công (chỉ dùng trong development)
     */
    public function testMomoPaymentSuccess($customTourId = null, Request $request = null)
    {
        // CHỈ cho phép trong môi trường local/development
        if (app()->environment('production')) {
            abort(404);
        }
        
        // Lấy customTourId từ URL parameter hoặc request input
        if (!$customTourId) {
            $customTourId = $request ? $request->input('customTourId') : null;
        }
        
        // Nếu vẫn không có, lấy từ session
        if (!$customTourId) {
            $customTourId = session()->get('customTourId');
        }
        
        if (!$customTourId) {
            \Log::error('testMomoPaymentSuccess: Missing customTourId', [
                'session_customTourId' => session()->get('customTourId'),
                'request_all' => $request ? $request->all() : [],
            ]);
            return redirect()->route('build-tour.result')
                ->with('error', 'Thiếu thông tin tour. Vui lòng thử lại.');
        }
        
        // Kiểm tra custom tour có tồn tại không
        $customTour = DB::table('tbl_custom_tours')->where('id', $customTourId)->first();
        if (!$customTour) {
            \Log::error('testMomoPaymentSuccess: Custom tour not found', ['customTourId' => $customTourId]);
            return redirect()->route('build-tour.result')
                ->with('error', 'Phương án tour đã chọn không tồn tại hoặc đã bị xoá.');
        }
        
        // Lấy thông tin booking từ session (nếu có)
        $pendingBookingData = session()->get('momo_pending_booking_data');
        
        \Log::info('testMomoPaymentSuccess: Processing', [
            'customTourId' => $customTourId,
            'hasPendingBookingData' => !empty($pendingBookingData),
            'pendingBookingData' => $pendingBookingData,
        ]);
        
        if ($pendingBookingData && isset($pendingBookingData['custom_tour_id']) && $pendingBookingData['custom_tour_id'] == $customTourId) {
            // Tự động lưu booking với thanh toán thành công
            $transIdMomo = 'TEST_' . time();
            $orderId = 'TEST_' . time();
            
            $bookingResult = $this->autoSaveBookingAfterMomoSuccess($customTourId, $pendingBookingData, $transIdMomo, $orderId);
            
            \Log::info('testMomoPaymentSuccess: Booking result', [
                'success' => $bookingResult['success'] ?? false,
                'bookingId' => $bookingResult['bookingId'] ?? null,
                'message' => $bookingResult['message'] ?? null,
            ]);
            
            if ($bookingResult['success']) {
                session()->forget('customTourId');
                session()->forget('momo_pending_booking_data');
                
                return redirect()
                    ->route('tour-booked', [
                        'bookingId' => $bookingResult['bookingId'],
                        'checkoutId' => $bookingResult['checkoutId']
                    ])
                    ->with('success', 'Thanh toán MoMo thành công (TEST)! Đơn hàng của bạn đã được tạo.');
            } else {
                // Nếu lưu booking thất bại, vẫn redirect về checkout với thông báo lỗi
                return redirect()
                    ->route('custom-tours.checkout', ['id' => $customTourId])
                    ->with('error', 'Thanh toán thành công nhưng có lỗi khi lưu đơn hàng: ' . ($bookingResult['message'] ?? 'Lỗi không xác định'))
                    ->with('momo_trans_id', $transIdMomo)
                    ->with('momo_order_id', $orderId);
            }
        } else {
            // Nếu không có thông tin form trong session, thử lấy từ custom tour và tự động điền
            \Log::warning('testMomoPaymentSuccess: No pending booking data in session', [
                'customTourId' => $customTourId,
                'session_data' => session()->all(),
            ]);
            
            // Redirect về checkout với thông báo yêu cầu điền lại form
            return redirect()
                ->route('custom-tours.checkout', ['id' => $customTourId])
                ->with('error', 'Không tìm thấy thông tin đặt tour trong session. Vui lòng điền lại form và thử lại.')
                ->with('momo_trans_id', 'TEST_' . time())
                ->with('momo_order_id', 'TEST_' . time());
        }
    }

    //Kiểm tra người dùng đã đặt và hoàn thành tour hay chưa để đánh giá
    public function checkBooking(Request $req){
        $tourId = $req->tourId;
        $userId = $this->getUserId();
        $check = $this->booking->checkBooking($tourId,$userId);
        if (!$check) {
            return response()->json(['success' => false]);
        }
        return response()->json(['success' => true]);
    }

}
