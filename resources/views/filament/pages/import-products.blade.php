<x-filament-panels::page>
    <form wire:submit="analyze">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button
                type="submit"
                icon="heroicon-o-magnifying-glass"
            >
                تحليل الملف
            </x-filament::button>
        </div>
    </form>

    @if ($preview)
        <div class="mt-8 space-y-6">
            <x-filament::section>
                <x-slot name="heading">
                    نتيجة التحليل
                </x-slot>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-sm text-gray-500">
                            اسم الملف
                        </div>
                        <div class="font-medium">
                            {{ $preview['file_name'] }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            إجمالي الصفوف
                        </div>
                        <div class="font-medium">
                            {{ $preview['total_rows'] }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">
                            عدد الأوراق
                        </div>
                        <div class="font-medium">
                            {{ count($preview['sheets']) }}
                        </div>
                    </div>
                </div>
            </x-filament::section>

            @foreach ($preview['sheets'] as $sheet)
                <x-filament::section>
                    <x-slot name="heading">
                        {{ $sheet['name'] }}
                    </x-slot>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                الصفوف:
                                <strong>{{ $sheet['data_rows'] }}</strong>
                            </div>

                            <div>
                                ITEM NO:
                                <strong>
                                    {{ $sheet['has_item_no'] ? 'موجود' : 'مفقود' }}
                                </strong>
                            </div>

                            <div>
                                CATEGORY_CODE:
                                <strong>
                                    {{ $sheet['has_category_code'] ? 'موجود' : 'غير موجود' }}
                                </strong>
                            </div>
                        </div>

                        @if ($sheet['errors'])
                            <div class="rounded-lg border border-danger-200 p-4">
                                <div class="font-semibold">
                                    أخطاء
                                </div>

                                @foreach ($sheet['errors'] as $error)
                                    <div class="mt-2">
                                        {{ $error }}
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($sheet['warnings'])
                            <div class="rounded-lg border p-4">
                                <div class="font-semibold">
                                    تحذيرات
                                </div>

                                @foreach ($sheet['warnings'] as $warning)
                                    <div class="mt-2">
                                        {{ $warning }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach

            @if ($preview['errors'])
                <x-filament::section>
                    <x-slot name="heading">
                        أخطاء عامة
                    </x-slot>

                    @foreach ($preview['errors'] as $error)
                        <div class="mb-2">
                            {{ $error }}
                        </div>
                    @endforeach
                </x-filament::section>
            @endif

            @if ($preview['warnings'])
                <x-filament::section>
                    <x-slot name="heading">
                        تحذيرات عامة
                    </x-slot>

                    @foreach ($preview['warnings'] as $warning)
                        <div class="mb-2">
                            {{ $warning }}
                        </div>
                    @endforeach
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>