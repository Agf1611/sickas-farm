<x-filament-panels::page.simple>
    @php
        $profile = $this->profile();
    @endphp

    <div class="sickas-login-shell" style="--sickas-login-bg: url('{{ $profile['banner_url'] }}');">
        <section class="sickas-login-visual">
            <div class="sickas-login-visual-overlay"></div>
            <div class="sickas-login-visual-content">
                <div class="sickas-login-brand">
                    <div class="sickas-login-logo">
                        @if ($profile['logo_url'])
                            <img src="{{ $profile['logo_url'] }}" alt="{{ $profile['app_name'] }}">
                        @else
                            <span>SF</span>
                        @endif
                    </div>
                    <div>
                        <p>{{ $profile['app_name'] }}</p>
                        <h1>{{ $profile['business_name'] }}</h1>
                    </div>
                </div>

                <div class="sickas-login-copy">
                    <span>{{ $profile['bumdes_name'] }}</span>
                    <h2>Kelola usaha ternak dengan data yang rapi.</h2>
                    <p>
                        Pantau pembelian, batch penggemukan, timbang berkala, pengeluaran,
                        penjualan, dan laporan performa dalam satu panel admin.
                    </p>
                </div>

                <div class="sickas-login-info">
                    <div>
                        <strong>{{ $profile['unit_name'] }}</strong>
                        <small>{{ $profile['address'] ?: 'Sistem informasi unit ternak BUMDes' }}</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="sickas-login-form-panel">
            <div class="sickas-login-mobile-brand">
                <div class="sickas-login-logo">
                    @if ($profile['logo_url'])
                        <img src="{{ $profile['logo_url'] }}" alt="{{ $profile['app_name'] }}">
                    @else
                        <span>SF</span>
                    @endif
                </div>
                <div>
                    <p>{{ $profile['app_name'] }}</p>
                    <h1>{{ $profile['business_name'] }}</h1>
                </div>
            </div>

            <div class="sickas-login-form-heading">
                <p>Panel Admin</p>
                <h2>Masuk ke akun Anda</h2>
                <span>Gunakan akun yang sudah diberikan oleh pengelola sistem.</span>
            </div>

            <div class="sickas-login-form-card">
                {{ $this->content }}
            </div>
        </section>
    </div>
</x-filament-panels::page.simple>
