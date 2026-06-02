<x-filament-panels::page>
    <div class="sickas-page space-y-6">
        @php
            $summary = $this->getSummary();
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gradient-to-r from-emerald-50 via-white to-amber-50 px-5 py-5 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Laporan</p>
                <div class="mt-1 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Laporan Performa Penggemukan</h1>
                        <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Evaluasi kenaikan berat, ADG, dan rekomendasi tindak lanjut untuk setiap batch penggemukan.
                        </p>
                    </div>
                    <x-filament::badge color="success">
                        Evaluasi performa
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Batch</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['total_batches'] }} batch</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Batch sesuai filter laporan.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Rata-rata ADG</p>
                <p class="mt-2 text-2xl font-semibold text-sky-600 dark:text-sky-400">{{ $this->formatAdg($summary['average_adg']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Performa kenaikan berat harian.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Perlu Timbang</p>
                <p class="mt-2 text-2xl font-semibold text-orange-600 dark:text-orange-400">{{ $summary['needs_reweighing'] }} batch</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Perlu pembaruan data berat.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Siap Evaluasi Jual</p>
                <p class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">{{ $summary['ready_to_review'] }} batch</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Memenuhi indikator pertimbangan jual.</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filter Laporan</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Atur periode, batch, kandang, dan status pertumbuhan.</p>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Batch</span>
                    <select
                        wire:model.live="batchId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Semua Batch</option>
                        @foreach ($this->getBatchOptions() as $id => $code)
                            <option value="{{ $id }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Ternak</span>
                    <select
                        wire:model.live="livestockTypeId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Semua Jenis</option>
                        @foreach ($this->getLivestockTypeOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Kandang</span>
                    <select
                        wire:model.live="penId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Semua Kandang</option>
                        @foreach ($this->getPenOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status Pertumbuhan</span>
                    <select
                        wire:model.live="growthStatus"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Semua Status</option>
                        @foreach ($this->getGrowthStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Dari</span>
                    <input
                        type="date"
                        wire:model.live="startDateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Sampai</span>
                    <input
                        type="date"
                        wire:model.live="startDateUntil"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </label>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    wire:click="$refresh"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                >
                    Terapkan Filter
                </button>
                <a
                    href="{{ $this->exportExcelUrl() }}"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                >
                    Export Excel
                </a>
                <a
                    href="{{ $this->exportPdfUrl() }}"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                >
                    Export PDF
                </a>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Reset Filter
                </button>
            </div>
        </div>

        <div class="grid gap-3 lg:hidden">
            @forelse ($this->getRows() as $row)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $row['batch_code'] }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $this->formatDate($row['start_date']) }} - {{ $row['pen_name'] }}
                            </p>
                        </div>
                        <x-filament::badge :color="$this->statusColor($row['status'])">
                            {{ $row['status'] }}
                        </x-filament::badge>
                    </div>
                    <div class="mt-3">
                        <x-filament::badge :color="$this->sellingIndicatorColor($row['selling_indicator'])">
                            {{ $row['selling_indicator'] }}
                        </x-filament::badge>
                        <x-filament::badge :color="$row['source_color']">
                            {{ $row['source_label'] }}
                        </x-filament::badge>
                        <x-filament::badge color="info">
                            {{ $row['livestock_type_name'] }}
                        </x-filament::badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Lama</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $row['days'] ? $row['days'].' hari' : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Ternak</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $row['current_head_count'] }} / {{ $row['initial_head_count'] }} ekor</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Berat Awal</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['initial_weight']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Berat Terakhir</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['latest_weight']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Kenaikan Total</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['weight_gain']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">ADG</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatAdg($row['adg']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Target Jual</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['target_sale_average_weight']) }}</dd>
                        </div>
                    </dl>

                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Rata-rata Awal</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['initial_average_weight']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Rata-rata Terakhir</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['latest_average_weight']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Naik Rata-rata</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatKg($row['average_weight_gain']) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $row['recommendation'] }}
                        <span class="mt-1 block text-gray-500 dark:text-gray-400">{{ $row['selling_indicator_description'] }}</span>
                    </p>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    Belum ada data performa penggemukan.
                </div>
            @endforelse
        </div>

        <div class="sickas-table-card hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:block">
            <div class="sickas-table-scroll">
                <table class="sickas-table min-w-[1900px] divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Kode Batch</th>
                            <th class="px-4 py-3">Jenis Ternak</th>
                            <th class="px-4 py-3">Sumber</th>
                            <th class="px-4 py-3">Kandang</th>
                            <th class="px-4 py-3">Tanggal Mulai</th>
                            <th class="px-4 py-3 text-right">Hari</th>
                            <th class="px-4 py-3 text-right">Ternak Awal</th>
                            <th class="px-4 py-3 text-right">Ternak Saat Ini</th>
                            <th class="px-4 py-3 text-right">Berat Awal Total</th>
                            <th class="px-4 py-3 text-right">Berat Terakhir Total</th>
                            <th class="px-4 py-3 text-right">Kenaikan Total</th>
                            <th class="px-4 py-3 text-right">Berat Awal Rata-rata</th>
                            <th class="px-4 py-3 text-right">Berat Terakhir Rata-rata</th>
                            <th class="px-4 py-3 text-right">Kenaikan Rata-rata</th>
                            <th class="px-4 py-3 text-right">ADG</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Target Jual</th>
                            <th class="px-4 py-3">Indikator Jual</th>
                            <th class="px-4 py-3">Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->getRows() as $row)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $row['batch_code'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge color="info">
                                        {{ $row['livestock_type_name'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$row['source_color']">
                                        {{ $row['source_label'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['pen_name'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $this->formatDate($row['start_date']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $row['days'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $row['initial_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $row['current_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['initial_weight']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['latest_weight']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['weight_gain']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['initial_average_weight']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['latest_average_weight']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['average_weight_gain']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-950 dark:text-white">{{ $this->formatAdg($row['adg']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$this->statusColor($row['status'])">
                                        {{ $row['status'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatKg($row['target_sale_average_weight']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$this->sellingIndicatorColor($row['selling_indicator'])">
                                        {{ $row['selling_indicator'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="min-w-72 px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ str($row['recommendation'])->limit(90) }}
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $row['selling_indicator_description'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data performa penggemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
