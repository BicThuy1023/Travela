<?php

use Illuminate\Support\Facades\Route;

// ADMIN CONTROLLERS
use App\Http\Controllers\admin\AdminManagementController;
use App\Http\Controllers\admin\BookingManagementController;
use App\Http\Controllers\admin\ContactManagementController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\LoginAdminController;
use App\Http\Controllers\admin\ToursManagementController;
use App\Http\Controllers\admin\UserManagementController;
use App\Http\Controllers\admin\CustomTourController;

// CLIENT CONTROLLERS
use App\Http\Controllers\clients\HomeController;
use App\Http\Controllers\clients\AboutController;
use App\Http\Controllers\clients\ServicesController;
use App\Http\Controllers\clients\ToursController;
use App\Http\Controllers\clients\TourDetailController;
use App\Http\Controllers\clients\DestinationController;
use App\Http\Controllers\clients\BookingController;
use App\Http\Controllers\clients\TravelGuidesController;
use App\Http\Controllers\clients\ContactController;
use App\Http\Controllers\clients\UserProfileController;
use App\Http\Controllers\clients\LoginController;
use App\Http\Controllers\clients\LoginGoogleController;
use App\Http\Controllers\clients\MyTourController;
use App\Http\Controllers\clients\PayPalController;
use App\Http\Controllers\clients\SearchController;
use App\Http\Controllers\clients\TourBookedController;
use App\Http\Controllers\clients\BuildTourController;

use App\Http\Controllers\Dev\ToolController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ================== CLIENT ==================

// Trang chủ
Route::get('/', [HomeController::class, 'index'])->name('home');

// Giới thiệu, điểm đến, cẩm nang
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/destination', [DestinationController::class, 'index'])->name('destination');
Route::get('/travel-guides', [TravelGuidesController::class, 'index'])->name('team');

// ================== BUILD TOUR (Thiết kế tour theo yêu cầu) ==================

// Step 1: Hiển thị form
Route::get('/build-tour', [BuildTourController::class, 'showForm'])
    ->name('build-tour.form');

// Step 2: Submit form
Route::post('/build-tour', [BuildTourController::class, 'submit'])
    ->name('build-tour.submit');

// Step 3: Xem kết quả (phương án)
Route::get('/build-tour/result', [BuildTourController::class, 'showResult'])
    ->name('build-tour.result');

// NEW: Xem chi tiết 1 phương án tour
Route::get('/build-tour/detail/{index}', [BuildTourController::class, 'showOptionDetail'])
    ->name('build-tour.detail');

// NEW: Chọn phương án (đặt tour)
Route::post('/build-tour/choose/{index}', [BuildTourController::class, 'chooseTour'])
    ->name('build-tour.choose');
// ================== CHECKOUT CUSTOM TOUR (sau khi chọn phương án) ==================

// Sau khi chọn phương án → sang trang đặt tour
Route::get('/build-tour/checkout', [BuildTourController::class, 'checkout'])
    ->name('build-tour.checkout')
    ->middleware('checkLoginClient');

// Submit form đặt tour theo yêu cầu
Route::post('/build-tour/checkout', [BuildTourController::class, 'submitCheckout'])
    ->name('build-tour.checkout.submit')
    ->middleware('checkLoginClient');

// ================== BOOKING CUSTOM (đặt tour theo yêu cầu sau khi chọn option) ==================

Route::get('/custom-tours/checkout/{id}', [BuildTourController::class, 'checkoutCustomTour'])
    ->name('custom-tours.checkout')
    ->middleware('checkLoginClient');

Route::post('/custom-tours/checkout/{id}', [BuildTourController::class, 'submitCustomTourBooking'])
    ->name('custom-tours.checkout.submit')
    ->middleware('checkLoginClient');

// ================== Map: Tìm tour gần vị trí ==================

Route::get('/nearby-tours', [SearchController::class, 'searchNearby'])->name('nearby.tours');
Route::get('/dev/update-tour-locations', [ToolController::class, 'updateTourLocations']);

