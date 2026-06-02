<x-filament-widgets::widget>
    @php
        $stats = $this->stats();
    @endphp

    <div class="sickas-stat-grid">
        @foreach ($stats as $stat)
            <article class="sickas-stat-card sickas-tone-{{ $stat['tone'] }}">
                <div class="sickas-stat-top">
                    <div>
                        <p class="sickas-stat-label">{{ $stat['label'] }}</p>
                        <p class="sickas-stat-value">{{ $stat['value'] }}</p>
                    </div>
                    <div class="sickas-stat-icon">
                        @switch($stat['icon'])
                            @case('layers')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z" stroke="currentColor" stroke-width="1.8"/><path d="m4 12 8 4.5 8-4.5M4 16.5 12 21l8-4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('warning')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4 3 20h18L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('cart')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 11h9.5l2-7H8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 20h.01M17 20h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                                @break
                            @case('money')
                            @case('cash')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v10H4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM7 10h.01M17 14h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('receipt')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10v18l-2-1.2-2 1.2-2-1.2-2 1.2-2-1.2V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('trend-up')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 16 5-5 4 4 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 8h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('trend-down')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 8 5 5 4-4 7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 16h5v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @default
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @endswitch
                    </div>
                </div>
                <p class="sickas-stat-desc">{{ $stat['description'] }}</p>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
