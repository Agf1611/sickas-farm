@php
    use App\Support\SickasFormatter;
    use Illuminate\Support\Facades\Storage;

    $header = $summary['header'];
    $population = $summary['population'];
    $weight = $summary['weight'];
    $finance = $summary['finance'];
    $warnings = collect($summary['warnings']);
    $sheepRows = collect($summary['sheep_rows']);
    $actions = collect($summary['actions']);

    $badgeClasses = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        'info' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
        'orange' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300',
        'purple' => 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-500/30 dark:bg-purple-500/10 dark:text-purple-300',
        'gray' => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300',
    ];

    $buttonClasses = [
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-500 focus-visible:ring-emerald-500',
        'info' => 'bg-sky-600 text-white hover:bg-sky-500 focus-visible:ring-sky-500',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-400 focus-visible:ring-amber-500',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-500 focus-visible:ring-rose-500',
        'orange' => 'bg-orange-600 text-white hover:bg-orange-500 focus-visible:ring-orange-500',
        'purple' => 'bg-purple-600 text-white hover:bg-purple-500 focus-visible:ring-purple-500',
        'gray' => 'bg-gray-800 text-white hover:bg-gray-700 focus-visible:ring-gray-500 dark:bg-white/10 dark:hover:bg-white/15',
    ];

    $summaryCards = [
        ['label' => 'Jumlah Awal', 'value' => SickasFormatter::number($population['initial']).' ekor', 'hint' => 'Populasi saat batch dimulai', 'tone' => 'info'],
        ['label' => 'Jumlah Aktif', 'value' => SickasFormatter::number($population['active']).' ekor', 'hint' => 'Ternak aktif dalam batch', 'tone' => 'success', 'mobile' => true],
        ['label' => 'Mati / Afkir', 'value' => SickasFormatter::number($population['dead'] + $population['culled']).' ekor', 'hint' => 'Mati '.SickasFormatter::number($population['dead']).' | Afkir '.SickasFormatter::number($population['culled']), 'tone' => 'danger', 'mobile' => true],
        ['label' => 'Terjual', 'value' => SickasFormatter::number($population['sold']).' ekor', 'hint' => 'Akumulasi penjualan batch', 'tone' => 'purple', 'mobile' => true],
        ['label' => 'Jumlah Saat Ini', 'value' => SickasFormatter::number($population['current']).' ekor', 'hint' => 'Stok batch tercatat', 'tone' => 'success'],
        ['label' => 'Berat Awal Total', 'value' => SickasFormatter::kg($weight['initial_total']), 'hint' => 'Rata-rata '.SickasFormatter::kg($weight['initial_average']), 'tone' => 'info'],
        ['label' => 'Berat Terakhir Batch', 'value' => SickasFormatter::kg($weight['latest_batch_total']), 'hint' => 'Dari timbang batch aktual', 'tone' => 'success', 'mobile' => true],
        ['label' => 'Berat Per Ekor Aktual', 'value' => SickasFormatter::kg($weight['latest_individual_total']), 'hint' => 'Ringkasan timbang per ekor', 'tone' => 'success'],
        ['label' => 'Total Kenaikan Berat', 'value' => SickasFormatter::kg($weight['weight_gain']), 'hint' => 'Kenaikan dari berat awal batch', 'tone' => ($weight['weight_gain'] ?? 0) < 0 ? 'danger' : 'success'],
        ['label' => 'ADG Rata-rata', 'value' => SickasFormatter::adg($weight['adg']), 'hint' => 'Status '.$weight['status'], 'tone' => $weight['status_color'], 'mobile' => true],
        ['label' => 'Modal Pembelian', 'value' => SickasFormatter::rupiah($finance['purchase_capital']), 'hint' => 'Termasuk modal batch tercatat', 'tone' => 'orange'],
        ['label' => 'Biaya Pembelian', 'value' => SickasFormatter::rupiah($finance['transport_cost'] + $finance['other_cost']), 'hint' => 'Transport dan biaya lain pembelian', 'tone' => 'warning'],
        ['label' => 'Pengeluaran', 'value' => SickasFormatter::rupiah($finance['expenses']), 'hint' => 'Biaya penggemukan tercatat', 'tone' => 'orange'],
        ['label' => 'Total Penjualan', 'value' => SickasFormatter::rupiah($finance['sales']), 'hint' => 'Nilai penjualan batch', 'tone' => 'success'],
        ['label' => 'Laba / Rugi', 'value' => SickasFormatter::rupiah($finance['profit']), 'hint' => 'Penjualan - modal - pengeluaran', 'tone' => $finance['profit'] >= 0 ? 'success' : 'danger', 'mobile' => true],
        ['label' => 'Estimasi Nilai Jual', 'value' => SickasFormatter::rupiah($finance['estimated_market_value']), 'hint' => 'Berdasarkan harga pasaran terbaru', 'tone' => 'success', 'mobile' => true],
        ['label' => 'Estimasi L/R Hari Ini', 'value' => SickasFormatter::rupiah($finance['estimated_profit_loss_today']), 'hint' => 'Penjualan lama + estimasi jual - modal - biaya', 'tone' => $finance['estimated_profit_loss_today'] >= 0 ? 'success' : 'danger', 'mobile' => true],
    ];

    $secondaryMobileCards = collect($summaryCards)->filter(fn (array $card): bool => ! ($card['mobile'] ?? false));

    $photoUrl = function (array|string|null $paths): ?string {
        $path = is_array($paths) ? ($paths[0] ?? null) : $paths;

        return filled($path) ? Storage::disk('public')->url($path) : null;
    };
