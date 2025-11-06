<?php

namespace App\Filament\Admin\Resources\VacancyResource\Pages;

use App\Filament\Admin\Resources\VacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacancy extends EditRecord
{
    protected static string $resource = VacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
