@include('admin.blocks.header')
<div class="container body">
    <div class="main_container">
        @include('admin.blocks.sidebar')

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="page-title">
                    <div class="title_left">
                        <h3>Quản lý liên hệ</h3>
                    </div>
                </div>

                <div class="clearfix"></div>

                <!-- Statistics Cards - Đồng bộ với trang khuyến mãi -->
                <div class="row" style="margin-bottom: 30px;">
                    @php
                        $totalContacts = $contacts->count();
                        $unreadContacts = $contacts->where('isReply', 'n')->count();
                        $readContacts = $contacts->where('isReply', 'y')->count();
                    @endphp
                    <div class="col-md-4 col-sm-4">
                        <div class="promo-stats-card">
                            <div class="step-icon">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <h2>Tổng liên hệ</h2>
                            <div class="stat-value">{{ $totalContacts }}</div>
                            <div class="stat-subtitle">Tất cả liên hệ khách hàng</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <div class="promo-stats-card orange">
                            <div class="step-icon">
                                <i class="fa fa-clock-o"></i>
                            </div>
                            <h2>Chưa phản hồi</h2>
                            <div class="stat-value">{{ $unreadContacts }}</div>
                            <div class="stat-subtitle">Liên hệ cần xử lý</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <div class="promo-stats-card green-light">
                            <div class="step-icon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                            <h2>Đã phản hồi</h2>
                            <div class="stat-value">{{ $readContacts }}</div>
                            <div class="stat-subtitle">Liên hệ đã xử lý</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="x_panel" style="box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-radius: 12px; border: none;">
                            <div class="x_title" style="border-bottom: 2px solid #f0f0f0; padding: 20px 30px;">
                                <h2 style="font-size: 24px; font-weight: 600; color: #2c3e50; margin: 0;">
                                    <i class="fa fa-comments" style="color: #73d13d; margin-right: 10px;"></i>
                                    Danh sách liên hệ
                                </h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content" style="padding: 30px;">
                                <div class="row">
                                    <!-- Contact List -->
                                    <div class="col-md-5">
                                        <div class="contact-list-container">
                                            <div class="contact-list-header">
                                                <h4 style="margin: 0; color: #2c3e50; font-weight: 600;">
                                                    <i class="fa fa-list" style="margin-right: 8px;"></i>
                                                    Liên hệ khách hàng
                                                </h4>
                                            </div>
                                            <div class="contact-list-body">
                                                @forelse ($contacts as $contact)
                                                    <div class="contact-card {{ ($contact->isReply ?? 'n') == 'y' ? 'replied' : 'unread' }}"
                                                         data-name="{{ $contact->fullName }}"
                                                         data-email="{{ $contact->email }}"
                                                         data-phone="{{ $contact->phoneNumber }}"
                                                         data-message="{{ $contact->message }}"
                                                         data-contactid="{{ $contact->contactId }}"
                                                         data-isreply="{{ $contact->isReply ?? 'n' }}">
                                                        <div class="contact-card-header">
                                                            <div class="contact-avatar">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                            <div class="contact-info">
                                                                <h5 class="contact-name">{{ $contact->fullName }}</h5>
                                                                <p class="contact-phone">
                                                                    <i class="fa fa-phone" style="margin-right: 5px;"></i>
                                                                    {{ $contact->phoneNumber }}
                                                                </p>
                                                            </div>
                                                            <div class="contact-status">
                                                                @if(($contact->isReply ?? 'n') == 'y')
                                                                    <span class="status-badge replied-badge">
                                                                        <i class="fa fa-check-circle"></i> Đã phản hồi
                                                                    </span>
                                                                @else
                                                                    <span class="status-badge unread-badge">
                                                                        <i class="fa fa-clock-o"></i> Chưa phản hồi
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="contact-card-body">
                                                            <p class="contact-message-preview">
                                                                {{ Str::limit($contact->message, 80) }}
                                                            </p>
                                                            <div class="contact-meta">
                                                                <span class="contact-email">
                                                                    <i class="fa fa-envelope" style="margin-right: 5px;"></i>
                                                                    {{ $contact->email }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="empty-contacts">
                                                        <i class="fa fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                                        <p style="color: #999; margin: 0;">Chưa có liên hệ nào</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Detail View -->
                                    <div class="col-md-7">
                                        <div class="contact-detail-container">
                                            <div class="contact-detail-header">
                                                <h4 style="margin: 0; color: #2c3e50; font-weight: 600;">
                                                    <i class="fa fa-eye" style="margin-right: 8px;"></i>
                                                    Chi tiết liên hệ
                                                </h4>
                                            </div>
                                            <div class="contact-detail-body" id="contactDetailBody">
                                                <div class="empty-detail">
                                                    <i class="fa fa-hand-pointer-o" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                                                    <h4 style="color: #999; margin-bottom: 10px;">Chọn một liên hệ</h4>
                                                    <p style="color: #bbb;">Nhấp vào liên hệ bên trái để xem chi tiết</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /page content -->
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="compose col-md-6">
        <div class="compose-header">
            <h4 style="margin: 0; color: #2c3e50;">
                <i class="fa fa-reply" style="margin-right: 8px;"></i>
                Phản hồi liên hệ
            </h4>
            <button type="button" class="close compose-close">
                <span>×</span>
            </button>
        </div>

        <div class="compose-body">
            <div id="editor-contact" class="editor-wrapper"></div>
        </div>

        <div class="compose-footer">
            <button id="sendReplyBtn" class="send-reply-contact btn btn-success" type="button"
                data-url="{{ route('admin.reply-contact') }}">
                <i class="fa fa-paper-plane" style="margin-right: 5px;"></i>
                Gửi phản hồi
            </button>
        </div>
    </div>
    <!-- /Reply Modal -->

    @include('admin.blocks.footer')

    <style>
        /* Statistics Cards - Đồng bộ với trang khuyến mãi */
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

        /* Contact List Container */
        .contact-list-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            height: 600px;
            display: flex;
            flex-direction: column;
        }

        .contact-list-header {
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #e0e0e0;
        }

        .contact-list-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .contact-list-body::-webkit-scrollbar {
            width: 6px;
        }

        .contact-list-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .contact-list-body::-webkit-scrollbar-thumb {
            background: #73d13d;
            border-radius: 3px;
        }

        /* Contact Card */
        .contact-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            border-color: #73d13d;
            box-shadow: 0 4px 15px rgba(115, 209, 61, 0.2);
            transform: translateX(5px);
        }

        .contact-card.unread {
            border-left: 4px solid #ff9800;
            background: linear-gradient(to right, #fff8f0, white);
        }

        .contact-card.replied {
            border-left: 4px solid #28a745;
            opacity: 0.85;
        }

        .contact-card.active {
            border-color: #73d13d;
            background: linear-gradient(to right, #f0fdf4, white);
            box-shadow: 0 4px 15px rgba(115, 209, 61, 0.3);
        }

        .contact-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #73d13d, #95de64);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
        }

        .contact-card.replied .contact-avatar {
            background: linear-gradient(135deg, #28a745, #48c774);
        }

        .contact-info {
            flex: 1;
            min-width: 0;
        }

        .contact-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 5px 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .contact-phone {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .contact-status {
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .unread-badge {
            background: #fff3cd;
            color: #856404;
        }

        .replied-badge {
            background: #d4edda;
            color: #155724;
        }

        .contact-card-body {
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }

        .contact-message-preview {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
            margin: 0 0 10px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .contact-meta {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-email {
            font-size: 12px;
            color: #999;
        }

        /* Contact Detail Container */
        .contact-detail-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            height: 600px;
            display: flex;
            flex-direction: column;
        }

        .contact-detail-header {
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #e0e0e0;
        }

        .contact-detail-body {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .contact-detail-body::-webkit-scrollbar {
            width: 6px;
        }

        .contact-detail-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .contact-detail-body::-webkit-scrollbar-thumb {
            background: #73d13d;
            border-radius: 3px;
        }

        .empty-detail {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
        }

        .empty-contacts {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
        }

        /* Contact Detail Content */
        .contact-detail-content {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #73d13d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .detail-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .detail-info-item i {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #73d13d, #95de64);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .detail-info-item span {
            font-size: 15px;
            color: #2c3e50;
        }

        .detail-message-box {
            background: #f8f9fa;
            border-left: 4px solid #73d13d;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }

        .detail-message-box p {
            margin: 0;
            color: #555;
            line-height: 1.8;
            font-size: 14px;
        }

        .detail-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-reply {
            background: linear-gradient(135deg, #73d13d, #95de64);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(115, 209, 61, 0.4);
        }

        /* Reply Modal */
        .compose {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 500px;
            background: white;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            border-radius: 12px 12px 0 0;
            z-index: 1050;
            display: none;
            flex-direction: column;
            max-height: 80vh;
        }

        .compose.show {
            display: flex;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }

        .compose-header {
            padding: 20px;
            background: linear-gradient(135deg, #73d13d, #95de64);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
        }

        .compose-header .close {
            color: white;
            opacity: 0.8;
            font-size: 24px;
            cursor: pointer;
        }

        .compose-header .close:hover {
            opacity: 1;
        }

        .compose-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .compose-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .contact-list-container,
            .contact-detail-container {
                height: auto;
                min-height: 400px;
            }

            .compose {
                width: 100%;
            }
        }
    </style>

    <script>
        $(document).ready(function() {
            let currentContactId = null;

            // Handle contact card click
            $('.contact-card').on('click', function() {
                // Remove active class from all cards
                $('.contact-card').removeClass('active');
                // Add active class to clicked card
                $(this).addClass('active');

                // Get contact data
                const name = $(this).data('name');
                const email = $(this).data('email');
                const phone = $(this).data('phone');
                const message = $(this).data('message');
                const contactId = $(this).data('contactid');
                const isReply = $(this).data('isreply');

                currentContactId = contactId;

                // Build detail HTML
                const detailHTML = `
                    <div class="contact-detail-content">
                        <div class="detail-section">
                            <div class="detail-section-title">Thông tin khách hàng</div>
                            <div class="detail-info-item">
                                <i class="fa fa-user"></i>
                                <span><strong>Họ tên:</strong> ${name}</span>
                            </div>
                            <div class="detail-info-item">
                                <i class="fa fa-phone"></i>
                                <span><strong>Số điện thoại:</strong> ${phone}</span>
                            </div>
                            <div class="detail-info-item">
                                <i class="fa fa-envelope"></i>
                                <span><strong>Email:</strong> ${email}</span>
                            </div>
                            ${isReply === 'y' ? `
                                <div class="detail-info-item" style="background: #d4edda;">
                                    <i class="fa fa-check-circle" style="background: #28a745;"></i>
                                    <span style="color: #155724;"><strong>Trạng thái:</strong> Đã phản hồi</span>
                                </div>
                            ` : `
                                <div class="detail-info-item" style="background: #fff3cd;">
                                    <i class="fa fa-clock-o" style="background: #ff9800;"></i>
                                    <span style="color: #856404;"><strong>Trạng thái:</strong> Chưa phản hồi</span>
                                </div>
                            `}
                        </div>

                        <div class="detail-section">
                            <div class="detail-section-title">Nội dung liên hệ</div>
                            <div class="detail-message-box">
                                <p>${message.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>

                        ${isReply === 'n' ? `
                            <div class="detail-actions">
                                <button class="btn-reply" onclick="openReplyModal(${contactId}, '${email}')">
                                    <i class="fa fa-reply"></i>
                                    Phản hồi
                                </button>
                            </div>
                        ` : `
                            <div class="detail-actions">
                                <button class="btn-reply" onclick="openReplyModal(${contactId}, '${email}')" style="background: linear-gradient(135deg, #6c757d, #868e96);">
                                    <i class="fa fa-reply"></i>
                                    Phản hồi lại
                                </button>
                            </div>
                        `}
                    </div>
                `;

                // Update detail view
                $('#contactDetailBody').html(detailHTML);
            });

            // Open reply modal
            window.openReplyModal = function(contactId, email) {
                currentContactId = contactId;
                $('.compose').addClass('show');
                // Initialize editor if needed
                if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.instances['editor-contact']) {
                    CKEDITOR.replace('editor-contact');
                }
            };

            // Close reply modal
            $('.compose-close').on('click', function() {
                $('.compose').removeClass('show');
            });

            // Handle reply send
            $('#sendReplyBtn').on('click', function() {
                const url = $(this).data('url');
                let messageContent = '';

                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['editor-contact']) {
                    messageContent = CKEDITOR.instances['editor-contact'].getData();
                } else {
                    messageContent = $('#editor-contact').val();
                }

                if (!messageContent || messageContent.trim() === '') {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Vui lòng nhập nội dung phản hồi');
                    } else {
                        alert('Vui lòng nhập nội dung phản hồi');
                    }
                    return;
                }

                const contactCard = $(`.contact-card[data-contactid="${currentContactId}"]`);
                const email = contactCard.data('email');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        contactId: currentContactId,
                        email: email,
                        message: messageContent
                    },
                    success: function(response) {
                        if (response.success) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(response.message);
                            } else {
                                alert(response.message);
                            }
                            $('.compose').removeClass('show');
                            // Reload page to update status
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            if (typeof toastr !== 'undefined') {
                                toastr.error(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Có lỗi xảy ra';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                });
            });
        });
    </script>
</div>
