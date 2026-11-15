<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4 landscape;
            margin: 25px 25px 35px 25px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            direction: rtl;
            font-family: DejaVu Sans, sans-serif;
            color: #17282C;
            font-size: 9px;
            line-height: 1.5;
        }

        /* =========================
           HEADER
        ========================== */

        .header {
            width: 100%;
            border-bottom: 2px solid #238B95;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 22%;
            text-align: right;
        }

        .company-cell {
            width: 78%;
            text-align: right;
        }

        .logo {
            width: 90px;
            max-height: 48px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #176C74;
        }

        .company-subtitle {
            margin-top: 2px;
            color: #68797D;
            font-size: 8px;
        }

        /* =========================
           TITLE
        ========================== */

        .report-title {
            text-align: center;
            margin-bottom: 14px;
        }

        .report-title h1 {
            margin: 0;
            font-size: 17px;
            color: #17282C;
        }

        .report-title p {
            margin: 3px 0 0;
            font-size: 8px;
            color: #68797D;
        }

        /* =========================
           META / FILTERS
        ========================== */

        .meta {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta td {
            width: 20%;
            border: 1px solid #DCE5E7;
            padding: 6px 8px;
            background: #F6F9FA;
            vertical-align: middle;
        }

        .meta-label {
            color: #7C8C90;
            font-size: 7px;
            margin-bottom: 2px;
        }

        .meta-value {
            color: #17282C;
            font-size: 8px;
            font-weight: bold;
        }

        /* =========================
           SUMMARY
        ========================== */

        .summary {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .summary td {
            width: 20%;
            border: 1px solid #DCE5E7;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }

        .summary-number {
            display: block;
            color: #176C74;
            font-size: 13px;
            font-weight: bold;
        }

        .summary-label {
            display: block;
            margin-top: 1px;
            color: #68797D;
            font-size: 7px;
        }

        /* =========================
           SECTION
        ========================== */

        .section-title {
            margin-bottom: 6px;
            color: #176C74;
            font-size: 10px;
            font-weight: bold;
        }

        /* =========================
           PRODUCTS TABLE
        ========================== */

        table.products {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border-spacing: 0;
            direction: rtl;
        }

        table.products thead {
            display: table-header-group;
        }

        table.products tr {
            page-break-inside: avoid;
        }

        table.products th {
            padding: 6px 4px;
            border: 1px solid #176C74;
            background: #176C74;
            color: #FFFFFF;
            font-size: 7.5px;
            font-weight: bold;
            text-align: right;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.products td {
            padding: 6px 4px;
            border: 1px solid #DCE5E7;
            color: #17282C;
            background: #FFFFFF;
            font-size: 7.5px;
            text-align: right;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.products tbody tr:nth-child(even) td {
            background: #F7FAFA;
        }

        /* Fixed column widths.
           Total = 100%
        */

        .col-number {
            width: 5%;
        }

        .col-item {
            width: 15%;
        }

        .col-name {
            width: 27%;
        }

        .col-category {
            width: 18%;
        }

        .col-sku {
            width: 12%;
        }

        .col-availability {
            width: 13%;
        }

        .col-status {
            width: 10%;
        }

        .center {
            text-align: center !important;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: embed;
            text-align: left !important;
        }

        .status-available {
            color: #177245;
            font-weight: bold;
        }

        .status-unavailable {
            color: #B42318;
            font-weight: bold;
        }

        .status-active {
            color: #177245;
            font-weight: bold;
        }

        .status-inactive {
            color: #B42318;
            font-weight: bold;
        }

        /* =========================
           EMPTY STATE
        ========================== */

        .empty {
            padding: 20px;
            text-align: center !important;
            color: #68797D;
        }

        /* =========================
           TOTAL
        ========================== */

        .total {
            margin-top: 10px;
            text-align: left;
            color: #68797D;
            font-size: 8px;
        }

        .total strong {
            color: #17282C;
        }

        /* =========================
           FOOTER
        ========================== */

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            border-top: 1px solid #DCE5E7;
            padding-top: 5px;
            color: #7C8C90;
            font-size: 7px;
            text-align: center;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>

    @php
        $logoPath = public_path('images/hat-logo.png');
    @endphp

    {{-- =========================
         HAT HEADER
    ========================== --}}

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if (file_exists($logoPath))
                        <img
                            src="{{ $logoPath }}"
                            class="logo"
                            alt="HAT"
                        >
                    @endif
                </td>

                <td class="company-cell">
                    <div class="company-name">
                        Hamed Alyosofi for Trading
                    </div>

                    <div class="company-subtitle">
                        HAT Trading — نظام إدارة المنتجات
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- =========================
         REPORT TITLE
    ========================== --}}

    <div class="report-title">
        <h1>تقرير المنتجات</h1>

        <p>
            تقرير تفصيلي بالمنتجات المطابقة للمعايير المحددة
        </p>
    </div>

    {{-- =========================
         FILTER INFORMATION
    ========================== --}}

    <table class="meta">
        <tr>
            <td>
                <div class="meta-label">
                    تاريخ التقرير
                </div>

                <div class="meta-value">
                    {{ now()->format('Y/m/d') }}
                </div>
            </td>

            <td>
                <div class="meta-label">
                    وقت الإنشاء
                </div>

                <div class="meta-value">
                    {{ now()->format('H:i') }}
                </div>
            </td>

            <td>
                <div class="meta-label">
                    التوفر
                </div>

                <div class="meta-value">
                    {{ $filters['availability'] }}
                </div>
            </td>

            <td>
                <div class="meta-label">
                    الحالة
                </div>

                <div class="meta-value">
                    {{ $filters['activeStatus'] }}
                </div>
            </td>

            <td>
                <div class="meta-label">
                    التصنيف
                </div>

                <div class="meta-value">
                    {{ $filters['category'] }}
                </div>
            </td>
        </tr>
    </table>

    {{-- =========================
         SUMMARY
    ========================== --}}

    <table class="summary">
        <tr>
            <td>
                <span class="summary-number">
                    {{ number_format($summary['total']) }}
                </span>

                <span class="summary-label">
                    إجمالي النتائج
                </span>
            </td>

            <td>
                <span class="summary-number">
                    {{ number_format($summary['available']) }}
                </span>

                <span class="summary-label">
                    متوفر
                </span>
            </td>

            <td>
                <span class="summary-number">
                    {{ number_format($summary['unavailable']) }}
                </span>

                <span class="summary-label">
                    غير متوفر
                </span>
            </td>

            <td>
                <span class="summary-number">
                    {{ number_format($summary['active']) }}
                </span>

                <span class="summary-label">
                    نشط
                </span>
            </td>

            <td>
                <span class="summary-number">
                    {{ number_format($summary['uncategorized']) }}
                </span>

                <span class="summary-label">
                    غير مصنف
                </span>
            </td>
        </tr>
    </table>

    {{-- =========================
         PRODUCTS
    ========================== --}}

    <div class="section-title">
        تفاصيل المنتجات
    </div>

    <table class="products">

        <thead>
            <tr>
                <th class="col-number center">
                    #
                </th>

                <th class="col-item">
                    رقم الصنف
                </th>

                <th class="col-name">
                    اسم المنتج
                </th>

                <th class="col-category">
                    التصنيف
                </th>

                <th class="col-sku">
                    SKU
                </th>

                <th class="col-availability">
                    التوفر
                </th>

                <th class="col-status center">
                    الحالة
                </th>
            </tr>
        </thead>

        <tbody>

            @forelse ($products as $index => $product)

                <tr>

                    <td class="col-number center">
                        {{ $index + 1 }}
                    </td>

                    <td class="col-item ltr">
                        {{ $product->item_no ?: '—' }}
                    </td>

                    <td class="col-name">
                        {{ $product->name ?: '—' }}
                    </td>

                    <td class="col-category">
                        {{ $product->category?->name ?: 'غير مصنف' }}
                    </td>

                    <td class="col-sku ltr">
                        {{ $product->sku ?: '—' }}
                    </td>

                    <td class="col-availability">
                        @if ($product->availability === 'available')

                            <span class="status-available">
                                متوفر
                            </span>

                        @elseif ($product->availability === 'unavailable')

                            <span class="status-unavailable">
                                غير متوفر
                            </span>

                        @else

                            —

                        @endif
                    </td>

                    <td class="col-status center">

                        @if ($product->is_active)

                            <span class="status-active">
                                نشط
                            </span>

                        @else

                            <span class="status-inactive">
                                غير نشط
                            </span>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" class="empty">
                        لا توجد منتجات مطابقة للمعايير المحددة.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

    {{-- =========================
         TOTAL
    ========================== --}}

    <div class="total">
        إجمالي السجلات:
        <strong>{{ number_format($summary['total']) }}</strong>
    </div>

    {{-- =========================
         FOOTER
    ========================== --}}

    <div class="footer">
        HAT Trading — تقرير المنتجات
        &nbsp; | &nbsp;
        صفحة <span class="page-number"></span>
    </div>

</body>
</html>