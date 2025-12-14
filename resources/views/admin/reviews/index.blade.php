@include('admin.blocks.header')
<style>
     .reviews-page {
         padding: 20px;
     }
     
     .reviews-list-container {
         margin: 20px auto;
         max-width: 1000px;
     }
     
     .page-title {
         font-size: 28px;
         font-weight: 700;
         color: #2c3e50;
         margin-bottom: 10px;
     }
     
     .page-subtitle {
         font-size: 14px;
         color: #666;
         margin-bottom: 30px;
     }
    
     .filter-section {
         background: white;
         border-radius: 12px;
         padding: 25px;
         margin: 0 auto 30px auto;
         max-width: 1000px;
         box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
     }
    
    .filter-row {
        display: flex;
        gap: 15px;
        align-items: end;
    }
    
    .filter-group {
        flex: 1;
    }
    
    .filter-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        margin-bottom: 8px;
    }
    
    .filter-input {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .filter-input:focus {
        outline: none;
        border-color: #73d13d;
    }
    
    .filter-select {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        transition: border-color 0.3s;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #73d13d;
    }
    
     .review-card {
         background: white;
         border-radius: 12px;
         padding: 25px;
         margin: 0 auto 20px auto;
         max-width: 100%;
         box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
         transition: all 0.3s ease;
         position: relative;
     }
    
    .review-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .review-card.hidden {
        opacity: 0.6;
        border-left: 4px solid #dc3545;
    }
    
    .review-card:not(.hidden) {
        border-left: 4px solid #73d13d;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }
    
    .review-info {
        flex: 1;
    }
    
     .review-tour-name {
         font-size: 18px;
         font-weight: 600;
         color: #2c3e50;
         margin-bottom: 10px;
     }
    
    .review-user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    
     .review-user-name {
         font-size: 15px;
         font-weight: 500;
         color: #666;
     }
     
     .review-date {
         font-size: 13px;
         color: #999;
     }
    
    .review-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 10px;
    }
    
     .review-rating .stars {
         color: #ffc107;
         font-size: 18px;
     }
     
     .review-rating .rating-number {
         font-size: 16px;
         font-weight: 600;
         color: #666;
         margin-left: 8px;
     }
    
     .review-comment {
         font-size: 15px;
         color: #555;
         line-height: 1.6;
         margin-bottom: 15px;
         padding: 18px;
         background: #f8f9fa;
         border-radius: 8px;
     }
    
    .review-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
     .review-helpful {
         display: flex;
         align-items: center;
         gap: 6px;
         font-size: 15px;
         color: #666;
     }
     
     .review-helpful i {
         font-size: 16px;
     }
    
    .review-helpful i {
        color: #73d13d;
    }
    
    .review-actions {
        display: flex;
        gap: 10px;
    }
    
     .btn-delete-review {
         background: #dc3545;
         border: none;
         color: white;
         padding: 10px 20px;
         border-radius: 8px;
         font-size: 14px;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
         display: inline-flex;
         align-items: center;
         gap: 8px;
     }
    
    .btn-delete-review:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }
    
    .badge-hidden {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #dc3545;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .empty-reviews {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    
    .empty-reviews i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-reviews h3 {
        font-size: 24px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .empty-reviews p {
        font-size: 16px;
        color: #999;
    }
</style>

<div class="container body">
    <div class="main_container">
        @include('admin.blocks.sidebar')

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="reviews-page">
        <h1 class="page-title">Đánh giá</h1>
        <p class="page-subtitle">Tổng cộng: {{ $reviews->total() }} đánh giá</p>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="{{ route('admin.reviews.index') }}" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group" style="flex: 2;">
                        <label>Tìm kiếm theo tour hoặc người dùng</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="filter-input" 
                            placeholder="Tìm kiếm theo tour hoặc người dùng..."
                            value="{{ request('search') }}"
                        >
                    </div>
                    <div class="filter-group" style="flex: 1;">
                        <label>Lọc theo số sao</label>
                        <select name="rating" class="filter-select" onchange="document.getElementById('filterForm').submit();">
                            <option value="">Tất cả đánh giá</option>
                            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 sao</option>
                            <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 sao</option>
                            <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 sao</option>
                            <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 sao</option>
                            <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 sao</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Reviews List -->
        <div class="reviews-list-container">
        @if($reviews->count() > 0)
            @foreach($reviews as $review)
            <div class="review-card {{ !$review->is_visible ? 'hidden' : '' }}">
                @if(!$review->is_visible)
                <span class="badge-hidden">Đã ẩn</span>
                @endif
                
                <div class="review-header">
                    <div class="review-info">
                        <div class="review-tour-name">
                            {{ $review->tour_title ?? 'Tour không tồn tại' }}
                        </div>
                        <div class="review-user-info">
                            <span class="review-user-name">
                                {{ $review->user_fullName ?? 'Người dùng không tồn tại' }}
                            </span>
                            <span class="review-date">
                                {{ \Carbon\Carbon::parse($review->timestamp ?? $review->created_at ?? now())->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <div class="review-rating">
                            <div class="stars">
                                @for($i = 0; $i < 5; $i++)
                                    @if($i < $review->rating)
                                        <i class="fa fa-star"></i>
                                    @else
                                        <i class="fa fa-star-o"></i>
                                    @endif
                                @endfor
                            </div>
                            <span class="rating-number">{{ $review->rating }}/5</span>
                        </div>
                    </div>
                </div>
                
                <div class="review-comment">
                    {{ $review->comment }}
                </div>
                
                <div class="review-footer">
                    <div class="review-helpful">
                        <i class="fa fa-thumbs-up"></i>
                        <span>{{ $review->helpful_count ?? 0 }} lượt hữu ích</span>
                    </div>
                    <div class="review-actions">
                        <button 
                            class="btn-delete-review" 
                            data-review-id="{{ $review->id ?? '' }}"
                            data-tour-id="{{ $review->tourId }}"
                            data-user-id="{{ $review->userId }}"
                            data-rating="{{ $review->rating }}"
                            data-comment="{{ htmlspecialchars($review->comment ?? '', ENT_QUOTES, 'UTF-8') }}"
                            data-timestamp="{{ $review->timestamp ?? ($review->created_at ?? '') }}"
                            onclick="deleteReview(this)"
                            title="Xóa đánh giá"
                        >
                            <i class="fa fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
            
            <!-- Pagination -->
            <div class="pagination-wrapper" style="margin-top: 30px;">
                {{ $reviews->links() }}
            </div>
        @else
            <div class="empty-reviews">
                <i class="fa fa-star-o"></i>
                <h3>Chưa có đánh giá nào</h3>
                <p>Hiện tại hệ thống chưa có đánh giá nào.</p>
            </div>
        @endif
        </div>
            </div>
        </div>
        <!-- /page content -->
    </div>
</div>

@include('admin.blocks.footer')

<script>
function deleteReview(button) {
    if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) {
        return;
    }
    
    // Lấy dữ liệu từ data attributes
    const reviewId = button.getAttribute('data-review-id') || '0';
    const tourId = button.getAttribute('data-tour-id') || '';
    const userId = button.getAttribute('data-user-id') || '';
    const rating = button.getAttribute('data-rating') || '';
    const comment = button.getAttribute('data-comment') || '';
    const timestamp = button.getAttribute('data-timestamp') || '';
    
    console.log('Deleting review:', {
        id: reviewId,
        tourId: tourId,
        userId: userId,
        rating: rating,
        comment: comment,
        timestamp: timestamp
    });
    
    if (!tourId || !userId) {
        alert('Thiếu thông tin cần thiết. Vui lòng thử lại.');
        return;
    }
    
    // Sử dụng JSON thay vì FormData
    const data = {
        tourId: tourId,
        userId: userId
    };
    if (rating) data.rating = rating;
    if (comment) data.comment = comment;
    if (timestamp) data.timestamp = timestamp;
    
    fetch(`/admin/reviews/${reviewId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa đánh giá');
    });
}

function toggleVisibility(id) {
    fetch(`/admin/reviews/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi thay đổi trạng thái');
    });
}
</script>

