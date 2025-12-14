<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\admin\ToursModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionManagementController extends Controller
{
    private $toursModel;

    public function __construct()
    {
        $this->toursModel = new ToursModel();
    }

    /**
     * Hiển thị danh sách mã khuyến mãi
     */
    public function index()
    {
        $title = 'Quản lý Mã khuyến mãi';

        // Thống kê
        $totalPromotions = Promotion::count();
        $activePromotions = Promotion::where('is_active', 1)->count();
        $totalUsage = Promotion::sum('usage_count');

        // Danh sách mã khuyến mãi
        $promotions = Promotion::with('tours')
            ->orderBy('created_at', 'DESC')
            ->get();

        return view('admin.promotions.index', compact('title', 'promotions', 'totalPromotions', 'activePromotions', 'totalUsage'));
    }

    /**
     * Hiển thị form tạo mã mới
     */
    public function create()
    {
        try {
            $title = 'Tạo mã khuyến mãi';
            
            // Lấy danh sách tours (chỉ lấy các tour đang available)
            $tours = $this->toursModel->getAllTours();
            
            // Kiểm tra nếu tours rỗng
            if ($tours->isEmpty()) {
                toastr()->warning('Hiện chưa có tour nào. Vui lòng tạo tour trước!');
            }

            return view('admin.promotions.create', compact('title', 'tours'));
        } catch (Exception $e) {
            Log::error('Error in PromotionManagementController@create: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            toastr()->error('Có lỗi xảy ra khi tải trang tạo mã khuyến mãi: ' . $e->getMessage());
            return redirect()->route('admin.promotions.index');
        }
    }

    /**
     * Lưu mã khuyến mãi mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tbl_promotions,code',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'max_discount_amount' => 'nullable|integer|min:0',
            'apply_type' => 'required|in:global,specific_tours',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'tour_ids' => 'nullable|array',
            'tour_ids.*' => 'exists:tbl_tours,tourId',
        ], [
            'code.unique' => 'Mã khuyến mãi đã tồn tại',
            'discount_value.min' => 'Giá trị giảm phải lớn hơn 0',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu',
        ]);

        try {
            DB::beginTransaction();

            // Tạo promotion
            $promotion = Promotion::create([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'min_order_amount' => $validated['min_order_amount'] ?? 0,
                'max_discount_amount' => $validated['max_discount_amount'] ?? 0,
                'apply_type' => $validated['apply_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'usage_limit' => $validated['usage_limit'] ?? 0,
                'per_user_limit' => $validated['per_user_limit'] ?? 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ]);

            // Nếu là specific_tours, lưu danh sách tour vào pivot
            if ($validated['apply_type'] === 'specific_tours' && !empty($validated['tour_ids'])) {
                $promotion->tours()->attach($validated['tour_ids']);
            }

            DB::commit();

            toastr()->success('Tạo mã khuyến mãi thành công!');
            return redirect()->route('admin.promotions.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating promotion: ' . $e->getMessage());
            toastr()->error('Có lỗi xảy ra khi tạo mã khuyến mãi!');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Hiển thị chi tiết mã khuyến mãi
     */
    public function show($id)
    {
        $promotion = Promotion::with('tours')->findOrFail($id);
        $title = 'Chi tiết mã khuyến mãi: ' . $promotion->code;

        return view('admin.promotions.show', compact('title', 'promotion'));
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit($id)
    {
        $title = 'Chỉnh sửa mã khuyến mãi';
        $promotion = Promotion::with('tours')->findOrFail($id);
        $tours = $this->toursModel->getAllTours();
        $selectedTourIds = $promotion->tours->pluck('tourId')->toArray();

        return view('admin.promotions.edit', compact('title', 'promotion', 'tours', 'selectedTourIds'));
    }

    /**
     * Cập nhật mã khuyến mãi
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tbl_promotions,code,' . $id,
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'max_discount_amount' => 'nullable|integer|min:0',
            'apply_type' => 'required|in:global,specific_tours',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:0',
            'per_user_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'tour_ids' => 'nullable|array',
            'tour_ids.*' => 'exists:tbl_tours,tourId',
        ], [
            'code.unique' => 'Mã khuyến mãi đã tồn tại',
            'discount_value.min' => 'Giá trị giảm phải lớn hơn 0',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu',
        ]);

        try {
            DB::beginTransaction();

            // Cập nhật promotion
            $promotion->update([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'min_order_amount' => $validated['min_order_amount'] ?? 0,
                'max_discount_amount' => $validated['max_discount_amount'] ?? 0,
                'apply_type' => $validated['apply_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'usage_limit' => $validated['usage_limit'] ?? 0,
                'per_user_limit' => $validated['per_user_limit'] ?? 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ]);

            // Cập nhật danh sách tour
            if ($validated['apply_type'] === 'specific_tours' && !empty($validated['tour_ids'])) {
                $promotion->tours()->sync($validated['tour_ids']);
            } else {
                $promotion->tours()->detach();
            }

            DB::commit();

            toastr()->success('Cập nhật mã khuyến mãi thành công!');
            return redirect()->route('admin.promotions.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating promotion: ' . $e->getMessage());
            toastr()->error('Có lỗi xảy ra khi cập nhật mã khuyến mãi!');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Xóa mã khuyến mãi
     */
    public function destroy($id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->tours()->detach();
            $promotion->delete();

            toastr()->success('Xóa mã khuyến mãi thành công!');
            return redirect()->route('admin.promotions.index');

        } catch (Exception $e) {
            Log::error('Error deleting promotion: ' . $e->getMessage());
            toastr()->error('Có lỗi xảy ra khi xóa mã khuyến mãi!');
            return redirect()->back();
        }
    }

    /**
     * Bật/Tắt trạng thái mã khuyến mãi
     */
    public function toggleStatus($id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->is_active = !$promotion->is_active;
            $promotion->save();

            $status = $promotion->is_active ? 'kích hoạt' : 'vô hiệu hóa';
            toastr()->success("Đã {$status} mã khuyến mãi thành công!");
            return redirect()->back();

        } catch (Exception $e) {
            Log::error('Error toggling promotion status: ' . $e->getMessage());
            toastr()->error('Có lỗi xảy ra!');
            return redirect()->back();
        }
    }
}