@endphp

<div class="sickas-batch-detail w-full max-w-none space-y-6">
    <section class="sickas-batch-hero-card overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950">
        <div class="sickas-batch-hero-inner relative bg-gradient-to-br from-emerald-700 via-slate-900 to-gray-950 p-5 text-white sm:p-6">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_20%,rgba(245,158,11,.28),transparent_28%),linear-gradient(135deg,rgba(255,255,255,.08)_0,rgba(255,255,255,0)_35%)]"></div>
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white">{{ $header['livestock_type'] }}</span>
                        <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white">{{ $header['pen'] }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-amber-300">Pusat Operasional Batch</p>
                        <h2 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ $header['batch_code'] }}</h2>
                        <p class="mt-2 max-w-3xl text-sm text-white/80">
                            Supplier {{ $header['supplier'] }} | Mulai {{ SickasFormatter::date($header['purchase_date']) }} | Populasi saat ini {{ SickasFormatter::number($population['current']) }} ekor
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses[$header['status_color']] ?? $badgeClasses['gray'] }}">{{ $header['status'] }}</span>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses[$header['detail_color']] ?? $badgeClasses['gray'] }}">{{ $header['detail_status'] }}</span>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses[$header['data_quality_color']] ?? $badgeClasses['gray'] }}">{{ $header['data_quality'] }}</span>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses[$weight['weighing_alert_color']] ?? $badgeClasses['gray'] }}">{{ $weight['weighing_alert'] }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="sickas-batch-summary-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article class="sickas-batch-stat sickas-batch-tone-{{ $card['tone'] }} {{ ($card['mobile'] ?? false) ? '' : 'hidden sm:block' }} rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-gray-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                        <p class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
                    </div>
                    <span class="h-3 w-3 shrink-0 rounded-full {{ match ($card['tone']) {
                        'success' => 'bg-emerald-500',
                        'info' => 'bg-sky-500',
                        'warning' => 'bg-amber-500',
                        'danger' => 'bg-rose-500',
                        'orange' => 'bg-orange-500',
                        'purple' => 'bg-purple-500',
                        default => 'bg-gray-400',
                    } }}"></span>
                </div>
            </article>
        @endforeach
    </section>

    <details class="sickas-mobile-accordion sm:hidden">
        <summary>
            <span>Ringkasan lengkap batch</span>
            <strong>{{ $secondaryMobileCards->count() }} item</strong>
        </summary>
        <div class="sickas-mobile-accordion-content">
            <div class="grid grid-cols-2 gap-2">
                @foreach ($secondaryMobileCards as $card)
                    <article class="sickas-batch-stat sickas-batch-tone-{{ $card['tone'] }} rounded-xl border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-950">
                        <p class="text-[0.6rem] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                        <p class="mt-1 text-sm font-bold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                        <p class="mt-1 text-[0.66rem] text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </details>

    <section class="grid gap-4 xl:grid-cols-[1fr_.85fr]">
        <div class="sickas-batch-panel sickas-batch-actions rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-bold text-gray-950 dark:text-white">Aksi Cepat Batch</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pilih aksi operasional harian yang paling sering dipakai.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        @if ($action['new_tab'] ?? false) target="_blank" rel="noreferrer" @endif
                        class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-bold shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:ring-offset-gray-950 {{ $buttonClasses[$action['tone']] ?? $buttonClasses['gray'] }}"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="sickas-batch-panel sickas-batch-warnings rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-950">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">Peringatan Batch</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hal yang perlu dicek sebelum operasional lanjut.</p>
                </div>
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-bold text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">{{ $warnings->count() }} item</span>
            </div>
            <div class="hidden lg:block">
                @if ($warnings->isEmpty())
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                        Tidak ada peringatan penting untuk batch ini.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($warnings as $warning)
                            <div class="rounded-xl border p-3 {{ $badgeClasses[$warning['tone']] ?? $badgeClasses['gray'] }}">
                                <p class="text-sm font-bold">{{ $warning['label'] }}</p>
                                <p class="mt-1 text-xs opacity-80">{{ $warning['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <details class="sickas-mobile-accordion lg:hidden" {{ $warnings->isNotEmpty() ? 'open' : '' }}>
                <summary>
                    <span>{{ $warnings->isEmpty() ? 'Tidak ada peringatan' : 'Lihat peringatan batch' }}</span>
                    <strong>{{ $warnings->count() }}</strong>
                </summary>
                <div class="sickas-mobile-accordion-content">
                    @if ($warnings->isEmpty())
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            Tidak ada peringatan. Batch dalam kondisi baik.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($warnings as $warning)
                                <div class="rounded-xl border p-3 {{ $badgeClasses[$warning['tone']] ?? $badgeClasses['gray'] }}">
                                    <p class="text-xs font-bold">{{ $warning['label'] }}</p>
                                    <p class="mt-1 text-xs opacity-80">{{ $warning['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </details>
        </div>
    </section>

    <section class="sickas-batch-panel sickas-batch-table-panel rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950">
        <div class="flex flex-col gap-2 border-b border-gray-200 p-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-950 dark:text-white">Data Ternak Dalam Batch</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Detail per ekor untuk melengkapi foto, berat, harga beli, dan status pertumbuhan.</p>
            </div>
            <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-bold text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">{{ $sheepRows->count() }} ekor</span>
        </div>

        @if ($sheepRows->isEmpty())
            <div class="p-6">
                <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-white/10 dark:bg-white/5">
                    <p class="text-base font-bold text-gray-950 dark:text-white">Batch ini belum memiliki data ternak per ekor.</p>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">Tambahkan data ternak agar pertumbuhan, foto, dan status per ekor bisa dipantau lebih akurat.</p>
                    <a href="{{ \App\Filament\Resources\Sheep\SheepResource::getUrl('index') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-500">
                        Buat Data Ternak dari Batch
                    </a>
                </div>
            </div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="sickas-batch-table min-w-[1180px] w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">Ternak</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">Jenis</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">Berat Awal</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">Berat Terakhir</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">Kenaikan</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">ADG</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">Harga Beli</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">Estimasi Jual</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">Kelengkapan</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($sheepRows as $row)
                            @php $image = $photoUrl($row['photo_paths']); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($image)
                                            <img src="{{ $image }}" alt="{{ $row['tag_number'] }}" class="h-11 w-11 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-white/10">
                                        @else
                                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-xs font-bold text-gray-500 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">No Foto</div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-gray-950 dark:text-white">{{ $row['tag_number'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['photo_status'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['livestock_type'] }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-100">{{ SickasFormatter::kg($row['initial_weight']) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-100">{{ SickasFormatter::kg($row['latest_weight']) }}</td>
                                <td class="px-4 py-3 text-right font-medium {{ ($row['weight_gain'] ?? 0) < 0 ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-600 dark:text-emerald-300' }}">{{ SickasFormatter::kg($row['weight_gain']) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-100">{{ SickasFormatter::adg($row['adg']) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-100">{{ SickasFormatter::rupiah($row['purchase_price']) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-emerald-700 dark:text-emerald-300">{{ SickasFormatter::rupiah($row['estimated_market_value']) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['status_color']] ?? $badgeClasses['gray'] }}">{{ $row['status'] }}</span>
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['growth_color']] ?? $badgeClasses['gray'] }}">{{ $row['growth_status'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['data_color']] ?? $badgeClasses['gray'] }}">{{ $row['data_status'] }}</span>
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['weighing_color']] ?? $badgeClasses['gray'] }}">{{ $row['weighing_status'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ $row['detail_url'] }}" class="inline-flex rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <details class="sickas-mobile-accordion sickas-livestock-accordion lg:hidden">
                <summary>
                    <span>Lihat data ternak per ekor</span>
                    <strong>{{ $sheepRows->count() }} ekor</strong>
                </summary>
                <div class="sickas-mobile-accordion-content space-y-2">
                    @foreach ($sheepRows as $row)
                        @php $image = $photoUrl($row['photo_paths']); @endphp
                        <article class="sickas-batch-mobile-card rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex gap-3">
                                @if ($image)
                                    <img src="{{ $image }}" alt="{{ $row['tag_number'] }}" class="h-14 w-14 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-white/10">
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white text-xs font-bold text-gray-500 ring-1 ring-gray-200 dark:bg-gray-950 dark:text-gray-400 dark:ring-white/10">No Foto</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="font-bold text-gray-950 dark:text-white">{{ $row['tag_number'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ SickasFormatter::kg($row['initial_weight']) }} -> {{ SickasFormatter::kg($row['latest_weight']) }}</p>
                                        </div>
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['status_color']] ?? $badgeClasses['gray'] }}">{{ $row['status'] }}</span>
                                    </div>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                                        <div><span class="text-gray-500 dark:text-gray-400">Naik</span><p class="font-bold text-gray-900 dark:text-white">{{ SickasFormatter::kg($row['weight_gain']) }}</p></div>
                                        <div><span class="text-gray-500 dark:text-gray-400">ADG</span><p class="font-bold text-gray-900 dark:text-white">{{ SickasFormatter::adg($row['adg']) }}</p></div>
                                        <div><span class="text-gray-500 dark:text-gray-400">Harga</span><p class="font-bold text-gray-900 dark:text-white">{{ SickasFormatter::rupiah($row['purchase_price']) }}</p></div>
                                        <div><span class="text-gray-500 dark:text-gray-400">Estimasi</span><p class="font-bold text-emerald-700 dark:text-emerald-300">{{ SickasFormatter::rupiah($row['estimated_market_value']) }}</p></div>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['growth_color']] ?? $badgeClasses['gray'] }}">{{ $row['growth_status'] }}</span>
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['data_color']] ?? $badgeClasses['gray'] }}">{{ $row['data_status'] }}</span>
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClasses[$row['weighing_color']] ?? $badgeClasses['gray'] }}">{{ $row['weighing_status'] }}</span>
                                    </div>
                                    <a href="{{ $row['detail_url'] }}" class="mt-2 inline-flex w-full justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 dark:border-white/10 dark:bg-gray-950 dark:text-gray-200">Detail</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </details>
        @endif
    </section>
</div>
