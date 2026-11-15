<x-filament-panels::page>

    <div dir="rtl" class="hat-report">

        @php
            $summary = $this->getSummary();
            $products = $this->getProducts();
        @endphp

        {{-- =========================================================
            REPORT HEADER
        ========================================================== --}}
        <section class="report-header">

            <div class="report-header-main">

                <div class="report-title-row">

                    <div class="report-title-mark">
                        <x-filament::icon
                            icon="heroicon-o-chart-bar-square"
                            class="h-5 w-5"
                        />
                    </div>

                    <div>
                        <h1 class="report-title">
                            تقرير المنتجات
                        </h1>

                        <p class="report-subtitle">
                            تقرير تفصيلي عن المنتجات وحالتها الحالية في نظام HAT
                        </p>
                    </div>

                </div>

                <div class="report-meta">
                    <span>تاريخ التقرير</span>
                    <strong>{{ now()->format('Y/m/d') }}</strong>

                    <span class="meta-separator"></span>

                    <span>الوقت</span>
                    <strong>{{ now()->format('H:i') }}</strong>
                </div>

            </div>


            <div class="report-actions">

                <button
                    type="button"
                    onclick="window.print()"
                    class="report-button report-button-secondary"
                >
                    <x-filament::icon
                        icon="heroicon-o-printer"
                        class="h-4 w-4"
                    />
                    <span>طباعة</span>
                </button>

               <button
    type="button"
    wire:click="exportPdf"
    wire:loading.attr="disabled"
    wire:target="exportPdf"
    class="hat-report-button hat-report-button-primary"
>
    <span wire:loading.remove wire:target="exportPdf">
        PDF
    </span>

    <span wire:loading wire:target="exportPdf">
        جاري إنشاء PDF...
    </span>
