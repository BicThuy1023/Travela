@include('admin.blocks.header')
<div class="container body">
    <div class="main_container">
        @include('admin.blocks.sidebar')

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="page-title">
                    <div class="title_left">
                        <h3>Quản lý người dùng</h3>
                    </div>

                    <div class="title_right">
                        <div class="col-md-5 col-sm-5  form-group pull-right top_search">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search for...">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button">Go!</button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>

                <div class="x_panel">
                    <div class="x_content row user-cards-container">
                        @foreach ($users as $user)
                            <div class="col-md-4 col-sm-4  profile_details">
                                <div class="well profile_view">
                                    <div class="col-sm-12">
                                        <h4 class="brief"><i>{{ $user->isActive }}</i></h4>
                                        <div class="left col-md-7 col-sm-7">
                                            <h2>{{ $user->fullName }}</h2>
                                            <p><strong>About: </strong> {{ $user->username }} </p>
                                            <ul class="list-unstyled">
                                                <li><i class="fa fa-building"></i> Address: {{ $user->address }}</li>
                                                <li><i class="fa fa-phone"></i> Phone #: {{ $user->phoneNumber }}</li>
                                            </ul>
                                        </div>
                                        <div class="right col-md-5 col-sm-5 text-center">
                                            <img src="{{ asset('admin/assets/images/user-profile/' . $user->avatar) }}"
                                                alt="" class="img-circle img-fluid user-avatar-fixed">
                                        </div>
                                    </div>
                                    <div class=" profile-bottom text-center">
                                        <div class=" col-sm-12 emphasis" style="display: flex; justify-content: end">
                                            @if ($user->isActive == 'Chưa kích hoạt')
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    data-attr='{"userId": "{{ $user->userId }}", "action": "{{ route('admin.active-user') }}"}'
                                                    id="btn-active">
                                                    <i class="fa fa-check"> </i> Kích hoạt
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-primary btn-warning"
                                                data-attr='{"userId": "{{ $user->userId }}", "action": "{{ route('admin.status-user') }}", "status": "b"}'
                                                id="btn-ban"
                                                style="{{ $user->status === 'b' ? 'display: none;' : '' }}">
                                                <i class="fa fa-ban"> </i> Chặn
                                            </button>

                                            <button type="button" class="btn btn-primary btn-warning"
                                                data-attr='{"userId": "{{ $user->userId }}", "action": "{{ route('admin.status-user') }}", "status": ""}'
                                                id="btn-unban"
                                                style="{{ $user->status !== 'b' ? 'display: none;' : '' }}">
                                                <i class="fa fa-ban"> </i> Bỏ chặn
                                            </button>

                                            <button type="button" class="btn btn-primary btn-danger"
                                                data-attr='{"userId": "{{ $user->userId }}", "action": "{{ route('admin.status-user') }}", "status": "d"}'
                                                id="btn-delete"
                                                style="{{ $user->status === 'd' ? 'display: none;' : '' }}">
                                                <i class="fa fa-close"> </i> Xóa
                                            </button>
                                            <button type="button" class="btn btn-primary btn-danger"
                                                data-attr='{"userId": "{{ $user->userId }}", "action": "{{ route('admin.status-user') }}", "status": ""}'
                                                id="btn-restore"
                                                style="{{ $user->status !== 'd' ? 'display: none;' : '' }}">
                                                <i class="fa fa-close"> </i> Khôi phục
                                            </button>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
        <!-- /page content -->
    </div>
</div>
@include('admin.blocks.footer')

<style>
    /* Đảm bảo tất cả avatar có cùng kích thước */
    .user-avatar-fixed {
        width: 120px !important;
        height: 120px !important;
        object-fit: cover;
        border-radius: 50%;
        display: block;
        margin: 0 auto;
    }
    
    /* Đảm bảo các thẻ user card có cùng chiều cao và chiều rộng */
    .user-cards-container {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .user-cards-container > .profile_details {
        padding: 0 10px;
        margin-bottom: 15px;
        display: flex;
        width: 100%;
    }
    
    @media (min-width: 768px) {
        .user-cards-container > .profile_details {
            width: 50%;
        }
    }
    
    @media (min-width: 992px) {
        .user-cards-container > .profile_details {
            width: 33.333333%;
        }
    }
    
    /* Bỏ margin-bottom của các card ở hàng cuối */
    .user-cards-container > .profile_details:last-child,
    .user-cards-container > .profile_details:nth-last-child(2),
    .user-cards-container > .profile_details:nth-last-child(3) {
        margin-bottom: 0;
    }
    
    @media (min-width: 768px) {
        .user-cards-container > .profile_details:nth-last-child(-n+2) {
            margin-bottom: 0;
        }
    }
    
    @media (min-width: 992px) {
        .user-cards-container > .profile_details:nth-last-child(-n+3) {
            margin-bottom: 0;
        }
    }
    
    .profile_view {
        width: 100%;
        min-height: 220px;
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 10px;
        margin-bottom: 0;
    }
    
    .profile_view > .col-sm-12:first-child {
        flex: 1;
        min-height: 140px;
        display: block;
    }
    
    .profile_view .left {
        min-height: 100px;
    }
    
    .profile_view .left h2 {
        margin-top: 5px;
        margin-bottom: 5px;
        font-size: 18px;
    }
    
    .profile_view .left p {
        margin-bottom: 5px;
        font-size: 13px;
    }
    
    .profile_view .left ul {
        margin-bottom: 5px;
        font-size: 12px;
    }
    
    .profile_view .left ul li {
        margin-bottom: 3px;
    }
    
    .profile_view .right {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 90px;
    }
    
    .profile-bottom {
        margin-top: auto;
        padding-top: 8px;
        border-top: 1px solid #e0e0e0;
        min-height: 45px;
    }
    
    .profile-bottom .emphasis {
        min-height: 30px;
        align-items: center;
    }
    
    .profile_view .brief {
        margin-bottom: 8px;
        font-size: 12px;
    }
    
    /* Loại bỏ khoảng trắng dưới cùng */
    .x_panel {
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .x_panel .x_content {
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .right_col[role="main"] > div:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
    }
</style>
