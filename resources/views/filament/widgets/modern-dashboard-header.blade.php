<x-filament-widgets::widget>
    @php
        $profile = $this->profile();
        $actions = $this->quickActions();
        $userName = auth()->user()?->name ?: 'Admin SICKAS';
    @endphp

    <div class="sickas-dashboard-head">
        <section class="sickas-hero-card" style="--sickas-hero-bg: url('{{ $profile['banner_url'] }}');">
            <div class="sickas-hero-glow"></div>
            <div class="sickas-hero-pattern"></div>

            <div class="sickas-hero-content">
                <div class="sickas-hero-main">
                    <div class="sickas-hero-logo">
                        @if ($profile['logo_url'])
                            <img src="{{ $profile['logo_url'] }}" alt="{{ $profile['app_name'] }}">
                        @else
                            <svg class="h-11 w-11 text-amber-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 10.5C5 6.9 7.9 4 11.5 4h1C16.1 4 19 6.9 19 10.5v2.7c0 3.4-2.6 6.3-6 6.7l-1 .1-1-.1c-3.4-.4-6-3.3-6-6.7v-2.7Z" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M9 11h.01M15 11h.01M10 15c1.2.8 2.8.8 4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        @endif
                    </div>

                    <div class="sickas-hero-copy">
                        <p class="sickas-hero-app">{{ $profile['app_name'] }}</p>
                        <h1>
                            Selamat Datang, {{ $userName }}
                        </h1>
                        <p class="sickas-hero-desc">
                            Kelola ternak dengan lebih mudah dan pantau perkembangan usaha hari ini.
                        </p>

                        <div class="sickas-hero-meta">
                            <p class="sickas-hero-unit">{{ $profile['unit_name'] }}</p>
                            @if ($profile['address'])
                                <p class="sickas-hero-address">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 21s7-5.3 7-11a7 7 0 1 0-14 0c0 5.7 7 11 7 11Z" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                    <span>{{ $profile['address'] }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="sickas-date-card">
                    <p>{{ $profile['date_label'] }}</p>
                    <span>{{ $profile['day_time_label'] }}</span>
                </div>
            </div>
        </section>

        <section class="sickas-quick-card">
            <div class="sickas-section-title">
                <h2>Quick Action</h2>
                <p>Akses cepat untuk input harian.</p>
            </div>

            @if ($actions)
                <div class="sickas-quick-grid">
                    @foreach ($actions as $action)
                        <a href="{{ $action['url'] }}" class="sickas-quick-button sickas-quick-{{ $action['tone'] }}">
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="sickas-empty-dark">
                    Tidak ada aksi cepat yang tersedia untuk role Anda.
                </div>
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
