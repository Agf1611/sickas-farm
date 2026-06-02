<x-filament-panels::page>
    <div class="sickas-page space-y-6">
        @php
            $batch = $this->getSelectedBatch();
            $rows = $this->getActiveSheepRows();
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gradient-to-r from-emerald-50 via-white to-amber-50 px-5 py-5 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Operasional Ternak</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Timbang Per Ekor</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                    Catat berat aktual masing-masing ternak dalam batch. Data ini dipakai untuk melihat pertumbuhan per ekor dan membuat ringkasan total batch.
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Batch Penggemukan</span>
                    <select
                        wire:model.live="batchId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Pilih Batch</option>
                        @foreach ($this->getBatchOptions() as $id => $code)
                            <option value="{{ $id }}">{{ $code }}</option>
                        @endforeach
                    </select>
                    @error('batchId')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Timbang</span>
                    <input
                        type="date"
                        wire:model.live="weighedAt"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                    @error('weighedAt')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="space-y-1 md:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Catatan Ringkasan Batch</span>
                    <input
                        type="text"
                        wire:model.live="notes"
                        placeholder="Opsional, contoh: Timbang per ekor periode awal Juni"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </label>
            </div>
        </div>

        @if ($batch)
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Batch</p>
                    <p class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">{{ $batch->batch_code }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $batch->pen?->name ?? 'Tanpa kandang' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Jenis Ternak</p>
                    <p class="mt-2 text-xl font-semibold text-sky-600 dark:text-sky-400">{{ $batch->livestockType?->name ?? 'Domba' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hanya ternak aktif yang tampil.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Populasi Aktif</p>
                    <p class="mt-2 text-xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $rows->count() }} ekor</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Siap ditimbang per ekor.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bobot Awal Batch</p>
                    <p class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">{{ $this->formatKg((float) $batch->initial_total_weight_kg) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pembanding pertumbuhan.</p>
                </div>
            </div>
        @endif

        @error('weights')
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        @if (! $batch)
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                Pilih batch terlebih dahulu untuk menampilkan daftar ternak aktif.
            </div>
        @elseif ($rows->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                Tidak ada ternak aktif dalam batch ini. Ternak yang sudah terjual, mati, hilang, sakit, atau afkir tidak ditampilkan.
            </div>
        @else
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-3 lg:hidden">
                    @foreach ($rows as $row)
                        @php
                            $sheep = $row['sheep'];
                            $growth = $row['growth'];
                        @endphp
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $sheep->tag_number }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Awal {{ $this->formatKg((float) $sheep->initial_weight_kg) }} - Terakhir {{ $this->formatKg($growth['latest_weight']) }}
                                    </p>
                                </div>
                                <x-filament::badge :color="$this->statusColor($growth['status'])">
                                    {{ $growth['status'] }}
                                </x-filament::badge>
                            </div>

                            <div class="mt-4 grid gap-3">
                                <label class="space-y-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Berat Sekarang</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.defer="weights.{{ $sheep->id }}"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                        placeholder="kg"
                                    >
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Catatan</span>
                                    <input
                                        type="text"
                                        wire:model.defer="itemNotes.{{ $sheep->id }}"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                        placeholder="Opsional"
                                    >
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Ternak</th>
                                    <th class="px-4 py-3">Berat Awal</th>
                                    <th class="px-4 py-3">Berat Terakhir</th>
                                    <th class="px-4 py-3">ADG</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Berat Sekarang</th>
                                    <th class="px-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($rows as $row)
                                    @php
                                        $sheep = $row['sheep'];
                                        $growth = $row['growth'];
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $sheep->tag_number }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $this->formatKg((float) $sheep->initial_weight_kg) }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $this->formatKg($growth['latest_weight']) }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $this->formatAdg($growth['adg']) }}</td>
                                        <td class="px-4 py-3">
                                            <x-filament::badge :color="$this->statusColor($growth['status'])">
                                                {{ $growth['status'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                wire:model.defer="weights.{{ $sheep->id }}"
                                                class="w-32 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                                placeholder="kg"
                                            >
                                        </td>
                                        <td class="px-4 py-3">
                                            <input
                                                type="text"
                                                wire:model.defer="itemNotes.{{ $sheep->id }}"
                                                class="w-56 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                                placeholder="Opsional"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="submit"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        Simpan Timbang Per Ekor
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-filament-panels::page>
