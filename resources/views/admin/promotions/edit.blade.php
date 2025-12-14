@include('admin.blocks.header')
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
                </div>

                <div class="clearfix"></div>

                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Chỉnh sửa mã khuyến mãi</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <form action="{{ route('admin.promotions.update', $promotion->id) }}" method="POST" id="promotion-form">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="form-group">
                                        <label>Tên chương trình <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $promotion->name) }}" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Mã giảm giá <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control" value="{{ old('code', $promotion->code) }}" required>
                                        <small class="form-text text-muted">Mã sẽ được chuyển thành chữ hoa</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Mô tả</label>
                                        <textarea name="description" class="form-control" rows="3">{{ old('description', $promotion->description) }}</textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Loại giảm giá <span class="text-danger">*</span></label>
                                                <select name="discount_type" class="form-control" required>
                                                    <option value="percent" {{ old('discount_type', $promotion->discount_type) == 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                                                    <option value="fixed" {{ old('discount_type', $promotion->discount_type) == 'fixed' ? 'selected' : '' }}>Số tiền cố định (VNĐ)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Giá trị giảm <span class="text-danger">*</span></label>
                                                <input type="number" name="discount_value" class="form-control" value="{{ old('discount_value', $promotion->discount_value) }}" min="1" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Giá trị đơn tối thiểu (VNĐ)</label>
                                                <input type="number" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $promotion->min_order_amount) }}" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Giảm tối đa (VNĐ) - chỉ áp dụng khi giảm %</label>
                                                <input type="number" name="max_discount_amount" class="form-control" value="{{ old('max_discount_amount', $promotion->max_discount_amount) }}" min="0">
                                                <small class="form-text text-muted">0 = không giới hạn</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Áp dụng cho <span class="text-danger">*</span></label>
                                        <select name="apply_type" id="apply_type" class="form-control" required>
                                            <option value="global" {{ old('apply_type', $promotion->apply_type) == 'global' ? 'selected' : '' }}>Toàn bộ tours</option>
                                            <option value="specific_tours" {{ old('apply_type', $promotion->apply_type) == 'specific_tours' ? 'selected' : '' }}>Tour cụ thể</option>
                                        </select>
                                    </div>

                                    <div class="form-group" id="tour-selection" style="display: {{ old('apply_type', $promotion->apply_type) == 'specific_tours' ? 'block' : 'none' }};">
                                        <label>Chọn tours</label>
                                        <select name="tour_ids[]" class="form-control" multiple size="10">
                                            @foreach($tours as $tour)
                                                <option value="{{ $tour->tourId }}" {{ in_array($tour->tourId, old('tour_ids', $selectedTourIds)) ? 'selected' : '' }}>
                                                    {{ $tour->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Giữ Ctrl (Cmd trên Mac) để chọn nhiều tour</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $promotion->start_date->format('Y-m-d')) }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Ngày kết thúc <span class="text-danger">*</span></label>
                                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $promotion->end_date->format('Y-m-d')) }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tổng lượt dùng</label>
                                                <input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit', $promotion->usage_limit) }}" min="0">
                                                <small class="form-text text-muted">0 = không giới hạn</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Số lần / 1 user</label>
                                                <input type="number" name="per_user_limit" class="form-control" value="{{ old('per_user_limit', $promotion->per_user_limit) }}" min="0">
                                                <small class="form-text text-muted">0 = không giới hạn</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}>
                                                Kích hoạt
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">Cập nhật</button>
                                        <a href="{{ route('admin.promotions.index') }}" class="btn btn-secondary">Hủy</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page content -->
    </div>
</div>

<script>
document.getElementById('apply_type').addEventListener('change', function() {
    var tourSelection = document.getElementById('tour-selection');
    if (this.value === 'specific_tours') {
        tourSelection.style.display = 'block';
    } else {
        tourSelection.style.display = 'none';
    }
});
</script>

@include('admin.blocks.footer')

