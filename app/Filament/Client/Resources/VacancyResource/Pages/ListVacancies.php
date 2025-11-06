<?php

namespace App\Filament\Client\Resources\VacancyResource\Pages;

use App\Filament\Client\Resources\VacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVacancies extends ListRecords
{
    protected static string $resource = VacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
