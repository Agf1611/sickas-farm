<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class SickasFormatter
{
    public static function number(float|int|string|null $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, $decimals, ',', '.');
    }

    public static function rupiah(float|int|string|null $value): string
    {
        return 'Rp '.self::number($value);
    }

    public static function kg(float|int|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return self::number($value, $decimals).' kg';
    }

    public static function adg(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return self::number($value, 3).' kg/hari';
    }

    public static function date(CarbonInterface|string|null $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }

    public static function dateTime(CarbonInterface|string|null $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y H:i');
    }
}
