<?php

namespace App\Filament\Admin\Resources\VacancySubmissionResource\Pages;

use App\Filament\Admin\Resources\VacancySubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVacancySubmissions extends ListRecords
{
    protected static string $resource = VacancySubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
