<div class="space-y-4">
    @if ($sale->saleItems->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            Penjualan ini belum memiliki detail ternak per ekor. Data masih tercatat sebagai penjualan {{ match ($sale->sale_type) {
                'per_head' => 'per ekor',
                'per_kg' => 'per kg',
                default => 'borongan',
            } }} sebanyak {{ \App\Support\SickasFormatter::number($sale->head_count) }} ekor.
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Kode Ternak</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Jenis</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Batch</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Bobot</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Harga</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                    @foreach ($sale->saleItems as $item)
                        <tr>
                            <td class="px-3 py-2 font-semibold text-gray-950 dark:text-white">
                                {{ $item->sheep?->tag_number ?? '-' }}
                                @if ($item->notes)
                                    <div class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $item->sheep?->livestockType?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $item->sheep?->fatteningBatch?->batch_code ?? '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\SickasFormatter::kg($item->weight_kg) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-950 dark:text-white">{{ \App\Support\SickasFormatter::rupiah($item->price) }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-200">
                                    {{ match ($item->sheep?->status) {
                                        'sold' => 'Terjual',
                                        'dead' => 'Mati',
                                        'lost' => 'Hilang',
                                        'culled' => 'Afkir',
                                        'sick' => 'Sakit',
                                        'active' => 'Aktif',
                                        default => '-',
                                    } }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
