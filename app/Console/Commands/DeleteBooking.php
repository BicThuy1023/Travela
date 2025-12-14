<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:delete {bookingId : ID của booking cần xóa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa booking và dữ liệu liên quan từ database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bookingId = $this->argument('bookingId');
        
        $this->info("Đang kiểm tra booking ID: {$bookingId}...");
        
        // Kiểm tra booking có tồn tại không
        $booking = DB::table('tbl_booking')
            ->where('bookingId', $bookingId)
            ->first();
        
        if (!$booking) {
            $this->error("Không tìm thấy booking với ID: {$bookingId}");
            return Command::FAILURE;
        }
        
        $this->info("Tìm thấy booking:");
        $this->line("  - Booking ID: {$booking->bookingId}");
        $this->line("  - Tên khách hàng: " . ($booking->fullName ?? 'N/A'));
        $this->line("  - Email: " . ($booking->email ?? 'N/A'));
        $this->line("  - Tour ID: " . ($booking->tourId ?? 'N/A'));
        $this->line("  - Custom Tour ID: " . ($booking->custom_tour_id ?? 'N/A'));
        
        // Xác nhận xóa
        if (!$this->confirm('Bạn có chắc chắn muốn xóa booking này?', true)) {
            $this->info('Đã hủy thao tác xóa.');
            return Command::SUCCESS;
        }
        
        try {
            DB::beginTransaction();
            
            // 1. Xóa từ tbl_checkout (nếu có)
            $checkoutDeleted = DB::table('tbl_checkout')
                ->where('bookingId', $bookingId)
                ->delete();
            
            if ($checkoutDeleted > 0) {
                $this->info("✓ Đã xóa {$checkoutDeleted} bản ghi từ tbl_checkout");
            } else {
                $this->warn("  Không tìm thấy bản ghi trong tbl_checkout");
            }
            
            // 2. Xóa từ tbl_booking
            $bookingDeleted = DB::table('tbl_booking')
                ->where('bookingId', $bookingId)
                ->delete();
            
            if ($bookingDeleted > 0) {
                $this->info("✓ Đã xóa {$bookingDeleted} bản ghi từ tbl_booking");
            }
            
            // 3. Kiểm tra custom_tour (chỉ xóa nếu không còn booking nào khác dùng)
            if ($booking->custom_tour_id) {
                $otherBookings = DB::table('tbl_booking')
                    ->where('custom_tour_id', $booking->custom_tour_id)
                    ->where('bookingId', '!=', $bookingId)
                    ->count();
                
                if ($otherBookings == 0) {
                    $this->warn("  Custom tour ID {$booking->custom_tour_id} không còn booking nào sử dụng.");
                    $this->warn("  (Không tự động xóa custom_tour, bạn có thể xóa thủ công nếu cần)");
                }
            }
            
            DB::commit();
            
            $this->info("\n✓ Đã xóa booking ID {$bookingId} thành công!");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Lỗi khi xóa booking: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
