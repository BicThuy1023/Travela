@include('admin.blocks.header')
<style>
    /* Custom styles cho promotion management - Progress Card Style */
    .promo-stats-card {
        background: #f5f5f5;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        position: relative;
        cursor: pointer;
    }
    
    .promo-stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #d0d0d0;
    }
    
    .promo-stats-card.active {
        background: #e8f5e9;
        border-color: #73d13d;
    }
    
    .promo-stats-card .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #73d13d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(115, 209, 61, 0.3);
    }
    
    .promo-stats-card.orange .step-icon {
        background: #ff8c42;
        box-shadow: 0 2px 8px rgba(255, 140, 66, 0.3);
    }
    
    .promo-stats-card.green-light .step-icon {
        background: #73d13d;
        box-shadow: 0 2px 8px rgba(115, 209, 61, 0.3);
    }
    
    .promo-stats-card.inactive .step-icon {
        background: #e0e0e0;
        color: #666;
        box-shadow: none;
    }
    
    .promo-stats-card h2 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #2c3e50;
        line-height: 1.3;
    }
    
    .promo-stats-card .stat-value {
        font-size: 28px;
        font-weight: bold;
        margin: 8px 0 0 0;
        color: #2c3e50;
        line-height: 1;
    }
    
    .promo-stats-card .stat-subtitle {
        font-size: 13px;
        color: #666;
        margin-top: 4px;
        line-height: 1.4;
    }
    
    .promo-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid #73d13d;
    }
    
    .promo-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .promo-card.inactive {
        border-left-color: #6c757d;
        opacity: 0.85;
    }
    
    .promo-card-title {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .promo-code {
        background: linear-gradient(135deg, #73d13d 0%, #95de64 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 14px;
        letter-spacing: 1px;
        display: inline-block;
        margin: 5px 0;
        box-shadow: 0 2px 8px rgba(115, 209, 61, 0.3);
    }
    
    .promo-info {
        color: #555;
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 15px;
    }
    
    .promo-info strong {
        color: #2c3e50;
        font-weight: 600;
    }
    
    .promo-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .promo-badge.active {
        background: linear-gradient(135deg, #73d13d 0%, #95de64 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(115, 209, 61, 0.3);
    }
    
    .promo-badge.inactive {
        background: #e9ecef;
        color: #6c757d;
    }
    
    .promo-actions {
        display: flex;
        gap: 8px;
        flex-wrap: nowrap;
        margin-top: 15px;
        align-items: stretch;
        justify-content: space-between;
    }
    
    .btn-promo-edit {
        background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
        border: none;
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 1;
        min-width: 0;
        white-space: nowrap;
    }
    
    .btn-promo-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-promo-toggle {
        background: linear-gradient(135deg, #ff6b9d 0%, #ffc796 100%);
        border: none;
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 1;
        min-width: 0;
        white-space: nowrap;
    }
    
    .btn-promo-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 107, 157, 0.4);
    }
    
    .btn-promo-delete {
        background: linear-gradient(135deg, #ff8c42 0%, #ffb366 100%);
        border: none;
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 1;
        min-width: 0;
        white-space: nowrap;
    }
    
    .btn-promo-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
    }
    
    .btn-create-promo {
        background: linear-gradient(135deg, #73d13d 0%, #95de64 100%);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(115, 209, 61, 0.3);
    }
    
    .btn-create-promo:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(115, 209, 61, 0.4);
        color: white;
        background: linear-gradient(135deg, #95de64 0%, #b7eb8f 100%);
    }
    
    .page-title h3 {
        color: #2c3e50;
        font-weight: 600;
    }
</style>

<div class="container body">
    <div class="main_container">
        @include('admin.blocks.sidebar')

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="page-title">
                    <div class="title_left">
                        <h3>{{ $title }}</h3>
                    </div>
                    <div class="title_right">
                        <a href="{{ route('admin.promotions.create') }}" class="btn btn-create-promo">
                            <i class="fa fa-plus"></i> Tạo mã mới
                        </a>
                    </div>
                </div>

                <div class="clearfix"></div>

                <!-- Thống kê - Progress Card Style -->
                <div class="row">
                    <div class="col-md-4 col-sm-4">
                        <div class="promo-stats-card">
                            <div class="step-icon">
                                <i class="fa fa-tag"></i>
                            </div>
                            <h2>Tổng số mã</h2>
                            <div class="stat-value">{{ $totalPromotions }}</div>
                            <div class="stat-subtitle">Tất cả mã khuyến mãi</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <div class="promo-stats-card orange">
                            <div class="step-icon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                            <h2>Đang hoạt động</h2>
                            <div class="stat-value">{{ $activePromotions }}</div>
                            <div class="stat-subtitle">Mã đang được sử dụng</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <div class="promo-stats-card green-light">
                            <div class="step-icon">
                                <i class="fa fa-chart-line"></i>
                            </div>
                            <h2>Tổng lượt sử dụng</h2>
                            <div class="stat-value">{{ $totalUsage }}</div>
                            <div class="stat-subtitle">Tổng số lần áp dụng</div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách mã khuyến mãi -->
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2><i class="fa fa-list"></i> Danh sách mã khuyến mãi</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                @if($promotions->count() > 0)
                                <div class="row">
                                    @foreach($promotions as $promotion)
                                    <div class="col-md-3 col-sm-6">
                                        <div class="promo-card {{ !$promotion->is_active ? 'inactive' : '' }}">
                                            <div class="promo-card-title">
                                                <span>{{ $promotion->name }}</span>
                                                <span class="promo-badge {{ $promotion->is_active ? 'active' : 'inactive' }}">
                                                    {{ $promotion->is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                                </span>
                                            </div>
                                            
                                            <div class="promo-info">
                                                <div class="promo-code">{{ $promotion->code }}</div>
                                                <p style="margin-top: 10px;">
                                                    <strong>Loại giảm:</strong> 
                                                    @if($promotion->discount_type === 'percent')
                                                        <span style="color: #73d13d; font-weight: bold;">{{ $promotion->discount_value }}%</span>
                                                    @else
                                                        <span style="color: #ff8c42; font-weight: bold;">{{ number_format($promotion->discount_value, 0, ',', '.') }} VNĐ</span>
                                                    @endif
                                                </p>
                                                <p>
                                                    <strong>Áp dụng:</strong> 
                                                    @if($promotion->apply_type === 'global')
                                                        <span style="color: #73d13d;">Toàn bộ tours</span>
                                                    @else
                                                        <span style="color: #ff8c42;">{{ $promotion->tours->count() }} tour cụ thể</span>
                                                    @endif
                                                </p>
                                                <p>
                                                    <strong>Thời gian:</strong><br>
                                                    <small style="color: #6c757d;">
                                                        {{ \Carbon\Carbon::parse($promotion->start_date)->format('d/m/Y') }} - 
                                                        {{ \Carbon\Carbon::parse($promotion->end_date)->format('d/m/Y') }}
                                                    </small>
                                                </p>
                                                <p>
                                                    <strong>Lượt dùng:</strong> 
                                                    <span style="color: #ff8c42; font-weight: bold;">{{ $promotion->usage_count }}</span>
                                                    @if($promotion->usage_limit > 0)
                                                        / {{ $promotion->usage_limit }}
                                                    @else
                                                        <span style="color: #73d13d;">(Không giới hạn)</span>
                                                    @endif
                                                </p>
                                            </div>
                                            
                                            <div class="promo-actions">
                                                <a href="{{ route('admin.promotions.edit', $promotion->id) }}" class="btn-promo-edit">
                                                    <i class="fa fa-edit"></i> Sửa
                                                </a>
                                                <form action="{{ route('admin.promotions.toggle-status', $promotion->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn-promo-toggle">
                                                        <i class="fa fa-{{ $promotion->is_active ? 'ban' : 'check' }}"></i> 
                                                        {{ $promotion->is_active ? 'Vô hiệu' : 'Kích hoạt' }}
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.promotions.destroy', $promotion->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa mã này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-promo-delete">
                                                        <i class="fa fa-trash"></i> Xóa
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center" style="padding: 40px;">
                                    <i class="fa fa-tag" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                                    <p style="color: #999; font-size: 16px;">Chưa có mã khuyến mãi nào. Hãy tạo mã mới!</p>
                                    <a href="{{ route('admin.promotions.create') }}" class="btn-create-promo" style="margin-top: 20px;">
                                        <i class="fa fa-plus"></i> Tạo mã đầu tiên
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page content -->
    </div>
</div>
@include('admin.blocks.footer')
