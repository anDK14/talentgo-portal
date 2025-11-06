<?php

namespace App\Filament\Admin\Widgets;

use App\Models\VacancySubmission;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentSubmissionsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Pengajuan Kandidat Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                VacancySubmission::with(['vacancy.client', 'candidate', 'status'])
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nama Kandidat')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->candidate->unique_talent_id),

                Tables\Columns\TextColumn::make('vacancy.position_name')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->vacancy->level),

                Tables\Columns\TextColumn::make('vacancy.client.company_name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'client_interested' => 'success',
                        'client_rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'submitted' => 'Diajukan',
                        'client_interested' => 'Tertarik',
                        'client_rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at->format('d M Y H:i')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Lihat Detail')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('edit', ['record' => $record->id]))
                        ->icon('heroicon-o-eye')
                        ->color('primary'),
                        
                    Tables\Actions\Action::make('quickApprove')
                        ->label('Tandai Tertarik')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 2, // client_interested
                                'client_feedback' => 'Ditandai tertarik melalui dashboard',
                            ]);
                        })
                        ->visible(fn ($record) => $record->submission_status_id === 1),
                        
                    Tables\Actions\Action::make('quickReject')
                        ->label('Tandai Ditolak')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 3, // client_rejected
                                'client_feedback' => 'Ditandai ditolak melalui dashboard',
                            ]);
                        })
                        ->visible(fn ($record) => $record->submission_status_id === 1),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('Lihat Semua Pengajuan')
                    ->url(\App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('index'))
                    ->icon('heroicon-o-arrow-right')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateDescription('Setelah kandidat diajukan ke lowongan, mereka akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Buat pengajuan pertama')
                    ->url(\App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->deferLoading()
            ->striped();
    }

    public static function canView(): bool
    {
        return auth()->user()->role->role_name === 'admin';
    }
}