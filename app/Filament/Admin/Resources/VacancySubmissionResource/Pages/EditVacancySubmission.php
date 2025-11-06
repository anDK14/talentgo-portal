<?php

namespace App\Filament\Admin\Resources\VacancySubmissionResource\Pages;

use App\Filament\Admin\Resources\VacancySubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacancySubmission extends EditRecord
{
    protected static string $resource = VacancySubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