// ================== Authentication khách hàng ==================

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/register', [LoginController::class, 'register'])->name('register');
Route::post('/login', [LoginController::class, 'login'])->name('user-login');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('activate-account/{token}', [LoginController::class, 'activateAccount'])->name('activate.account');

// Forgot Password
Route::post('/forgot-password', [LoginController::class, 'forgotPassword'])->name('forgot-password');
Route::get('/reset-password/{token}', [LoginController::class, 'showResetPasswordForm'])->name('reset-password.form');
Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('reset-password');

// Login Google
Route::get('auth/google', [LoginGoogleController::class, 'redirectToGoogle'])->name('login-google');
Route::get('auth/google/callback', [LoginGoogleController::class, 'handleGoogleCallback']);


// ================== Tours ==================

Route::get('/tours', [ToursController::class, 'index'])->name('tours');
Route::get('/filter-tours', [ToursController::class, 'filterTours'])->name('filter-tours');

// My Tours
Route::get('/my-tours', [MyTourController::class, 'index'])
    ->name('my-tours')
    ->middleware('checkLoginClient');

// Tour detail + review
Route::get('/tour-detail/{id?}', [TourDetailController::class, 'index'])->name('tour-detail');
Route::post('/checkBooking', [BookingController::class, 'checkBooking'])
    ->name('checkBooking')
    ->middleware('checkLoginClient');
Route::post('/reviews', [TourDetailController::class, 'reviews'])
    ->name('reviews')
    ->middleware('checkLoginClient');

// ================== User Profile ==================

Route::get('/user-profile', [UserProfileController::class, 'index'])
    ->name('user-profile')
    ->middleware('checkLoginClient');

Route::post('/user-profile', [UserProfileController::class, 'update'])->name('update-user-profile');
Route::post('/change-password-profile', [UserProfileController::class, 'changePassword'])->name('change-password');
Route::post('/change-avatar-profile', [UserProfileController::class, 'changeAvatar'])->name('change-avatar');


// ================== Booking & Thanh toán ==================

// GET route để xử lý trường hợp redirect sau khi đăng nhập (redirect về tour detail)
Route::get('/booking/{id?}', function($id = null) {
    if ($id) {
        return redirect()->route('tour-detail', ['id' => $id]);
    }
    return redirect()->route('home');
})->name('booking.get');

// POST route cho form booking
Route::post('/booking/{id?}', [BookingController::class, 'index'])
    ->name('booking')
    ->middleware('checkLoginClient');

Route::post('/create-booking', [BookingController::class, 'createBooking'])->name('create-booking');

Route::get('/booking', [BookingController::class, 'handlePaymentMomoCallback'])
    ->name('handlePaymentMomoCallback');

// Paypal
Route::get('create-transaction', [PayPalController::class, 'createTransaction'])->name('createTransaction');
Route::get('process-transaction', [PayPalController::class, 'processTransaction'])->name('processTransaction');
Route::get('success-transaction', [PayPalController::class, 'successTransaction'])->name('successTransaction');
Route::get('cancel-transaction', [PayPalController::class, 'cancelTransaction'])->name('cancelTransaction');

// Momo
Route::post('/create-momo-payment', [BookingController::class, 'createMomoPayment'])->name('createMomoPayment');
Route::post('/momo-ipn', [BookingController::class, 'handleMomoIPN'])->name('momo.ipn'); // IPN webhook từ MoMo

// Test MoMo payment success (chỉ dùng trong development)
Route::get('/test-momo-success/{customTourId}', [BookingController::class, 'testMomoPaymentSuccess'])->name('test.momo.success');

// Tour đã đặt
Route::get('/tour-booked', [TourBookedController::class, 'index'])
    ->name('tour-booked')
    ->middleware('checkLoginClient');

Route::post('/cancel-booking', [TourBookedController::class, 'cancelBooking'])->name('cancel-booking');


