<?php

namespace App\Filament\Client\Resources\VacancyResource\Pages;

use App\Filament\Client\Resources\VacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewVacancy extends ViewRecord
{
    protected static string $resource = VacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali ke Daftar Lowongan')
                ->url(VacancyResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Detail Lowongan')
                    ->schema([
                        Components\TextEntry::make('position_name')
                            ->label('Nama Posisi'),
                        Components\TextEntry::make('level')
                            ->label('Level'),
                        Components\TextEntry::make('status.status_name')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Open' => 'success',
                                'On-Process' => 'warning',
                                'Closed' => 'danger',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'Open' => 'Dibuka',
                                'On-Process' => 'Proses Rekrutmen',
                                'Closed' => 'Ditutup',
                                default => $state,
                            }),
                    ])->columns(3),

                Components\Section::make('Deskripsi Pekerjaan')
                    ->schema([
                        Components\TextEntry::make('job_description')
                            ->label('')
                            ->prose()
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Keahlian yang Dibutuhkan')
                    ->schema([
                        Components\TextEntry::make('required_skills')
                            ->label('')
                            ->prose()
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function hasDeleteAction(): bool
    {
        return false;
    }

    protected function hasEditAction(): bool
    {
        return false;
    }
}