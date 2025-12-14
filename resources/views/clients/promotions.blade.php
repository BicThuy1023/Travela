@include('clients.blocks.header')
@include('clients.blocks.banner')

<style>
    .promotions-page {
        padding: 60px 0;
        background: #f8f9fa;
    }
    
    .promotions-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .promotions-header h1 {
        font-size: 36px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .promotions-header p {
        font-size: 16px;
        color: #666;
    }
    
    .coupon-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        display: flex;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .coupon-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .coupon-main {
        flex: 1;
        padding: 25px;
        position: relative;
    }
    
    .coupon-main::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background: #f8f9fa;
        border-radius: 50%;
        border: 2px solid #ff8c42;
    }
    
    .coupon-main::before {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        width: 20px;
        height: 50%;
        background: #f8f9fa;
        border-top-right-radius: 12px;
    }
    
    .coupon-main::after {
        top: auto;
        bottom: 0;
        border-top-right-radius: 0;
        border-bottom-right-radius: 12px;
    }
    
    .coupon-badge {
        display: inline-block;
        background: linear-gradient(135deg, #ff8c42 0%, #ffb366 100%);
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .coupon-title {
        font-size: 20px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .coupon-description {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .coupon-code-section {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #ddd;
    }
    
    .coupon-code-label {
        font-size: 13px;
        color: #999;
    }
    
    .coupon-code {
        font-size: 16px;
        font-weight: bold;
        color: #73d13d;
        letter-spacing: 1px;
        font-family: 'Courier New', monospace;
    }
    
    .coupon-side {
        width: 180px;
        background: linear-gradient(135deg, #ff8c42 0%, #ffb366 100%);
        padding: 25px 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        position: relative;
    }
    
    .coupon-side::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background: #f8f9fa;
        border-radius: 50%;
    }
    
    .coupon-discount {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .coupon-min-order {
        font-size: 12px;
        opacity: 0.9;
        margin-bottom: 15px;
        line-height: 1.4;
    }
    
    .btn-copy-code {
        background: white;
        color: #ff8c42;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .btn-copy-code:hover {
        background: #f0f0f0;
        transform: scale(1.05);
    }
    
    .btn-copy-code.copied {
        background: #73d13d;
        color: white;
    }
    
    .empty-promotions {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .empty-promotions i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-promotions h3 {
        font-size: 24px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .empty-promotions p {
        font-size: 16px;
        color: #999;
    }
    
    .apply-type-badge {
        display: inline-block;
        background: #e8f5e9;
        color: #73d13d;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }
</style>

<section class="promotions-page">
    <div class="container">
        <div class="promotions-header">
            <h1>Mã khuyến mãi</h1>
            <p>Khám phá các ưu đãi hấp dẫn dành cho bạn</p>
        </div>

        @if($promotions->count() > 0)
        <div class="row">
            @foreach($promotions as $promotion)
            @php
                $isExpired = false;
                if ($promotion->usage_limit > 0 && $promotion->usage_count >= $promotion->usage_limit) {
                    $isExpired = true;
                }
            @endphp
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="coupon-card" style="{{ $isExpired ? 'opacity: 0.7;' : '' }}">
                    @if($isExpired)
                    <div style="position: absolute; top: 15px; right: 15px; background: #dc3545; color: white; padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; z-index: 10;">
                        Hết mã
                    </div>
                    @endif
                    <div class="coupon-main">
                        <span class="coupon-badge">
                            @if($promotion->apply_type === 'global')
                                Áp dụng cho tất cả các tour
                            @else
                                {{ $promotion->tours->count() }} tour cụ thể
                            @endif
                        </span>
                        
                        <h3 class="coupon-title">{{ $promotion->name }}</h3>
                        
                        <p class="coupon-description">
                            {{ $promotion->description ?? 'Ưu đãi đặc biệt dành cho bạn' }}
                        </p>
                        
                        <div class="coupon-code-section">
                            <span class="coupon-code-label">Mã ưu đãi:</span>
                            <span class="coupon-code" id="code-{{ $promotion->id }}" style="color: {{ $isExpired ? '#999' : '#73d13d' }};">{{ $promotion->code }}</span>
                        </div>
                        
                        <div style="margin-top: 10px; font-size: 12px; color: #999;">
                            <i class="fa fa-calendar"></i> 
                            Áp dụng từ {{ \Carbon\Carbon::parse($promotion->start_date)->format('d/m/Y') }} 
                            đến {{ \Carbon\Carbon::parse($promotion->end_date)->format('d/m/Y') }}
                        </div>
                    </div>
                    
                    <div class="coupon-side">
                        <div class="coupon-discount">
                            @if($promotion->discount_type === 'percent')
                                Giảm {{ $promotion->discount_value }}%
                            @else
                                Giảm {{ number_format($promotion->discount_value, 0, ',', '.') }} VNĐ
                            @endif
                        </div>
                        
                        @if($promotion->min_order_amount > 0)
                        <div class="coupon-min-order">
                            Đơn tối thiểu:<br>
                            {{ number_format($promotion->min_order_amount, 0, ',', '.') }} VNĐ
                        </div>
                        @else
                        <div class="coupon-min-order" style="opacity: 0.6;">
                            Không giới hạn<br>giá trị đơn
                        </div>
                        @endif
                        
                        <button class="btn-copy-code" onclick="copyPromoCode('{{ $promotion->code }}', this)" style="{{ $isExpired ? 'background: #ccc; cursor: not-allowed; opacity: 0.6;' : '' }}">
                            <i class="fa fa-copy"></i> Sao chép mã
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-promotions">
            <i class="fa fa-tag"></i>
            <h3>Hiện chưa có mã khuyến mãi</h3>
            <p>Vui lòng quay lại sau để nhận các ưu đãi hấp dẫn!</p>
        </div>
        @endif
    </div>
</section>

<script>
function copyPromoCode(code, button) {
    // Tạo một textarea tạm thời để copy
    const textarea = document.createElement('textarea');
    textarea.value = code;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        
        // Thay đổi text và style của button
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fa fa-check"></i> Đã sao chép!';
        button.classList.add('copied');
        
        // Hiển thị thông báo
        if (typeof toastr !== 'undefined') {
            toastr.success('Đã sao chép mã: ' + code);
        } else {
            alert('Đã sao chép mã: ' + code);
        }
        
        // Khôi phục sau 2 giây
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('copied');
        }, 2000);
        
    } catch (err) {
        console.error('Lỗi khi sao chép:', err);
        alert('Không thể sao chép mã. Vui lòng sao chép thủ công: ' + code);
    }
    
    document.body.removeChild(textarea);
}
</script>

@include('clients.blocks.footer')