// ================== Contact ==================

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/create-contact', [ContactController::class, 'createContact'])->name('create-contact');


// ================== Search ==================

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search-voice-text', [SearchController::class, 'searchTours'])->name('search-voice-text');


// ================== ADMIN ==================

// Login admin
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginAdminController::class, 'index'])->name('admin.login');
    Route::post('/login-account', [LoginAdminController::class, 'loginAdmin'])->name('admin.login-account');
    Route::get('/logout', [LoginAdminController::class, 'logout'])->name('admin.logout');
});

// Admin có middleware
Route::prefix('admin')->middleware('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Quản lý admin
    Route::get('/admin', [AdminManagementController::class, 'index'])->name('admin.admin');
    Route::post('/update-admin', [AdminManagementController::class, 'updateAdmin'])->name('admin.update-admin');
    Route::post('/update-avatar', [AdminManagementController::class, 'updateAvatar'])->name('admin.update-avatar');

    // Quản lý user
    Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users');
    Route::post('/active-user', [UserManagementController::class, 'activeUser'])->name('admin.active-user');
    Route::post('/status-user', [UserManagementController::class, 'changeStatus'])->name('admin.status-user');

    // Quản lý tours
    Route::get('/tours', [ToursManagementController::class, 'index'])->name('admin.tours');
    Route::get('/page-add-tours', [ToursManagementController::class, 'pageAddTours'])->name('admin.page-add-tours');
    Route::post('/add-tours', [ToursManagementController::class, 'addTours'])->name('admin.add-tours');
    Route::post('/add-images-tours', [ToursManagementController::class, 'addImagesTours'])->name('admin.add-images-tours');
    Route::post('/add-timeline', [ToursManagementController::class, 'addTimeline'])->name('admin.add-timeline');
    Route::post('/delete-tour', [ToursManagementController::class, 'deleteTour'])->name('admin.delete-tour');
    Route::get('/tour-edit', [ToursManagementController::class, 'getTourEdit'])->name('admin.tour-edit');
    Route::post('/edit-tour', [ToursManagementController::class, 'updateTour'])->name('admin.edit-tour');
    Route::post('/add-temp-images', [ToursManagementController::class, 'uploadTempImagesTours'])->name('admin.add-temp-images');

    // Quản lý tours theo yêu cầu
    Route::get('/custom-tours', [CustomTourController::class, 'index'])->name('admin.custom_tours.index');
    Route::post('/custom-tours/confirm-booking', [CustomTourController::class, 'confirmBooking'])->name('admin.custom_tours.confirm-booking');
    Route::post('/custom-tours/finish-booking', [CustomTourController::class, 'finishBooking'])->name('admin.custom_tours.finish-booking');
    Route::get('/custom-tours/get-edit', [CustomTourController::class, 'getCustomTourEdit'])->name('admin.custom_tours.get-edit');
    Route::post('/custom-tours/update', [CustomTourController::class, 'updateCustomTour'])->name('admin.custom_tours.update');

    // Quản lý booking
    Route::get('/booking', [BookingManagementController::class, 'index'])->name('admin.booking');
    Route::post('/confirm-booking', [BookingManagementController::class, 'confirmBooking'])->name('admin.confirm-booking');
    Route::get('/booking-detail/{id?}', [BookingManagementController::class, 'showDetail'])->name('admin.booking-detail');
    Route::post('/finish-booking', [BookingManagementController::class, 'finishBooking'])->name('admin.finish-booking');
    Route::post('/received-money', [BookingManagementController::class, 'receiviedMoney'])->name('admin.received');

    // Gửi mail PDF
    Route::post('/admin/send-pdf', [BookingManagementController::class, 'sendPdf'])->name('admin.send.pdf');

    // Quản lý liên hệ
    Route::get('/contact', [ContactManagementController::class, 'index'])->name('admin.contact');
    Route::post('/reply-contact', [ContactManagementController::class, 'replyContact'])->name('admin.reply-contact');
});
