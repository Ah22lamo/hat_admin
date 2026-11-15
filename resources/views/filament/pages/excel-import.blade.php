<x-filament-panels::page>
<div class="relative space-y-6" dir="rtl">

    {{-- =========================================================
         Loading Overlay — Analyze Excel
    ========================================================== --}}
    <div
        wire:loading.flex
        wire:target="analyze"
        class="fixed inset-0 z-[9999] hidden items-center justify-center bg-gray-900/50 p-6 backdrop-blur-sm"
    >
        <div class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-2xl">

            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#EAF6F7] text-[#238B95]"
            >
                <svg
                    class="h-8 w-8 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />

                    <path
                        class="opacity-90"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>
            </div>

            <h2 class="mt-5 text-xl font-bold text-gray-900">
                جارٍ تحليل ملف Excel
            </h2>

            <p class="mt-2 text-sm leading-6 text-gray-500">
                يتم الآن قراءة الملف واكتشاف الأعمدة والتحقق من البيانات.
            </p>

            <div class="mt-6 rounded-xl bg-[#F5F8F9] px-4 py-3">
                <p class="text-xs font-medium text-gray-600">
                    يرجى الانتظار وعدم تحديث الصفحة.
                </p>
            </div>

        </div>
    </div>


    {{-- =========================================================
         Loading Overlay — Import
    ========================================================== --}}
    <div
        wire:loading.flex
        wire:target="confirmImport"
        class="fixed inset-0 z-[9999] hidden items-center justify-center bg-gray-900/60 p-6 backdrop-blur-sm"
    >
        <div class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-2xl">

            <div
                class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-[#EAF6F7] text-[#238B95]"
            >
                <svg
                    class="h-10 w-10 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />

                    <path
                        class="opacity-90"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>
            </div>

            <h2 class="mt-6 text-2xl font-bold text-gray-900">
                جارٍ استيراد المنتجات
            </h2>

            <p class="mt-3 text-sm leading-7 text-gray-500">
                يتم الآن حفظ المنتجات والتحقق من البيانات وتسجيل عملية الاستيراد.
            </p>

            <div class="mt-6 overflow-hidden rounded-full bg-gray-100">
                <div class="h-2 w-full animate-pulse rounded-full bg-[#238B95]"></div>
            </div>

            <div class="mt-5 rounded-2xl border border-[#BFE3E6] bg-[#EAF6F7] p-4">

                <div class="flex items-center justify-center gap-2 text-sm font-semibold text-[#176C74]">

                    <x-heroicon-o-shield-check class="h-5 w-5" />

                    <span>
                        لا تغلق الصفحة ولا تقم بتحديثها.
                    </span>

                </div>

                <p class="mt-2 text-xs leading-5 text-gray-600">
                    ستظهر نتيجة العملية تلقائيًا عند اكتمال الاستيراد.
                </p>

            </div>

        </div>
    </div>


    {{-- =========================================================
         Header
    ========================================================== --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div class="min-w-0">

                <h1 class="text-2xl font-bold text-gray-900">
                    استيراد المنتجات من Excel
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">
                    ارفع ملف المنتجات، راجع البيانات، أكمل المعلومات المطلوبة،
                    ثم نفّذ الاستيراد إلى قاعدة البيانات.
                </p>

            </div>

            @if ($preview)

                <button
                    type="button"
                    wire:click="resetImport"
                    wire:loading.attr="disabled"
                    wire:target="resetImport,confirmImport"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition duration-200 hover:border-gray-400 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    استيراد ملف آخر
                </button>

            @endif

        </div>

    </div>


    {{-- =========================================================
         Upload Section
    ========================================================== --}}
    @if (!$preview)

        <div
            x-data="{
                selectedFileName: '',
                selectedFileSize: '',
                uploading: false,
                uploadFinished: false,
                progress: 0,
                isDragging: false,

                chooseFile() {
                    if (!this.uploading) {
                        this.$refs.fileInput.click();
                    }
                },

                resetFileState() {
                    this.selectedFileName = '';
                    this.selectedFileSize = '';
                    this.uploading = false;
                    this.uploadFinished = false;
                    this.progress = 0;
                },

                showFile(file) {
                    if (!file) {
                        this.resetFileState();
                        return;
                    }

                    this.selectedFileName = file.name;

                    if (file.size >= 1024 * 1024) {
                        this.selectedFileSize =
                            (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                    } else {
                        this.selectedFileSize =
                            (file.size / 1024).toFixed(1) + ' KB';
                    }

                    this.uploadFinished = false;
                    this.progress = 0;
                },

                fileSelected(event) {
                    const file = event.target.files?.[0];

                    if (!file) {
                        this.resetFileState();
                        return;
                    }

                    this.showFile(file);
                },

                dropFile(event) {
                    this.isDragging = false;

                    const files = event.dataTransfer?.files;

                    if (!files || !files.length) {
                        return;
                    }

                    const file = files[0];

                    this.$refs.fileInput.files = files;

                    this.showFile(file);

                    this.$refs.fileInput.dispatchEvent(
                        new Event('change', { bubbles: true })
                    );
                }
            }"

            x-on:livewire-upload-start="
                uploading = true;
                uploadFinished = false;
                progress = 0;
            "

            x-on:livewire-upload-progress="
                uploading = true;
                progress = $event.detail.progress;
            "

            x-on:livewire-upload-finish="
                uploading = false;
                uploadFinished = true;
                progress = 100;
            "

            x-on:livewire-upload-error="
                uploading = false;
                uploadFinished = false;
                progress = 0;
            "

            x-on:livewire-upload-cancel="
                uploading = false;
                uploadFinished = false;
                progress = 0;
            "

            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
        >

            {{-- Upload Header --}}
            <div class="mb-5">

                <h2 class="text-lg font-bold text-gray-900">
                    1. اختيار ملف Excel
                </h2>

                <p class="mt-1 text-sm leading-6 text-gray-500">
                    اختر ملف المنتجات من جهازك لبدء عملية التحليل.
                </p>

            </div>


            {{-- Upload Zone --}}
            <div
                x-on:dragenter.prevent="isDragging = true"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="dropFile($event)"
                x-on:click="chooseFile()"

                :class="isDragging
                    ? 'border-[#238B95] bg-[#EAF6F7] ring-2 ring-[#238B95]/10'
                    : 'border-gray-300 bg-[#FAFCFC] hover:border-[#238B95] hover:bg-[#F5FBFB]'"

                class="group cursor-pointer rounded-2xl border-2 border-dashed px-6 py-10 text-center transition-all duration-200 sm:px-10"
            >

                <input
                    x-ref="fileInput"
                    type="file"
                    wire:model="file"
                    x-on:change="fileSelected($event)"
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                >

                {{-- Empty State --}}
                <template x-if="!selectedFileName">

                    <div class="flex flex-col items-center">

                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#EAF6F7] text-[#238B95] transition-transform duration-200 group-hover:scale-105"
                        >
                            <x-heroicon-o-document-arrow-up class="h-8 w-8" />
                        </div>

                        <h3 class="mt-5 text-base font-bold text-gray-900">
                            اختر ملف Excel
                        </h3>

                        <p class="mt-2 text-sm text-gray-500">
                            اضغط هنا لاختيار الملف من جهازك
                        </p>

                        <p class="mt-1 hidden text-xs text-gray-400 md:block">
                            ويمكنك أيضًا سحب الملف وإفلاته هنا
                        </p>

                        <button
                            type="button"
                            x-on:click.stop="chooseFile()"
                            class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-[#238B95] px-6 py-3 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#176C74] hover:shadow-md"
                        >
                            <x-heroicon-o-folder-open class="h-5 w-5" />
                            اختيار الملف
                        </button>

                        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">

                            <span class="rounded-md bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                                XLSX
                            </span>

                            <span class="rounded-md bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                                XLS
                            </span>

                            <span class="rounded-md bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                                CSV
                            </span>

                            <span class="text-xs text-gray-300">
                                •
                            </span>

                            <span class="text-xs text-gray-400">
                                حتى 100 MB
                            </span>

                        </div>

                    </div>

                </template>


                {{-- Selected File State --}}
                <template x-if="selectedFileName">

                    <div class="mx-auto flex max-w-2xl flex-col items-center">

                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#EAF6F7] text-[#238B95]"
                        >
                            <x-heroicon-o-document-text class="h-8 w-8" />
                        </div>

                        <p
                            class="mt-5 max-w-full truncate px-4 text-base font-bold text-gray-900"
                            x-text="selectedFileName"
                        ></p>

                        <p
                            class="mt-1 text-sm text-gray-500"
                            x-text="selectedFileSize"
                        ></p>

                        {{-- Upload Progress --}}
                        <div
                            x-show="uploading"
                            x-cloak
                            class="mt-6 w-full max-w-md"
                        >

                            <div class="mb-2 flex items-center justify-between text-xs">

                                <span class="font-semibold text-[#176C74]">
                                    جارٍ رفع الملف
                                </span>

                                <span
                                    class="font-bold text-[#238B95]"
                                    x-text="progress + '%'"
                                ></span>

                            </div>

                            <div class="h-2 overflow-hidden rounded-full bg-gray-200">

                                <div
                                    class="h-full rounded-full bg-[#238B95] transition-all duration-150"
                                    :style="`width: ${progress}%`"
                                ></div>

                            </div>

                        </div>


                        {{-- Ready State --}}
                        <div
                            x-show="uploadFinished && !uploading"
                            x-cloak
                            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700"
                        >

                            <x-heroicon-o-check-circle class="h-4 w-4" />

                            الملف جاهز للتحليل

                        </div>


                        {{-- Server Upload State --}}
                        <div
                            wire:loading
                            wire:target="file"
                            class="mt-4 text-xs font-medium text-gray-500"
                        >
                            يتم تجهيز الملف...
                        </div>


                        {{-- Change File --}}
                        <button
                            type="button"
                            x-on:click.stop="chooseFile()"
                            x-bind:disabled="uploading"
                            class="mt-5 text-xs font-semibold text-[#238B95] transition-colors duration-200 hover:text-[#176C74] disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            تغيير الملف
                        </button>

                    </div>

                </template>

            </div>


            {{-- Validation Error --}}
            @error('file')

                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">

                    <p class="text-sm font-semibold leading-6 text-red-700">
                        {{ $message }}
                    </p>

                </div>

            @enderror


            {{-- Analyze Action --}}
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

                <div class="text-right">

                    <template x-if="!selectedFileName">

                        <p class="text-xs text-gray-400">
                            اختر ملفًا أولًا للمتابعة.
                        </p>

                    </template>

                    <template x-if="selectedFileName && uploading">

                        <p class="text-xs font-medium text-amber-600">
                            انتظر حتى يكتمل رفع الملف.
                        </p>

                    </template>

                    <template x-if="selectedFileName && uploadFinished && !uploading">

                        <p class="text-xs font-medium text-emerald-600">
                            الملف جاهز. يمكنك بدء التحليل.
                        </p>

                    </template>

                </div>


                <button
                    type="button"
                    x-bind:disabled="!uploadFinished || uploading"
                    wire:click="analyze"
                    wire:loading.attr="disabled"
                    wire:target="analyze,file"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#238B95] px-7 py-3 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#176C74] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40"
                >

                    <span
                        wire:loading.remove
                        wire:target="analyze"
                    >
                        تحليل الملف
                    </span>

                    <span
                        wire:loading
                        wire:target="analyze"
                        class="inline-flex items-center gap-2"
                    >

                        <svg
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-90"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>

                        جارٍ التحليل...

                    </span>

                </button>

            </div>

        </div>

    @endif


    {{-- =========================================================
         Preview Section
    ========================================================== --}}
    @if ($preview)

        {{-- Summary --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">
                    عدد الصفوف
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ $this->summary['total_rows'] }}
                </p>
            </div>


            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">
                    صالحة
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    {{ $this->summary['valid_rows'] }}
                </p>
            </div>


            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">
                    تحذيرات
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-600">
                    {{ $this->summary['warning_rows'] }}
                </p>
            </div>


            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">
                    أخطاء
                </p>

                <p class="mt-2 text-2xl font-bold text-red-600">
                    {{ $this->summary['error_rows'] }}
                </p>
            </div>


            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">
                    تكرارات
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-700">
                    {{ $this->summary['duplicate_rows'] }}
                </p>
            </div>

        </div>


        {{-- Employee Instruction --}}
        <div class="rounded-2xl border border-[#BFE3E6] bg-[#EAF6F7] p-5">

            <div class="flex gap-3">

                <x-heroicon-o-information-circle
                    class="mt-0.5 h-6 w-6 shrink-0 text-[#238B95]"
                />

                <div>

                    <h3 class="font-bold text-[#176C74]">
                        مراجعة البيانات قبل الاستيراد
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-gray-700">
                        يتم تحليل بيانات Excel أولًا. عند التأكيد، سيتم
                        <strong>إنشاء المنتجات الجديدة</strong>
                        وتحديث
                        <strong>المنتجات الموجودة مسبقًا</strong>
                        وفقًا لرقم الصنف ITEM، بعد اكتمال اسم المنتج والتصنيف.
                    </p>

                </div>

            </div>

        </div>


        {{-- Sheets --}}
        @foreach ($preview['sheets'] ?? [] as $sheetIndex => $sheet)

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                {{-- Sheet Header --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">

                        <div>

                            <h2 class="font-bold text-gray-900">
                                {{ $sheet['name'] ?? 'Sheet' }}
                            </h2>

                            <p class="mt-1 text-xs text-gray-500">
                                الصف الرئيسي للأعمدة:
                                {{ $sheet['header_row'] ?? 'غير محدد' }}
                            </p>

                        </div>

                        <span
                            class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-600"
                        >
                            {{ count($sheet['rows'] ?? []) }} صف
                        </span>

                    </div>

                </div>


                {{-- Table --}}
                <div class="overflow-x-auto">

                    <table class="min-w-[1580px] w-full text-right text-sm">

                        <thead class="bg-[#F5F8F9]">

                            <tr class="border-b border-gray-200">

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    #
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    ITEM
                                </th>

                                <th class="min-w-[130px] px-4 py-4 font-bold text-gray-700">
                                    صورة المنتج
                                </th>

                                <th class="min-w-[220px] px-4 py-4 font-bold text-gray-700">
                                    اسم المنتج *
                                </th>

                                <th class="min-w-[220px] px-4 py-4 font-bold text-gray-700">
                                    التصنيف *
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    CTNS
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    PCS/CTN
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    QTY
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    PRICE
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    AMOUNT
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    CBM
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    T/CBM
                                </th>

                                <th class="px-4 py-4 font-bold text-gray-700">
                                    الحالة
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @foreach ($sheet['rows'] ?? [] as $rowIndex => $row)

                                @php
                                    $hasName = filled($row['name'] ?? null);
                                    $hasCategory = filled($row['category_id'] ?? null);
                                    $ready = $hasName && $hasCategory;
                                    $status = $row['_status'] ?? 'failed';

                                    $itemNo = trim((string) ($row['item_no'] ?? ''));

                                    $existingProductItems = collect(
                                        $preview['duplicates']['existing_products'] ?? []
                                    )->keys()->map(
                                        fn ($item) => trim((string) $item)
                                    )->flip();

                                    $isExistingProduct = $itemNo !== ''
                                        && $existingProductItems->has($itemNo);
                                @endphp


                                <tr class="transition duration-150 hover:bg-gray-50">

                                    {{-- Row --}}
                                    <td class="px-4 py-4 align-top text-xs font-semibold text-gray-500">
                                        {{ $row['_row_number'] ?? ($rowIndex + 1) }}
                                    </td>


                                    {{-- ITEM --}}
                                    <td class="px-4 py-4 align-top">

                                        <span class="font-mono text-sm font-semibold text-gray-900">
                                            {{ $row['item_no'] ?? '—' }}
                                        </span>

                                    </td>


                                    {{-- Product Image --}}
                                    <td class="w-[150px] min-w-[150px] max-w-[180px] px-4 py-4 align-top">

                                        @php
                                            $imagePaths = $row['image_paths'] ?? [];

                                            if (
                                                empty($imagePaths)
                                                && filled($row['image_path'] ?? null)
                                            ) {
                                                $imagePaths = [$row['image_path']];
                                            }

                                            $imagePaths = array_values(
                                                array_filter($imagePaths)
                                            );
                                        @endphp

                                        @if (count($imagePaths) > 0)

                                            <div class="flex w-full flex-wrap items-center justify-center gap-2">

                                                @foreach (array_slice($imagePaths, 0, 3) as $imagePath)

                                                    @php
                                                        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath);
                                                    @endphp

                                                    <button
                                                        type="button"
                                                        x-data
                                                        @click="$dispatch('open-product-image', {
                                                            url: '{{ $imageUrl }}',
                                                            item: '{{ $row['item_no'] ?? 'المنتج' }}'
                                                        })"
                                                        class="group relative flex h-24 w-full max-w-[130px] items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200 hover:border-[#238B95] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#238B95]/30"
                                                    >

                                                        <img
                                                            src="{{ $imageUrl }}"
                                                            alt="صورة {{ $row['item_no'] ?? 'المنتج' }}"
                                                            class="block max-h-full max-w-full object-contain p-2 transition-transform duration-200 group-hover:scale-105"
                                                            loading="lazy"
                                                        >

                                                        <div
                                                            class="absolute inset-x-0 bottom-0 bg-black/50 px-1 py-1 text-center text-[9px] font-semibold text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                                                        >
                                                            عرض الصورة
                                                        </div>

                                                    </button>

                                                @endforeach

                                                @if (count($imagePaths) > 3)

                                                    <span
                                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-[#EAF6F7] px-2 text-xs font-bold text-[#176C74]"
                                                        title="عدد الصور: {{ count($imagePaths) }}"
                                                    >
                                                        +{{ count($imagePaths) - 3 }}
                                                    </span>

                                                @endif

                                            </div>

                                            <div class="mt-2 flex items-center justify-center gap-1 text-[11px] font-semibold text-emerald-600">

                                                <x-heroicon-s-check-circle class="h-4 w-4 shrink-0" />

                                                <span>
                                                    {{ count($imagePaths) }} صورة
                                                </span>

                                            </div>

                                        @else

                                            <div class="flex min-h-[96px] w-full flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-3 py-4">

                                                <x-heroicon-o-photo class="h-8 w-8 text-gray-300" />

                                                <span class="mt-1 text-[11px] font-semibold text-gray-400">
                                                    لا توجد صورة
                                                </span>

                                            </div>

                                        @endif

                                    </td>


                                    {{-- Product Name --}}
                                    <td class="px-4 py-4 align-top">

                                        <input
                                            type="text"
                                            wire:model.live="preview.sheets.{{ $sheetIndex }}.rows.{{ $rowIndex }}.name"
                                            placeholder="أدخل اسم المنتج"
                                            class="w-full rounded-xl border-gray-300 text-sm shadow-sm transition focus:border-[#238B95] focus:ring-[#238B95]"
                                        >

                                        @if (!$hasName)

                                            <p class="mt-1 text-xs font-medium text-red-600">
                                                الاسم مطلوب.
                                            </p>

                                        @endif

                                    </td>


                                    {{-- Category --}}
                                    <td class="px-4 py-4 align-top">

                                        <select
                                            wire:model.live="preview.sheets.{{ $sheetIndex }}.rows.{{ $rowIndex }}.category_id"
                                            class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm transition focus:border-[#238B95] focus:ring-[#238B95]"
                                        >

                                            <option value="">
                                                اختر التصنيف
                                            </option>

                                            @foreach ($this->categories as $categoryId => $categoryName)

                                                <option value="{{ $categoryId }}">
                                                    {{ $categoryName }}
                                                </option>

                                            @endforeach

                                        </select>

                                        @if (!$hasCategory)

                                            <p class="mt-1 text-xs font-medium text-red-600">
                                                التصنيف مطلوب.
                                            </p>

                                        @endif

                                    </td>


                                    {{-- CTNS --}}
                                    <td class="px-4 py-4 align-top text-gray-700">
                                        {{ $row['cartons_count'] ?? '—' }}
                                    </td>


                                    {{-- PCS/CTN --}}
                                    <td class="px-4 py-4 align-top text-gray-700">
                                        {{ $row['qty_per_carton'] ?? '—' }}
                                    </td>


                                    {{-- QTY --}}
                                    <td class="px-4 py-4 align-top font-semibold text-gray-800">
                                        {{ $row['total_quantity'] ?? '—' }}
                                    </td>


                                    {{-- PRICE --}}
                                    <td class="px-4 py-4 align-top text-gray-700">
                                        {{ $row['unit_price'] ?? '—' }}
                                    </td>


                                    {{-- AMOUNT --}}
                                    <td class="px-4 py-4 align-top text-gray-700">
                                        {{ $row['subtotal_price'] ?? '—' }}
                                    </td>


                                    {{-- CBM --}}
                                    <td class="px-4 py-4 align-top text-gray-700">
                                        {{ $row['cbm_per_carton'] ?? '—' }}
                                    </td>


                                    {{-- T/CBM --}}
                                    <td class="px-4 py-4 align-top text-gray-700">
                                        {{ $row['total_cbm'] ?? '—' }}
                                    </td>


                                    {{-- Status --}}
                                    <td class="px-4 py-4 align-top">

                                        @if (!$ready || $status === 'failed')

                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                                            >
                                                <x-heroicon-s-exclamation-triangle class="h-4 w-4" />

                                                يحتاج مراجعة
                                            </span>

                                        @elseif ($isExistingProduct)

                                            <div class="flex flex-col items-start gap-1">

                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-[#EAF6F7] px-3 py-1 text-xs font-semibold text-[#176C74]"
                                                >
                                                    <x-heroicon-s-arrow-path class="h-4 w-4" />

                                                    موجود — سيتم تحديثه
                                                </span>

                                                <span class="text-[10px] text-gray-400">
                                                    لن يتم إنشاء نسخة جديدة
                                                </span>

                                            </div>

                                        @else

                                            <div class="flex flex-col items-start gap-1">

                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
                                                >
                                                    <x-heroicon-s-plus-circle class="h-4 w-4" />

                                                    جديد — سيتم إنشاؤه
                                                </span>

                                            </div>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @endforeach


        {{-- =====================================================
             Final Import Action
        ====================================================== --}}
        @php

            $allReady = true;

            foreach ($preview['sheets'] ?? [] as $sheet) {

                foreach ($sheet['rows'] ?? [] as $row) {

                    if (
                        blank($row['name'] ?? null) ||
                        blank($row['category_id'] ?? null)
                    ) {
                        $allReady = false;
                        break 2;
                    }

                }

            }

        @endphp


        <div
            class="sticky bottom-4 z-20 rounded-2xl border border-gray-200 bg-white/95 p-5 shadow-lg backdrop-blur"
        >

            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>

                    @if ($allReady)

                        @php
                            $existingProducts = $this->summary['existing_products'] ?? 0;
                            $totalProducts = $this->summary['total_rows'] ?? 0;
                        @endphp

                        <p class="font-bold text-emerald-700">
                            جميع البيانات جاهزة للتنفيذ.
                        </p>

                        <p class="mt-1 text-xs leading-5 text-gray-500">

                            @if ($existingProducts > 0 && $existingProducts < $totalProducts)

                                سيتم إنشاء المنتجات الجديدة وتحديث
                                <strong>{{ number_format($existingProducts) }}</strong>
                                منتج موجود مسبقًا.

                            @elseif ($existingProducts === $totalProducts && $totalProducts > 0)

                                جميع المنتجات موجودة مسبقًا، وسيتم
                                <strong>تحديث بياناتها فقط</strong>
                                دون إنشاء نسخ جديدة.

                            @else

                                سيتم إنشاء المنتجات الجديدة في قاعدة البيانات بعد التأكيد.

                            @endif

                        </p>

                    @else

                        <p class="font-bold text-amber-700">
                            توجد بيانات ناقصة.
                        </p>

                        <p class="mt-1 text-xs leading-5 text-gray-500">
                            أكمل اسم المنتج والتصنيف لجميع الصفوف قبل التأكيد.
                        </p>

                    @endif

                </div>


                <button
                    type="button"
                    wire:click="confirmImport"
                    wire:loading.attr="disabled"
                    wire:target="confirmImport"
                    @disabled(!$allReady)
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#238B95] px-7 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#176C74] disabled:cursor-not-allowed disabled:opacity-40"
                >

                    <span
                        wire:loading.remove
                        wire:target="confirmImport"
                    >
                        تأكيد استيراد المنتجات
                    </span>

                    <span
                        wire:loading
                        wire:target="confirmImport"
                        class="inline-flex items-center gap-2"
                    >

                        <svg
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >

                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-90"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />

                        </svg>

                        جارٍ الاستيراد...

                    </span>

                </button>

            </div>

        </div>


        {{-- =====================================================
             Import Result
        ====================================================== --}}
        @if ($importResult)

            @php
                $createdProducts = $importResult['created_products'] ?? 0;
                $updatedProducts = $importResult['updated_products'] ?? 0;
                $createdImages = $importResult['created_images'] ?? 0;
                $updatedImages = $importResult['updated_images'] ?? 0;
                $failedRows = $importResult['failed_rows'] ?? 0;
                $totalRows = $importResult['total_rows'] ?? 0;
                $successfulRows = $importResult['successful_rows'] ?? 0;

                $isUpdateOnly =
                    $createdProducts === 0 &&
                    $updatedProducts > 0;

                $isCreateOnly =
                    $createdProducts > 0 &&
                    $updatedProducts === 0;

                $isMixed =
                    $createdProducts > 0 &&
                    $updatedProducts > 0;
            @endphp


            <div
                class="overflow-hidden rounded-2xl border
                    {{ $failedRows > 0
                        ? 'border-amber-200 bg-amber-50'
                        : 'border-emerald-200 bg-emerald-50' }}
                    shadow-sm"
            >

                {{-- Result Header --}}
                <div class="p-6">

                    <div class="flex items-start gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl
                                {{ $failedRows > 0
                                    ? 'bg-amber-100 text-amber-600'
                                    : 'bg-emerald-100 text-emerald-600' }}"
                        >

                            @if ($isUpdateOnly)

                                <x-heroicon-s-arrow-path class="h-6 w-6" />

                            @elseif ($isMixed)

                                <x-heroicon-s-arrows-right-left class="h-6 w-6" />

                            @else

                                <x-heroicon-s-check-circle class="h-6 w-6" />

                            @endif

                        </div>


                        <div class="min-w-0 flex-1">

                            @if ($isUpdateOnly)

                                <h3 class="text-lg font-bold text-[#176C74]">
                                    تم تحديث المنتجات الموجودة
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    لم يتم إنشاء منتجات جديدة.
                                    تم العثور على المنتجات الموجودة مسبقًا وتحديث بياناتها
                                    وفقًا لملف Excel.
                                </p>

                            @elseif ($isMixed)

                                <h3 class="text-lg font-bold text-[#176C74]">
                                    تم إنشاء وتحديث المنتجات
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    تم إنشاء المنتجات الجديدة وتحديث المنتجات الموجودة مسبقًا
                                    وفقًا لبيانات ملف Excel.
                                </p>

                            @elseif ($isCreateOnly)

                                <h3 class="text-lg font-bold text-emerald-800">
                                    تم إنشاء المنتجات الجديدة
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    تم إنشاء المنتجات غير الموجودة مسبقًا في قاعدة البيانات.
                                </p>

                            @else

                                <h3 class="text-lg font-bold text-emerald-800">
                                    اكتملت عملية الاستيراد
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    تم تنفيذ عملية الاستيراد.
                                </p>

                            @endif

                        </div>

                    </div>


                    {{-- Operation Summary --}}
                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

                        {{-- Created --}}
                        <div class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm">

                            <div class="flex items-center gap-2">

                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <x-heroicon-o-plus-circle class="h-5 w-5" />
                                </div>

                                <div>

                                    <p class="text-xs font-medium text-gray-500">
                                        منتجات جديدة
                                    </p>

                                    <p class="mt-0.5 text-2xl font-bold text-gray-900">
                                        {{ number_format($createdProducts) }}
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Updated --}}
                        <div class="rounded-2xl border border-[#BFE3E6] bg-white p-4 shadow-sm">

                            <div class="flex items-center gap-2">

                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF6F7] text-[#238B95]">
                                    <x-heroicon-o-arrow-path class="h-5 w-5" />
                                </div>

                                <div>

                                    <p class="text-xs font-medium text-gray-500">
                                        منتجات محدثة
                                    </p>

                                    <p class="mt-0.5 text-2xl font-bold text-[#176C74]">
                                        {{ number_format($updatedProducts) }}
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Images --}}
                        <div class="rounded-2xl border border-purple-100 bg-white p-4 shadow-sm">

                            <div class="flex items-center gap-2">

                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                    <x-heroicon-o-photo class="h-5 w-5" />
                                </div>

                                <div>

                                    <p class="text-xs font-medium text-gray-500">
                                        صور مضافة
                                    </p>

                                    <p class="mt-0.5 text-2xl font-bold text-gray-900">
                                        {{ number_format($createdImages) }}
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Failed --}}
                        <div
                            class="rounded-2xl border
                                {{ $failedRows > 0
                                    ? 'border-red-100 bg-white'
                                    : 'border-gray-100 bg-white' }}
                                p-4 shadow-sm"
                        >

                            <div class="flex items-center gap-2">

                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl
                                        {{ $failedRows > 0
                                            ? 'bg-red-50 text-red-600'
                                            : 'bg-gray-50 text-gray-400' }}"
                                >
                                    <x-heroicon-o-x-circle class="h-5 w-5" />
                                </div>

                                <div>

                                    <p class="text-xs font-medium text-gray-500">
                                        صفوف فاشلة
                                    </p>

                                    <p
                                        class="mt-0.5 text-2xl font-bold
                                            {{ $failedRows > 0
                                                ? 'text-red-700'
                                                : 'text-gray-700' }}"
                                    >
                                        {{ number_format($failedRows) }}
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Detailed Update Message --}}
                    @if ($isUpdateOnly)

                        <div class="mt-5 rounded-2xl border border-[#BFE3E6] bg-white/80 p-4">

                            <div class="flex items-start gap-3">

                                <x-heroicon-o-information-circle
                                    class="mt-0.5 h-5 w-5 shrink-0 text-[#238B95]"
                                />

                                <div>

                                    <p class="text-sm font-bold text-[#176C74]">
                                        ماذا حدث؟
                                    </p>

                                    <p class="mt-1 text-sm leading-6 text-gray-600">
                                        جميع المنتجات المستوردة كانت موجودة مسبقًا في قاعدة البيانات،
                                        لذلك لم يتم إنشاء نسخ جديدة منها.
                                        تم تحديث بيانات المنتجات الموجودة فقط.
                                    </p>

                                    @if ($createdImages > 0 || $updatedImages > 0)

                                        <p class="mt-2 text-sm font-semibold text-gray-700">
                                            وتمت معالجة
                                            {{ number_format($createdImages + $updatedImages) }}
                                            صورة مرتبطة بالمنتجات.
                                        </p>

                                    @endif

                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- General Statistics --}}
                    <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-gray-200/70 pt-4 text-xs text-gray-500">

                        <span>
                            إجمالي الصفوف:
                            <strong class="text-gray-700">
                                {{ number_format($totalRows) }}
                            </strong>
                        </span>

                        <span>
                            تم تنفيذها:
                            <strong class="text-gray-700">
                                {{ number_format($successfulRows) }}
                            </strong>
                        </span>

                        @if ($createdImages > 0)

                            <span>
                                صور مضافة:
                                <strong class="text-gray-700">
                                    {{ number_format($createdImages) }}
                                </strong>
                            </span>

                        @endif

                        @if ($updatedImages > 0)

                            <span>
                                صور مستبدلة:
                                <strong class="text-gray-700">
                                    {{ number_format($updatedImages) }}
                                </strong>
                            </span>

                        @endif

                        <span>
                            Batch ID:
                            <strong class="font-mono text-gray-700">
                                #{{ $importResult['batch_id'] ?? '—' }}
                            </strong>
                        </span>

                    </div>

                </div>

            </div>

        @endif

    @endif


    {{-- =========================================================
         Product Image Modal
    ========================================================== --}}
    <div
        x-data="{
            open: false,
            imageUrl: '',
            itemNo: '',

            openImage(event) {
                this.imageUrl = event.detail.url;
                this.itemNo = event.detail.item;
                this.open = true;
            },

            closeImage() {
                this.open = false;
                this.imageUrl = '';
                this.itemNo = '';
            }
        }"

        x-on:open-product-image.window="openImage($event)"

        x-show="open"
        x-cloak

        x-on:keydown.escape.window="closeImage()"

        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
        style="display: none;"
    >

        {{-- Background --}}
        <div
            class="absolute inset-0 bg-black/70 backdrop-blur-sm"
            x-on:click="closeImage()"
        ></div>


        {{-- Modal --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"

            class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
            x-on:click.stop
        >

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">

                <div class="min-w-0">

                    <h3 class="truncate text-base font-bold text-gray-900">
                        صورة المنتج
                    </h3>

                    <p
                        x-show="itemNo"
                        x-text="itemNo"
                        class="mt-0.5 text-xs font-medium text-gray-500"
                    ></p>

                </div>


                {{-- Close Button --}}
                <button
                    type="button"
                    x-on:click="closeImage()"

                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#238B95]/30"
                    aria-label="إغلاق"
                >

                    <x-heroicon-o-x-mark class="h-6 w-6" />

                </button>

            </div>


            {{-- Image Area --}}
            <div class="flex min-h-[300px] max-h-[calc(92vh-130px)] flex-1 items-center justify-center overflow-auto bg-gray-50 p-6">

                <img
                    x-show="imageUrl"
                    :src="imageUrl"
                    :alt="'صورة المنتج ' + itemNo"

                    class="max-h-[70vh] max-w-full rounded-xl object-contain shadow-sm"
                >

            </div>


            {{-- Footer --}}
            <div class="flex items-center justify-end border-t border-gray-200 bg-white px-5 py-3">

                <button
                    type="button"
                    x-on:click="closeImage()"

                    class="inline-flex items-center gap-2 rounded-xl bg-[#238B95] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#176C74] focus:outline-none focus:ring-2 focus:ring-[#238B95]/30"
                >

                    <x-heroicon-o-arrow-right class="h-4 w-4" />

                    إغلاق

                </button>

            </div>

        </div>

    </div>

</div>

</x-filament-panels::page>
