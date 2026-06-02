@auth
    @php
        $items = [
            [
                'label' => 'Dasbor',
                'url' => url('/admin'),
                'active' => request()->is('admin'),
                'icon' => 'home',
            ],
            [
                'label' => 'Beli',
                'url' => \App\Filament\Resources\SheepPurchases\SheepPurchaseResource::getUrl('index'),
                'active' => request()->is('admin/sheep-purchases*'),
                'icon' => 'cart',
            ],
            [
                'label' => 'Timbang',
                'url' => \App\Filament\Pages\IndividualWeighing::getUrl(),
                'active' => request()->is('admin/individual-weighing*') || request()->is('admin/weight-records*'),
                'icon' => 'scale',
            ],
            [
                'label' => 'Jual',
                'url' => \App\Filament\Resources\Sales\SaleResource::getUrl('index'),
                'active' => request()->is('admin/sales*'),
                'icon' => 'receipt',
            ],
            [
                'label' => 'Batch',
                'url' => \App\Filament\Resources\SheepBatches\SheepBatchResource::getUrl('index'),
                'active' => request()->is('admin/sheep-batches*'),
                'icon' => 'grid',
            ],
        ];
    @endphp

    <nav class="sickas-mobile-bottom-nav" aria-label="Navigasi operasional cepat">
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}" class="sickas-mobile-nav-item {{ $item['active'] ? 'is-active' : '' }}">
                @switch($item['icon'])
                    @case('cart')
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 11h9.5l2-7H8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 20h.01M17 20h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                        @break
                    @case('scale')
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16M7 7h10M6 7l-3 6h6L6 7Zm12 0-3 6h6l-3-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @break
                    @case('receipt')
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10v18l-2-1.2-2 1.2-2-1.2-2 1.2-2-1.2V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @break
                    @case('grid')
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        @break
                    @default
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 11 8-7 8 7v9h-5v-6H9v6H4v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                @endswitch
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endauth
