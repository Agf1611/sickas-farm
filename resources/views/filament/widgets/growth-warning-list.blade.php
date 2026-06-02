<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Peringatan Pertumbuhan Ternak
        </x-slot>

        <x-slot name="description">
            Daftar singkat batch aktif yang perlu mendapat perhatian pengurus.
        </x-slot>

        @php
            $warnings = $this->getWarnings();
            $groups = [
                'down' => ['title' => 'Berat Turun', 'color' => 'danger', 'empty' => 'Tidak ada batch berat turun.'],
                'slow' => ['title' => 'ADG Lambat', 'color' => 'warning', 'empty' => 'Tidak ada batch ADG lambat.'],
                'not_weighed_overdue' => ['title' => 'Belum Ditimbang > 14 Hari', 'color' => 'danger', 'empty' => 'Tidak ada batch aktif yang terlambat timbang awal.'],
            ];
        @endphp

        <div class="grid gap-3 lg:grid-cols-3">
            @foreach ($groups as $key => $group)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $group['title'] }}</h3>
                        <x-filament::badge :color="$group['color']">
                            {{ count($warnings[$key]) }} batch
                        </x-filament::badge>
                    </div>

                    <div class="space-y-3">
                        @forelse ($warnings[$key] as $row)
                            <div class="rounded-lg bg-gray-50 p-3 text-sm ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-gray-950 dark:text-white">{{ $row['batch_code'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['pen_name'] }}</p>
                                    </div>
                                    <x-filament::badge :color="$key === 'slow' ? 'warning' : 'danger'">
                                        {{ $row['status'] }}
                                    </x-filament::badge>
                                </div>

                                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Kenaikan</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $this->formatKg($row['weight_gain']) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">ADG</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $this->formatAdg($row['adg']) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        @empty
                            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500 ring-1 ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                                {{ $group['empty'] }}
                            </p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
