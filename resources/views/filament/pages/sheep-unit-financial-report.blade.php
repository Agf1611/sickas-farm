<x-filament-panels::page>
    <div class="sickas-page space-y-6">
        @php
            $data = $this->getReportData();
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gradient-to-r from-amber-50 via-white to-sky-50 px-5 py-5 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
                <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Laporan Keuangan</p>
                <div class="mt-1 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Laporan Keuangan {{ $this->unitName() }}</h1>
                        <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Ringkasan modal, pengeluaran, penjualan, dan laba/rugi bersih {{ $this->unitName() }}.
                        </p>
                    </div>
                    <x-filament::badge color="warning">
                        {{ $this->periodLabel() }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filter Laporan</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saring periode transaksi, batch, dan kandang.</p>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
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
            </div>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Periode: {{ $this->periodLabel() }}
                </p>
                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
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
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Modal Pembelian</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['purchase_capital']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Modal batch sesuai filter laporan.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Semua Pengeluaran</p>
                <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $this->formatRupiah($data['total_expenses']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Biaya pakan, obat, upah, dan lainnya.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Penjualan</p>
                <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">{{ $this->formatRupiah($data['total_sales']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nilai penjualan pada periode terpilih.</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Laba/Rugi Bersih</p>
                <p class="mt-1 text-2xl font-semibold {{ $this->profitColor($data['net_profit_loss']) }}">{{ $this->formatRupiah($data['net_profit_loss']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Penjualan dikurangi modal dan biaya.</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Rincian Biaya Penggemukan</h3>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Biaya Pakan</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['feed_expenses']) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Biaya Obat/Vitamin</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['medicine_expenses']) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Upah</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['wage_expenses']) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Transportasi</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['transport_expenses']) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Biaya Kandang/Peralatan</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['pen_equipment_expenses']) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Biaya Lain-lain</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['other_expenses']) }}</p>
                </div>
            </div>
        </div>

        <div class="sickas-table-card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="sickas-table-scroll">
                <table class="sickas-table min-w-[760px] divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Total Modal Pembelian</th>
                            <td class="px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">{{ $this->formatRupiah($data['purchase_capital']) }}</td>
                        </tr>
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Total Semua Pengeluaran</th>
                            <td class="px-4 py-3 text-right font-semibold text-amber-600 dark:text-amber-400">{{ $this->formatRupiah($data['total_expenses']) }}</td>
                        </tr>
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Total Penjualan</th>
                            <td class="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400">{{ $this->formatRupiah($data['total_sales']) }}</td>
                        </tr>
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Laba/Rugi Bersih</th>
                            <td class="px-4 py-3 text-right text-lg font-semibold {{ $this->profitColor($data['net_profit_loss']) }}">{{ $this->formatRupiah($data['net_profit_loss']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
