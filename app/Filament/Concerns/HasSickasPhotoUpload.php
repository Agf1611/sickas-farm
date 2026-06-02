<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;

trait HasSickasPhotoUpload
{
    protected static function photoUpload(string $field, string $label, string $directory): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->multiple()
            ->disk('public')
            ->directory($directory)
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ])
            ->maxSize(2048)
            ->downloadable()
            ->openable()
            ->reorderable()
            ->deleteUploadedFileUsing(fn (...$arguments): null => null)
            ->helperText('Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB per foto.')
            ->columnSpanFull();
    }

    protected static function photoColumn(string $field, string $label): ImageColumn
    {
        return ImageColumn::make($field)
            ->label($label)
            ->disk('public')
            ->stacked()
            ->limit(3)
            ->limitedRemainingText()
            ->square()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
