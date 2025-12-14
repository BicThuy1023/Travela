<!DOCTYPE html>
<html lang="vi" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #{{ $invoice_booking->checkoutId }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .invoice-header {
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .invoice-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .invoice-header img {
            width: 40px;
            height: 40px;
            vertical-align: middle;
        }
        .invoice-info {
            margin-bottom: 30px;
        }
        .invoice-col {
            float: left;
            width: 33.33%;
            padding: 10px;
            box-sizing: border-box;
        }
        .invoice-col address {
            margin: 0;
            font-style: normal;
        }
        .invoice-col strong {
            display: block;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table thead {
            background-color: #f5f5f5;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            font-weight: bold;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .lead {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .text-muted {
            color: #777;
        }
        .well {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .invoice_payment-method {
            width: 80px;
            height: auto;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #5bc0de;
            color: white;
            border-radius: 3px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <section class="content invoice">
        <!-- title row -->
        <div class="row">
            <div class="invoice-header">
                <h3>
                    <img src="{{ public_path('admin/assets/images/icon/icon_office.png') }}" alt="">
                    {{ $invoice_booking->title }}
                    <small style="float: right; font-size: 14px;">Ngày: {{ date('d-m-Y', strtotime($invoice_booking->bookingDate)) }}</small>
                </h3>
            </div>
        </div>
        
        <!-- info row -->
        <div class="row invoice-info">
            <div class="invoice-col">
                Từ
                <address>
                    <strong>{{ $invoice_booking->fullName }}</strong>
                    <br>{{ $invoice_booking->address }}
                    <br>Số điện thoại: {{ $invoice_booking->phoneNumber }}
                    <br>Email: {{ $invoice_booking->email }}
                </address>
            </div>
            
            <div class="invoice-col">
                Đến
                <address>
                    <strong>Công ty ASIA Travel</strong>
                    <br>470 Trần Đại Nghĩa
                    <br>Bình Dương
                    <br>Phone: 1 (804) 123-9876
                    <br>Email: ttbthuy892@gmail.com
                </address>
            </div>
            
            <div class="invoice-col">
                <b>Mã hóa đơn #{{ $invoice_booking->checkoutId }}</b>
                <br><br>
                <b>Mã giao dịch:</b> {{ $invoice_booking->transactionId }}
                <br>
                <b>Ngày thanh toán:</b> {{ $invoice_booking->paymentDate ?? date('d-m-Y', strtotime($invoice_booking->bookingDate)) }}
                <br>
                <b>Tài khoản:</b> {{ $invoice_booking->userId }}
            </div>
        </div>
        <div class="clearfix"></div>
        
        <!-- Table row -->
        <div class="row">
            <div class="table">
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Điểm đến</th>
                            <th>Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Người lớn</td>
                            <td>{{ $invoice_booking->numAdults }}</td>
                            <td>{{ number_format($invoice_booking->priceAdult, 0, ',', '.') }} vnđ</td>
                            <td>{{ $invoice_booking->destination }}</td>
                            <td>{{ number_format($invoice_booking->priceAdult * $invoice_booking->numAdults, 0, ',', '.') }} vnđ</td>
                        </tr>
                        <tr>
                            <td>Trẻ em</td>
                            <td>{{ $invoice_booking->numChildren }}</td>
                            <td>{{ number_format($invoice_booking->priceChild, 0, ',', '.') }} vnđ</td>
                            <td>{{ $invoice_booking->destination }}</td>
                            <td>{{ number_format($invoice_booking->priceChild * $invoice_booking->numChildren, 0, ',', '.') }} vnđ</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row">
            <!-- accepted payments column -->
            <div style="float: left; width: 50%; padding-right: 20px;">
                <p class="lead">Phương thức thanh toán:</p>
                @if ($invoice_booking->paymentMethod == 'momo-payment')
                    <img src="{{ public_path('admin/assets/images/icon/icon_momo.png') }}" class="invoice_payment-method" alt="">
                @elseif ($invoice_booking->paymentMethod == 'paypal-payment')
                    <img src="{{ public_path('admin/assets/images/icon/icon_paypal.png') }}" class="invoice_payment-method" alt="">
                @else
                    <img src="{{ public_path('admin/assets/images/icon/icon_office.png') }}" alt="" style="width: 40px;">
                    <span class="badge">Thanh toán tại văn phòng</span>
                @endif
                <p class="text-muted well" style="margin-top: 10px;">
                    Vui lòng hoàn tất thanh toán theo hướng dẫn hoặc liên hệ với chúng tôi nếu cần hỗ trợ.
                </p>
            </div>
            
            <!-- totals column -->
            <div style="float: left; width: 50%;">
                @if(isset($invoice_booking->paymentStatus) && $invoice_booking->paymentStatus == 'y')
                    <p class="lead" style="color: #28a745;">
                        <i class="fa fa-check-circle"></i> Đã thanh toán
                        @if (isset($invoice_booking->paymentDate))
                            - {{ date('d-m-Y', strtotime($invoice_booking->paymentDate)) }}
                        @endif
                    </p>
                @else
                    <p class="lead">Số tiền phải trả trước
                        @if (isset($invoice_booking->startDate))
                            {{ date('d-m-Y', strtotime($invoice_booking->startDate)) }}
                        @else
                            {{ date('d-m-Y', strtotime($invoice_booking->bookingDate)) }}
                        @endif
                    </p>
                @endif
                <div class="table-responsive">
                    <table>
                        <tbody>
                            <tr>
                                <th style="width:50%">Tổng tiền (trước giảm giá):</th>
                                <td>{{ number_format($invoice_booking->undiscountedTotal ?? $invoice_booking->totalPrice ?? 0, 0, ',', '.') }} vnđ</td>
                            </tr>
                            <tr>
                                <th>Tax (0%)</th>
                                <td>0 vnđ</td>
                            </tr>
                            @if (isset($invoice_booking->discountAmount) && $invoice_booking->discountAmount > 0)
                            <tr>
                                <th>Giảm giá 
                                    @if (isset($invoice_booking->groupDiscountPercent) && $invoice_booking->groupDiscountPercent > 0)
                                        (Ưu đãi tour đoàn {{ $invoice_booking->groupDiscountPercent }}%)
                                    @endif
                                </th>
                                <td style="color: #28a745;">- {{ number_format($invoice_booking->discountAmount, 0, ',', '.') }} vnđ</td>
                            </tr>
                            @else
                            <tr>
                                <th>Giảm giá</th>
                                <td>0 vnđ</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Tổng tiền:</th>
                                <td><strong>{{ number_format($invoice_booking->amount ?? $invoice_booking->totalPrice ?? 0, 0, ',', '.') }} vnđ</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </section>
</body>
</html>

