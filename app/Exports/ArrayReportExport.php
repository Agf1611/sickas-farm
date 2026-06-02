<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ArrayReportExport implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function __construct(
        private readonly array $rows,
        private readonly string $title,
    ) {}

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return str($this->title)->limit(31, '')->toString();
    }
}
