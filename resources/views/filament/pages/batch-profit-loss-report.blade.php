<x-filament-panels::page>
    <div class="sickas-page space-y-6">
        @php
            $summary = $this->getSummary();
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gradient-to-r from-amber-50 via-white to-emerald-50 px-5 py-5 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
                <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Laporan Keuangan</p>
                <div class="mt-1 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Laporan Laba Rugi Per Batch</h1>
                        <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Perbandingan penjualan, modal pembelian, dan biaya penggemukan untuk setiap batch.
                        </p>
                    </div>
                    <x-filament::badge :color="$summary['profit_loss'] >= 0 ? 'success' : 'danger'">
                        {{ $summary['profit_loss'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Modal Pembelian</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($summary['purchase_capital']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Modal pembelian batch terfilter.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $this->formatRupiah($summary['total_expenses']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Biaya penggemukan yang tercatat.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Penjualan</p>
                <p class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">{{ $this->formatRupiah($summary['total_sales']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nilai jual dari batch terkait.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Laba / Rugi</p>
                <p class="mt-2 text-2xl font-semibold {{ $this->profitColor($summary['profit_loss']) }}">{{ $this->formatRupiah($summary['profit_loss']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Penjualan - modal - pengeluaran.</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filter Laporan</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saring batch dan periode transaksi yang dihitung.</p>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
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
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Dari Tanggal</span>
                    <input
                        type="date"
                        wire:model.live="periodFrom"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Sampai Tanggal</span>
                    <input
                        type="date"
                        wire:model.live="periodUntil"
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
                        <x-filament::badge :color="$this->batchStatusColor($row['status'])">
                            {{ $this->batchStatusLabel($row['status']) }}
                        </x-filament::badge>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <x-filament::badge :color="$row['source_color']">
                            {{ $row['source_label'] }}
                        </x-filament::badge>
                        <x-filament::badge color="info">
                            {{ $row['livestock_type_name'] }}
                        </x-filament::badge>
                        <x-filament::badge :color="$this->profitBadgeColor($row['profit_loss'])">
                            {{ $row['profit_loss'] >= 0 ? 'Laba' : 'Rugi' }}
                        </x-filament::badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Ternak</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $row['current_head_count'] }} / {{ $row['initial_head_count'] }} ekor</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Modal</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $this->formatRupiah($row['purchase_capital']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Pengeluaran</dt>
                            <dd class="font-medium text-amber-600 dark:text-amber-400">{{ $this->formatRupiah($row['total_expenses']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Penjualan</dt>
                            <dd class="font-medium text-green-600 dark:text-green-400">{{ $this->formatRupiah($row['total_sales']) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Laba / Rugi</p>
                        <p class="mt-1 text-lg font-semibold {{ $this->profitColor($row['profit_loss']) }}">{{ $this->formatRupiah($row['profit_loss']) }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    Belum ada data batch untuk ditampilkan.
                </div>
            @endforelse
        </div>

        <div class="sickas-table-card hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:block">
            <div class="sickas-table-scroll">
                <table class="sickas-table min-w-[1350px] divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Kode Batch</th>
                            <th class="px-4 py-3">Jenis Ternak</th>
                            <th class="px-4 py-3">Kandang</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Sumber</th>
                            <th class="px-4 py-3 text-right">Jumlah Awal</th>
                            <th class="px-4 py-3 text-right">Jumlah Saat Ini</th>
                            <th class="px-4 py-3 text-right">Modal Pembelian</th>
                            <th class="px-4 py-3 text-right">Total Pengeluaran</th>
                            <th class="px-4 py-3 text-right">Total Penjualan</th>
                            <th class="px-4 py-3 text-right">Laba / Rugi</th>
                            <th class="px-4 py-3">Status</th>
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
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$row['source_color']">
                                        {{ $row['source_label'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatNumber($row['initial_head_count']) }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatNumber($row['current_head_count']) }} ekor</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $this->formatRupiah($row['purchase_capital']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-amber-600 dark:text-amber-400">{{ $this->formatRupiah($row['total_expenses']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $this->formatRupiah($row['total_sales']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $this->profitColor($row['profit_loss']) }}">{{ $this->formatRupiah($row['profit_loss']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge :color="$this->batchStatusColor($row['status'])">
                                        {{ $this->batchStatusLabel($row['status']) }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data batch untuk ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
