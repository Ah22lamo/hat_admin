<x-filament-widgets::widget>
    <x-filament::section>
        <div class="mb-5">
            <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                إجراءات سريعة
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                الوصول السريع إلى العمليات الأكثر استخدامًا
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="group rounded-2xl border border-gray-200 bg-white p-5 transition duration-200 hover:-translate-y-1 hover:border-primary-400 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-950 dark:text-primary-400">
                            <x-filament::icon
                                :icon="$action['icon']"
                                class="h-6 w-6"
                            />
                        </div>

                        <div>
                            <h3 class="font-bold text-gray-950 dark:text-white">
                                {{ $action['label'] }}
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                {{ $action['description'] }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>