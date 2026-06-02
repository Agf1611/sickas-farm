<?php

namespace App\Filament\Resources\BusinessProfiles\Pages;

use App\Filament\Resources\BusinessProfiles\BusinessProfileResource;
use App\Models\BusinessProfile;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBusinessProfile extends ManageRecords
{
    protected static string $resource = BusinessProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Profil Usaha')
                ->visible(fn (): bool => BusinessProfile::query()->doesntExist()),
        ];
    }
}
