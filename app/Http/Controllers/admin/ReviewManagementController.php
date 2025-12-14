<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewManagementController extends Controller
{
    /**
     * Hiển thị danh sách đánh giá
     */
    public function index(Request $request)
    {
        $title = 'Đánh giá';

        // Sử dụng DB::table để đảm bảo có đầy đủ dữ liệu
        $query = DB::table('tbl_reviews')
            ->leftJoin('tbl_tours', 'tbl_tours.tourId', '=', 'tbl_reviews.tourId')
            ->leftJoin('tbl_users', 'tbl_users.userId', '=', 'tbl_reviews.userId')
            ->select(
                'tbl_reviews.*',
                'tbl_tours.title as tour_title',
                'tbl_users.fullName as user_fullName'
            );

        // Tìm kiếm theo tour hoặc người dùng
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tbl_tours.title', 'like', "%{$search}%")
                  ->orWhere('tbl_users.fullName', 'like', "%{$search}%");
            });
        }

        // Lọc theo số sao
        if ($request->has('rating') && $request->rating) {
            $query->where('tbl_reviews.rating', $request->rating);
        }

        // Sắp xếp theo ngày mới nhất
        $reviews = $query->orderBy('tbl_reviews.timestamp', 'DESC')
            ->paginate(10);

        return view('admin.reviews.index', compact('title', 'reviews'));
    }

    /**
     * Xóa đánh giá
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Lấy dữ liệu từ request (hỗ trợ cả JSON và form data)
            $allData = $request->all();
            if ($request->isJson()) {
                $allData = array_merge($allData, $request->json()->all());
            }
            
            $tourId = $allData['tourId'] ?? null;
            $userId = $allData['userId'] ?? null;
            $rating = $allData['rating'] ?? null;
            $comment = $allData['comment'] ?? null;
            $timestamp = $allData['timestamp'] ?? null;
            
            Log::info('Delete review request', [
                'id' => $id,
                'tourId' => $tourId,
                'userId' => $userId,
                'rating' => $rating,
                'comment' => $comment,
                'timestamp' => $timestamp,
                'allData' => $allData
            ]);
            
            // Kiểm tra thông tin bắt buộc
            if (!$tourId || !$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin cần thiết: tourId và userId là bắt buộc'
                ], 400);
            }
            
            // Thử xóa bằng cột id trước (nếu có và id hợp lệ)
            if ($id && $id != 'null' && $id != '0' && is_numeric($id)) {
                try {
                    $deleted = DB::table('tbl_reviews')->where('id', $id)->delete();
                    if ($deleted > 0) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Xóa đánh giá thành công'
                        ]);
                    }
                } catch (Exception $e1) {
                    // Nếu lỗi do không có cột id, tiếp tục dùng composite key
                    Log::info('Cannot delete by id, trying composite key: ' . $e1->getMessage());
                }
            }
            
            // Sử dụng composite key từ request
            $query = DB::table('tbl_reviews')
                ->where('tourId', $tourId)
                ->where('userId', $userId);
            
            // Thêm các điều kiện bổ sung để đảm bảo xóa đúng review
            if ($rating) {
                $query->where('rating', $rating);
            }
            if ($comment) {
                $query->where('comment', $comment);
            }
            if ($timestamp) {
                $query->where('timestamp', $timestamp);
            }
            
            $deleted = $query->delete();
            
            if ($deleted > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Xóa đánh giá thành công'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đánh giá phù hợp để xóa. Vui lòng thử lại.'
                ], 404);
            }
        } catch (Exception $e) {
            Log::error('Error deleting review: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa đánh giá: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ẩn/hiện đánh giá
     */
    public function toggleVisibility($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->is_visible = !$review->is_visible;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => $review->is_visible ? 'Hiển thị đánh giá thành công' : 'Ẩn đánh giá thành công',
                'is_visible' => $review->is_visible
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thay đổi trạng thái: ' . $e->getMessage()
            ], 500);
        }
    }
}

