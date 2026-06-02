<x-filament-panels::page>
    <div class="sickas-page space-y-6">
        @php
            $summary = $this->getSummary();
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gradient-to-r from-sky-50 via-white to-emerald-50 px-5 py-5 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
                <p class="text-sm font-medium text-sky-700 dark:text-sky-300">Laporan</p>
                <div class="mt-1 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Laporan Populasi Ternak</h1>
                        <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Ringkasan populasi awal, aktif, mati, afkir, dan terjual per batch penggemukan.
                        </p>
                    </div>
                    <x-filament::badge color="info">
                        Kontrol populasi
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ternak Awal</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->formatNumber($summary['initial']) }} ekor</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jumlah awal seluruh batch terfilter.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ternak Aktif</p>
                <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">{{ $this->formatNumber($summary['active']) }} ekor</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Populasi yang masih digemukkan.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ternak Mati</p>
                <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $this->formatNumber($summary['dead']) }} ekor</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akumulasi catatan kematian.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ternak Afkir</p>
                <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $this->formatNumber($summary['culled']) }} ekor</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ternak yang keluar karena afkir.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ternak Terjual</p>
                <p class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ $this->formatNumber($summary['sold']) }} ekor</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ternak yang sudah masuk transaksi jual.</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filter Laporan</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saring berdasarkan kandang, batch, status ternak, dan tanggal pembelian.</p>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
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
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status Ternak</span>
                    <select
                        wire:model.live="sheepStatus"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Semua Status</option>
                        @foreach ($this->getSheepStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Beli Dari</span>
                    <input
                        type="date"
                        wire:model.live="purchaseDateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Beli Sampai</span>
                    <input
                        type="date"
                        wire:model.live="purchaseDateUntil"
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
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $row['pen_name'] }} - {{ $row['supplier_name'] }}</p>
                        </div>
                        <x-filament::badge :color="$this->batchStatusColor($row['batch_status'])">
                            {{ $this->batchStatusLabel($row['batch_status']) }}
                        </x-filament::badge>
                    </div>
                    <div class="mt-2">
                        <x-filament::badge :color="$row['source_color']">
                            {{ $row['source_label'] }}
                        </x-filament::badge>
                        <x-filament::badge color="info">
                            {{ $row['livestock_type_name'] }}
                        </x-filament::badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Tanggal Beli</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatDate($row['purchase_date']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Jumlah Awal</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $row['initial_head_count'] }} ekor</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Aktif</dt>
                            <dd class="font-medium text-green-600 dark:text-green-400">{{ $row['active_head_count'] }} ekor</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Terjual</dt>
                            <dd class="font-medium text-blue-600 dark:text-blue-400">{{ $row['sold_head_count'] }} ekor</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Mati</dt>
                            <dd class="font-medium text-red-600 dark:text-red-400">{{ $row['dead_head_count'] }} ekor</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Afkir</dt>
                            <dd class="font-medium text-red-600 dark:text-red-400">{{ $row['culled_head_count'] }} ekor</dd>
                        </div>
                    </dl>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    Belum ada data populasi ternak.
                </div>
            @endforelse
        </div>

        <div class="sickas-table-card hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:block">
            <div class="sickas-table-scroll">
                <table class="sickas-table min-w-[1200px] divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Kode Batch</th>
                            <th class="px-4 py-3">Jenis Ternak</th>
                            <th class="px-4 py-3">Kandang</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Tanggal Beli</th>
                            <th class="px-4 py-3 text-right">Jumlah Awal</th>
                            <th class="px-4 py-3 text-right">Jumlah Aktif</th>
                            <th class="px-4 py-3 text-right">Jumlah Mati</th>
                            <th class="px-4 py-3 text-right">Jumlah Afkir</th>
                            <th class="px-4 py-3 text-right">Jumlah Terjual</th>
                            <th class="px-4 py-3">Status Batch</th>
                            <th class="px-4 py-3">Sumber</th>
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
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['pen_name'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['supplier_name'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $this->formatDate($row['purchase_date']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $row['initial_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">{{ $row['active_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $row['dead_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $row['culled_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ $row['sold_head_count'] }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$this->batchStatusColor($row['batch_status'])">
                                        {{ $this->batchStatusLabel($row['batch_status']) }}
                                    </x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$row['source_color']">
                                        {{ $row['source_label'] }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data populasi ternak.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
