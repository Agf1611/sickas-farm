<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BusinessProfile extends Model
{
    protected $fillable = [
        'app_name',
        'business_name',
        'bumdes_name',
        'unit_name',
        'logo_path',
        'banner_path',
        'address',
        'village',
        'district',
        'regency',
        'province',
        'postal_code',
        'phone',
        'email',
        'legal_number',
        'director_name',
        'unit_head_name',
        'report_footer',
        'default_currency',
        'default_weight_unit',
        'default_quantity_unit',
    ];

    public static function main(): ?self
    {
        return self::query()->oldest('id')->first();
    }

    /**
     * @return array<string, string|null>
     */
    public static function reportIdentity(): array
    {
        $profile = self::main();

        return [
            'app_name' => $profile?->app_name ?: 'SICKAS FARM',
            'business_title' => $profile?->business_name ?: 'SICKAS FARM',
            'bumdes_name' => $profile?->bumdes_name ?: 'BUMDes Ketapang Ternak Domba',
            'unit_name' => $profile?->unit_name ?: 'BUMDes Ketapang Ternak Domba',
            'address' => $profile?->fullAddress(),
            'phone' => $profile?->phone,
            'email' => $profile?->email,
            'legal_number' => $profile?->legal_number,
            'director_name' => $profile?->director_name,
            'unit_head_name' => $profile?->unit_head_name,
            'report_footer' => $profile?->report_footer,
            'logo_path' => $profile?->logoPathForPdf(),
            'banner_url' => $profile?->bannerUrl(),
        ];
    }

    public function fullAddress(): ?string
    {
        $parts = collect([
            $this->address,
            $this->village,
            $this->district,
            $this->regency,
            $this->province,
            $this->postal_code,
        ])->filter(fn (?string $value): bool => filled($value));

        return $parts->isEmpty() ? null : $parts->implode(', ');
    }

    public function logoPathForPdf(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $path = Storage::disk('public')->path($this->logo_path);

        return is_file($path) ? $path : null;
    }

    public function logoUrl(): ?string
    {
        return $this->publicStorageUrl($this->logo_path);
    }

    public function bannerUrl(): ?string
    {
        return $this->publicStorageUrl($this->banner_path);
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! $path || (! Storage::disk('public')->exists($path))) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
