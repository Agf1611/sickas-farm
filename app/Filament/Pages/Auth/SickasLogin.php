<?php

namespace App\Filament\Pages\Auth;

use App\Models\BusinessProfile;
use Filament\Auth\Pages\Login;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SickasLogin extends Login
{
    protected string $view = 'filament.pages.auth.sickas-login';

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string|null>
     */
    public function profile(): array
    {
        try {
            $profile = Schema::hasTable('business_profiles') ? BusinessProfile::main() : null;
        } catch (Throwable) {
            $profile = null;
        }

        return [
            'app_name' => $profile?->app_name ?: 'SICKAS FARM',
            'business_name' => $profile?->business_name ?: 'SICKAS FARM',
            'bumdes_name' => $profile?->bumdes_name ?: 'BUMDes Ketapang Ternak Domba',
            'unit_name' => $profile?->unit_name ?: 'Sistem Informasi Ternak',
            'address' => $profile?->fullAddress(),
            'logo_url' => $profile?->logoUrl(),
            'banner_url' => $profile?->bannerUrl() ?: url('/images/sickas-farm/default-farm-banner.svg'),
        ];
    }
}