</button>

            </div>

        </section>


        {{-- =========================================================
            FILTER BAR
        ========================================================== --}}
        <section class="report-panel">

            <div class="panel-heading">

                <div>
                    <h2>
                        خيارات التقرير
                    </h2>

                    <p>
                        استخدم الفلاتر لتحديد البيانات التي تريد عرضها.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="reset-button"
                >
                    <x-filament::icon
                        icon="heroicon-o-arrow-path"
                        class="h-4 w-4"
                    />

                    إعادة ضبط
                </button>

            </div>


            <div class="filters-grid">

                <div class="filter-field">

                    <label>
                        حالة التوفر
                    </label>

                    <select
                        wire:model.live="availability"
                        class="report-select"
                    >
                        <option value="all">جميع الحالات</option>
                        <option value="available">متوفر</option>
                        <option value="unavailable">غير متوفر</option>
                    </select>

                </div>


                <div class="filter-field">

                    <label>
                        حالة المنتج
                    </label>

                    <select
                        wire:model.live="activeStatus"
                        class="report-select"
                    >
                        <option value="all">جميع الحالات</option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>

                </div>


                <div class="filter-field">

                    <label>
                        التصنيف
                    </label>

                    <select
                        wire:model.live="categoryId"
                        class="report-select"
                    >
                        <option value="">جميع التصنيفات</option>

                        @foreach ($this->getCategories() as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                </div>

            </div>

        </section>


        {{-- =========================================================
            REPORT SUMMARY
        ========================================================== --}}
        <section class="summary-strip">

            <div class="summary-item">

                <span class="summary-label">
                    إجمالي النتائج
                </span>

                <strong class="summary-value">
                    {{ number_format($summary['total']) }}
                </strong>

            </div>


            <div class="summary-item">

                <span class="summary-label">
                    متوفر
                </span>

                <strong class="summary-value summary-success">
                    {{ number_format($summary['available']) }}
                </strong>

            </div>


            <div class="summary-item">

                <span class="summary-label">
                    غير متوفر
                </span>

                <strong class="summary-value summary-danger">
                    {{ number_format($summary['unavailable']) }}
                </strong>

            </div>


            <div class="summary-item">

                <span class="summary-label">
                    نشط
                </span>

                <strong class="summary-value summary-primary">
                    {{ number_format($summary['active']) }}
                </strong>

            </div>


            <div class="summary-item summary-last">

                <span class="summary-label">
                    غير مصنف
                </span>

                <strong class="summary-value summary-warning">
                    {{ number_format($summary['uncategorized']) }}
                </strong>

            </div>

        </section>


        {{-- =========================================================
            TABLE
        ========================================================== --}}
        <section class="report-panel table-panel">

            <div class="table-heading">

                <div>
                    <h2>
                        تفاصيل المنتجات
                    </h2>

                    <p>
                        {{ number_format($summary['total']) }} منتج مطابق للمعايير المحددة
                    </p>
                </div>

                <div class="result-counter">
                    {{ number_format($summary['total']) }}
                    نتيجة
                </div>

            </div>


            <div class="table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>

                            <th class="col-number">
                                #
                            </th>

                            <th class="col-item">
                                رقم الصنف
                            </th>

                            <th class="col-product">
                                اسم المنتج
                            </th>

                            <th class="col-category">
                                التصنيف
                            </th>

                            <th class="col-sku">
                                SKU
                            </th>

                            <th class="col-status">
                                التوفر
                            </th>

                            <th class="col-active">
                                الحالة
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($products as $index => $product)

                            <tr>

                                <td class="number-cell">
                                    {{ $index + 1 }}
                                </td>


                                <td class="item-cell">
                                    {{ $product->item_no }}
                                </td>


                                <td class="product-cell">
                                    <span>
                                        {{ $product->name }}
                                    </span>
                                </td>


                                <td>

                                    @if ($product->category)

                                        <span class="category-label">
                                            {{ $product->category->name }}
                                        </span>

                                    @else

                                        <span class="empty-value">
                                            غير مصنف
                                        </span>

                                    @endif

                                </td>


                                <td class="sku-cell">
                                    {{ $product->sku ?: '—' }}
                                </td>


                                <td>

                                    @if ($product->availability === 'available')

                                        <span class="status status-available">
                                            <span class="status-dot"></span>
                                            متوفر
                                        </span>

                                    @else

                                        <span class="status status-unavailable">
                                            <span class="status-dot"></span>
                                            غير متوفر
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if ($product->is_active)

                                        <span class="status status-active">
                                            نشط
                                        </span>

                                    @else

                                        <span class="status status-inactive">
                                            غير نشط
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-table"
                                >

                                    <div class="empty-icon">

                                        <x-filament::icon
                                            icon="heroicon-o-document-magnifying-glass"
                                            class="h-6 w-6"
                                        />

                                    </div>

                                    <strong>
                                        لا توجد نتائج
                                    </strong>

                                    <span>
                                        لم يتم العثور على منتجات مطابقة للفلاتر المحددة.
                                    </span>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Table Footer --}}
            <div class="table-footer">

                <span>
                    إجمالي السجلات:
                    <strong>{{ number_format($summary['total']) }}</strong>
                </span>

                <span>
                    تقرير HAT الداخلي
                </span>

            </div>

        </section>

    </div>


    {{-- =============================================================
        REPORT DESIGN SYSTEM
    ============================================================== --}}
    <style>

        .hat-report {
            --hat-primary: #238B95;
            --hat-primary-dark: #176C74;

            --report-bg: #F6F8F9;
            --report-surface: #FFFFFF;

            --report-border: #E4E9EB;

            --report-text: #17282C;
            --report-muted: #68777B;
            --report-soft: #8A989C;
        }


        /* =========================================================
           HEADER
        ========================================================== */

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;

            padding: 24px 28px;

            background: var(--report-surface);

            border: 1px solid var(--report-border);
            border-radius: 16px;

            box-shadow:
                0 1px 2px rgba(23, 40, 44, .03);
        }


        .report-header-main {
            display: flex;
            align-items: center;
            justify-content: space-between;

            flex: 1;
            min-width: 0;
        }


        .report-title-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .report-title-mark {
            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: #EAF6F7;
            color: var(--hat-primary);
        }


        .report-title {
            margin: 0;

            color: var(--report-text);

            font-size: 21px;
            font-weight: 700;

            line-height: 1.4;
            letter-spacing: -.2px;
        }


        .report-subtitle {
            margin: 3px 0 0;

            color: var(--report-muted);

            font-size: 13px;
            line-height: 1.7;
        }


        .report-meta {
            display: flex;
            align-items: center;
            gap: 7px;

            color: var(--report-soft);

            font-size: 12px;

            white-space: nowrap;
        }


        .report-meta strong {
            color: #526367;
            font-weight: 600;
        }


        .meta-separator {
            width: 3px;
            height: 3px;

            margin: 0 6px;

            border-radius: 50%;
            background: #B8C3C6;
        }


        /* =========================================================
           BUTTONS
        ========================================================== */

        .report-actions {
            display: flex;
            align-items: center;
            gap: 9px;

            flex-shrink: 0;
        }


        .report-button {
            height: 40px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 0 16px;

            border-radius: 9px;

            font-family: inherit;
            font-size: 13px;
            font-weight: 600;

            text-decoration: none;

            transition:
                background .18s ease,
                border-color .18s ease,
                box-shadow .18s ease,
                transform .18s ease;
        }


        .report-button-secondary {
            color: #45565A;

            background: #FFFFFF;

            border: 1px solid #DDE4E6;
        }


        .report-button-secondary:hover {
            background: #F7F9FA;
            border-color: #CDD7D9;
        }


        .report-button-primary {
            color: #FFFFFF;

            background: var(--hat-primary);

            border: 1px solid var(--hat-primary);

            box-shadow: 0 2px 5px rgba(35, 139, 149, .16);
        }


        .report-button-primary:hover {
            background: var(--hat-primary-dark);
            border-color: var(--hat-primary-dark);
        }


        /* =========================================================
           PANELS
        ========================================================== */

        .report-panel {
            overflow: hidden;

            background: var(--report-surface);

            border: 1px solid var(--report-border);
            border-radius: 16px;

            box-shadow:
                0 1px 2px rgba(23, 40, 44, .025);
        }


        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 20px 24px;

            border-bottom: 1px solid #EDF1F2;
        }


        .panel-heading h2,
        .table-heading h2 {
            margin: 0;

            color: var(--report-text);

            font-size: 15px;
            font-weight: 700;
        }


        .panel-heading p,
        .table-heading p {
            margin: 4px 0 0;

            color: var(--report-muted);

            font-size: 12px;
            line-height: 1.6;
        }


        .reset-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 7px 10px;

            color: #68777B;

            background: transparent;
            border: 0;

            border-radius: 7px;

            font-family: inherit;
            font-size: 12px;
            font-weight: 600;

            cursor: pointer;

            transition:
                color .15s ease,
                background .15s ease;
        }


        .reset-button:hover {
            color: var(--hat-primary);
            background: #F2F8F9;
        }


        /* =========================================================
           FILTERS
        ========================================================== */

        .filters-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;

            padding: 22px 24px;
        }


        .filter-field {
            min-width: 0;
        }


        .filter-field label {
            display: block;

            margin-bottom: 7px;

            color: #536367;

            font-size: 12px;
            font-weight: 600;
        }


        .report-select {
            width: 100%;
            height: 42px;

            padding: 0 12px;

            color: #26383C;
            background-color: #FFFFFF;

            border: 1px solid #DCE4E6;
            border-radius: 9px;

            outline: none;

            font-family: inherit;
            font-size: 13px;

            transition:
                border-color .15s ease,
                box-shadow .15s ease;
        }


        .report-select:hover {
            border-color: #C7D3D6;
        }


        .report-select:focus {
            border-color: var(--hat-primary);

            box-shadow:
                0 0 0 3px rgba(35, 139, 149, .10);
        }


        /* =========================================================
           SUMMARY
        ========================================================== */

        .summary-strip {
            display: grid;

            grid-template-columns:
                repeat(5, minmax(0, 1fr));

            background: var(--report-surface);

            border: 1px solid var(--report-border);
            border-radius: 16px;

            overflow: hidden;
        }


        .summary-item {
            min-width: 0;

            padding: 18px 22px;

            border-left: 1px solid #EDF1F2;
        }


        .summary-item:first-child {
            border-right: 3px solid var(--hat-primary);
        }


        .summary-last {
            border-left: 0;
        }


        .summary-label {
            display: block;

            color: #7A898D;

            font-size: 12px;
            font-weight: 500;
        }


        .summary-value {
            display: block;

            margin-top: 7px;

            color: var(--report-text);

            font-size: 22px;
            font-weight: 700;

            line-height: 1;
        }


        .summary-success {
            color: #26805F;
        }


        .summary-danger {
            color: #B45151;
        }


        .summary-primary {
            color: var(--hat-primary-dark);
        }


        .summary-warning {
            color: #A57526;
        }


        /* =========================================================
           TABLE
        ========================================================== */

        .table-panel {
            overflow: hidden;
        }


        .table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 20px 24px;

            border-bottom: 1px solid #EDF1F2;
        }


        .result-counter {
            padding: 6px 10px;

            color: #667579;

            background: #F4F7F8;

            border: 1px solid #E5EAEC;
            border-radius: 7px;

            font-size: 11px;
            font-weight: 600;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        .report-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;

            font-size: 13px;
        }


        .report-table thead {
            background: #F7F9FA;
        }


        .report-table th {
            height: 46px;

            padding: 0 18px;

            color: #68777B;

            border-bottom: 1px solid #E5EAEC;

            font-size: 11px;
            font-weight: 700;

            white-space: nowrap;
        }


        .report-table td {
            height: 58px;

            padding: 0 18px;

            color: #46575B;

            border-bottom: 1px solid #EEF2F3;

            vertical-align: middle;
        }


        .report-table tbody tr {
            transition: background .15s ease;
        }


        .report-table tbody tr:hover {
            background: #FAFCFC;
        }


        .report-table tbody tr:last-child td {
            border-bottom: 0;
        }


        .col-number {
            width: 55px;
            text-align: center !important;
        }


        .col-item {
            width: 145px;
        }


        .col-product {
            width: 30%;
        }


        .col-category {
            width: 170px;
        }


        .col-sku {
            width: 140px;
        }


        .col-status {
            width: 130px;
            text-align: center !important;
        }


        .col-active {
            width: 110px;
            text-align: center !important;
        }


        .number-cell {
            color: #9AA6A9 !important;

            text-align: center;
        }


        .item-cell {
            color: var(--report-text) !important;

            font-weight: 650;
        }


        .product-cell {
            color: #34474B !important;

            font-weight: 500;
        }


        .product-cell span {
            display: block;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .sku-cell {
            color: #778589 !important;

            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;

            font-size: 12px;
        }


        .category-label {
            display: inline-block;

            max-width: 145px;

            overflow: hidden;

            padding: 5px 9px;

            color: #36727A;

            background: #EEF7F8;

            border-radius: 6px;

            font-size: 11px;
            font-weight: 600;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .empty-value {
            color: #A2AFB2;

            font-size: 12px;
        }


        /* =========================================================
           STATUS
        ========================================================== */

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            min-width: 80px;

            padding: 5px 9px;

            border-radius: 6px;

            font-size: 11px;
            font-weight: 600;
        }


        .status-dot {
            width: 5px;
            height: 5px;

            border-radius: 50%;

            background: currentColor;
        }


        .status-available {
            color: #36785F;
            background: #EEF8F3;
        }


        .status-unavailable {
            color: #A95151;
            background: #FBF0F0;
        }


        .status-active {
            color: #36727A;
            background: #EEF7F8;
        }


        .status-inactive {
            color: #747F82;
            background: #F1F3F4;
        }


        /* =========================================================
           EMPTY
        ========================================================== */

        .empty-table {
            height: 260px !important;

            text-align: center;
        }


        .empty-icon {
            width: 46px;
            height: 46px;

            margin: 0 auto 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #8A989C;

            background: #F2F5F6;

            border-radius: 10px;
        }


        .empty-table strong {
            display: block;

            color: #526367;

            font-size: 13px;
        }


        .empty-table span {
            display: block;

            margin-top: 5px;

            color: #929EA1;

            font-size: 12px;
        }


        /* =========================================================
           FOOTER
        ========================================================== */

        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 13px 20px;

            color: #8A989C;

            background: #FAFBFB;

            border-top: 1px solid #EDF1F2;

            font-size: 11px;
        }


        .table-footer strong {
            color: #5D6D71;
            font-weight: 700;
        }


        /* =========================================================
           DARK MODE
        ========================================================== */

        .dark .hat-report {
            --report-surface: #111A1C;
            --report-border: #263639;
            --report-text: #E8EFF0;
            --report-muted: #94A4A8;
            --report-soft: #718185;
        }


        .dark .report-title-mark {
            background: #16383C;
            color: #5BB7BF;
        }


        .dark .report-button-secondary {
            color: #D2DCDD;
            background: #172224;
            border-color: #304145;
        }


        .dark .report-button-secondary:hover {
            background: #1D2B2E;
        }


        .dark .panel-heading,
        .dark .table-heading {
            border-color: #263639;
        }


        .dark .report-select {
            color: #E1E9EA;
            background-color: #172224;
            border-color: #304145;
        }


        .dark .report-select:focus {
            border-color: #4DAAB3;
        }


        .dark .summary-item {
            border-color: #263639;
        }


        .dark .summary-label {
            color: #87979B;
        }


        .dark .summary-primary {
            color: #5BB7BF;
        }


        .dark .report-table thead {
            background: #172224;
        }


        .dark .report-table th {
            color: #8FA0A4;
            border-color: #2A3A3D;
        }


        .dark .report-table td {
            color: #B8C5C8;
            border-color: #253538;
        }


        .dark .report-table tbody tr:hover {
            background: #172224;
        }


        .dark .product-cell {
            color: #D3DDDF !important;
        }


        .dark .item-cell {
            color: #E4EBEC !important;
        }


        .dark .category-label {
            color: #76C4CB;
            background: #16383C;
        }


        .dark .result-counter,
        .dark .table-footer {
            background: #172224;
        }


        .dark .result-counter {
            color: #9CAEB1;
            border-color: #2B3B3E;
        }


        .dark .table-footer {
            border-color: #263639;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 1100px) {

            .report-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .report-header-main {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .report-actions {
                width: 100%;
            }

            .report-button {
                flex: 1;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .summary-strip {
                grid-template-columns: repeat(2, 1fr);
            }

            .summary-item {
                border-bottom: 1px solid #EDF1F2;
            }

        }


        @media (max-width: 640px) {

            .report-header {
                padding: 18px;
            }

            .report-title {
                font-size: 18px;
            }

            .report-meta {
                flex-wrap: wrap;
            }

            .summary-strip {
                grid-template-columns: 1fr;
            }

            .summary-item {
                border-left: 0;
                border-bottom: 1px solid #EDF1F2;
            }

            .summary-item:last-child {
                border-bottom: 0;
            }

            .panel-heading,
            .table-heading {
                align-items: flex-start;
                flex-direction: column;
            }

        }


        /* =========================================================
           PRINT
        ========================================================== */

        @media print {

            .fi-sidebar,
            .fi-topbar,
            .fi-breadcrumbs,
            .report-actions,
            .reset-button,
            .report-header-mark,
            button,
            a {
                display: none !important;
            }

            body {
                background: #FFFFFF !important;
            }

            .hat-report {
                padding: 0 !important;
            }

            .report-panel,
            .report-header,
            .summary-strip {
                box-shadow: none !important;
                break-inside: avoid;
            }

            .report-table {
                font-size: 10px;
            }

        }

    </style>

</x-filament-panels::page>