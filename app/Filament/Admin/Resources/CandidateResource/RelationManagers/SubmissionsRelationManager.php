<?php

namespace App\Filament\Admin\Resources\CandidateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Riwayat Pengajuan';

    protected static ?string $modelLabel = 'Pengajuan';

    protected static ?string $pluralModelLabel = 'Pengajuan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pengajuan')
                    ->schema([
                        Forms\Components\Select::make('vacancy_id')
                            ->relationship('vacancy', 'position_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Lowongan')
                            ->helperText('Pilih lowongan pekerjaan'),
                        Forms\Components\Select::make('submission_status_id')
                            ->relationship('status', 'status_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Status')
                            ->default(1) // submitted
                            ->helperText('Atur status pengajuan'),
                        Forms\Components\Textarea::make('client_feedback')
                            ->label('Umpan Balik Klien')
                            ->rows(3)
                            ->placeholder('Masukkan umpan balik dari klien...')
                            ->helperText('Komentar atau umpan balik dari klien')
                            ->columnSpanFull(),
                    ]),
                
                // Hidden field for auto-assignment
                Forms\Components\Hidden::make('candidate_id')
                    ->default($this->getOwnerRecord()->id),
                Forms\Components\Hidden::make('submitted_by_user_id')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->recordUrl(null) // Nonaktifkan klik pada seluruh baris
            ->recordAction(null) // Nonaktifkan aksi default
            ->columns([
                Tables\Columns\TextColumn::make('vacancy.position_name')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom position
                    ->description(fn ($record) => $record->vacancy->level)
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('vacancy.client.company_name')
                    ->label('Perusahaan Klien')
                    ->searchable()
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom client
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Status')
                    ->badge()
                    ->url(null) // Nonaktifkan link di kolom status
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'client_interested' => 'success',
                        'client_rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'submitted' => 'Diajukan',
                        'client_interested' => 'Klien Tertarik',
                        'client_rejected' => 'Ditolak Klien',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_feedback')
                    ->label('Umpan Balik')
                    ->limit(50)
                    ->url(null) // Nonaktifkan link di kolom feedback
                    ->tooltip(function ($record) {
                        return $record->client_feedback;
                    })
                    ->placeholder('Belum ada umpan balik')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('submittedBy.email')
                    ->label('Diajukan Oleh')
                    ->url(null) // Nonaktifkan link di kolom submitted by
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom created at
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'status_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Status'),
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('vacancy.client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Klien'),
                Tables\Filters\Filter::make('recent_submissions')
                    ->label('30 Hari Terakhir')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajukan ke Lowongan')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ajukan Kandidat ke Lowongan')
                    ->modalDescription('Ajukan kandidat ini ke lowongan pekerjaan baru'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Ubah Pengajuan')
                        ->icon('heroicon-o-pencil')
                        ->modalHeading('Ubah Detail Pengajuan'),
                    Tables\Actions\Action::make('viewVacancy')
                        ->label('Lihat Lowongan')
                        ->icon('heroicon-o-briefcase')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancyResource::getUrl('edit', ['record' => $record->vacancy_id]))
                        ->color('info')
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('viewClient')
                        ->label('Lihat Klien')
                        ->icon('heroicon-o-building-office')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\ClientResource::getUrl('edit', ['record' => $record->vacancy->client_id]))
                        ->color('primary')
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !is_null($record->vacancy->client_id)),
                    Tables\Actions\Action::make('updateStatus')
                        ->label('Tandai Tertarik')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 2, // client_interested
                                'client_feedback' => 'Ditandai tertarik oleh admin.',
                            ]);
                        })
                        ->visible(fn ($record) => $record->submission_status_id === 1),
                    Tables\Actions\Action::make('rejectStatus')
                        ->label('Tandai Ditolak')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 3, // client_rejected
                                'client_feedback' => 'Ditandai ditolak oleh admin.',
                            ]);
                        })
                        ->visible(fn ($record) => $record->submission_status_id === 1),
                    Tables\Actions\Action::make('resetStatus')
                        ->label('Reset ke Diajukan')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 1, // submitted
                                'client_feedback' => null,
                            ]);
                        })
                        ->visible(fn ($record) => $record->submission_status_id !== 1),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pengajuan')
                        ->modalDescription('Apakah Anda yakin ingin menghapus pengajuan ini? Tindakan ini tidak dapat dibatalkan.'),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash'),
                    Tables\Actions\BulkAction::make('markInterested')
                        ->label('Tandai sebagai Tertarik')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update([
                                'submission_status_id' => 2,
                                'client_feedback' => 'Ditandai tertarik secara massal oleh admin.',
                            ]);
                        }),
                    Tables\Actions\BulkAction::make('markRejected')
                        ->label('Tandai sebagai Ditolak')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update([
                                'submission_status_id' => 3,
                                'client_feedback' => 'Ditandai ditolak secara massal oleh admin.',
                            ]);
                        }),
                    Tables\Actions\BulkAction::make('resetStatus')
                        ->label('Reset ke Diajukan')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            $records->each->update([
                                'submission_status_id' => 1,
                                'client_feedback' => null,
                            ]);
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateDescription('Kandidat ini belum diajukan ke lowongan manapun.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajukan ke Lowongan Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['vacancy.client', 'status', 'submittedBy']);
    }
}