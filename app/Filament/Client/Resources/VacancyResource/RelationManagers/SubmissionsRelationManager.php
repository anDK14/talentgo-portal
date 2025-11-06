<?php

namespace App\Filament\Client\Resources\VacancyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Kandidat yang Diajukan';

    protected static ?string $modelLabel = 'Kandidat';

    protected static ?string $pluralModelLabel = 'Kandidat';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('candidate_id')
                    ->relationship('candidate', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('submission_status_id')
                    ->relationship('status', 'status_name')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('candidate.unique_talent_id')
                    ->label('Talent ID')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('semibold'),
                
                Tables\Columns\TextColumn::make('candidate.experience_summary')
                    ->label('Ringkasan Pengalaman')
                    ->limit(80)
                    ->tooltip(function ($record) {
                        return $record->candidate->experience_summary;
                    })
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('candidate.skills_summary')
                    ->label('Keahlian')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->candidate->skills_summary;
                    })
                    ->wrap(),
                
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
                        'submitted' => 'Menunggu Review',
                        'client_interested' => 'Tertarik',
                        'client_rejected' => 'Tidak Sesuai',
                        default => $state,
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'status_name')
                    ->label('Filter Berdasarkan Status')
                    ->preload(),
            ])
            ->headerActions([]) // Client tidak bisa create submission
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('viewBlindCV')
                        ->label('Lihat Blind CV')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(function ($record) {
                            return 'Blind CV - ' . ($record->candidate->unique_talent_id ?? 'Talent ID Tidak Diketahui');
                        })
                        ->modalContent(function ($record) {
                            $blindCV = $record->candidate->blind_cv;
                            
                            return view('filament.client.components.blind-cv', [
                                'blindCV' => $blindCV,
                                'submission' => $record,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->disabled(fn ($record) => $record->submission_status_id !== 1),

                    Tables\Actions\Action::make('markInterested')
                        ->label('Tertarik, Jadwalkan Wawancara')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Feedback')
                        ->modalDescription('Apakah Anda tertarik dengan kandidat ini dan ingin menjadwalkan wawancara?')
                        ->modalSubmitActionLabel('Ya, Tertarik')
                        ->modalCancelActionLabel('Batal')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 2, // client_interested
                                'client_feedback' => 'Klien menyatakan tertarik dan meminta jadwalkan wawancara.',
                            ]);
                            
                            // Trigger event for notification
                            \App\Events\ClientFeedbackGiven::dispatch($record);
                        })
                        ->visible(fn ($record) => $record->submission_status_id === 1),

                    Tables\Actions\Action::make('markRejected')
                        ->label('Tidak Sesuai')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Feedback')
                        ->modalDescription('Apakah kandidat ini tidak sesuai dengan kebutuhan Anda?')
                        ->modalSubmitActionLabel('Ya, Tidak Sesuai')
                        ->modalCancelActionLabel('Batal')
                        ->action(function ($record) {
                            $record->update([
                                'submission_status_id' => 3, // client_rejected
                                'client_feedback' => 'Klien menyatakan kandidat tidak sesuai dengan kebutuhan.',
                            ]);
                            
                            // Trigger event for notification
                            \App\Events\ClientFeedbackGiven::dispatch($record);
                        })
                        ->visible(fn ($record) => $record->submission_status_id === 1),
                ])
                ->label('Aksi')
                ->button()
                ->color('primary')
            ])
            ->bulkActions([]) // Client tidak bisa bulk actions
            ->emptyStateHeading('Belum ada kandidat yang diajukan')
            ->emptyStateDescription('Tim TalentGO akan segera mengajukan kandidat yang sesuai untuk lowongan ini.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Tables\Actions\Action::make('contactSupport')
                    ->label('Hubungi TalentGO')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->url('mailto:support@talentgo.com?subject=Permintaan Kandidat untuk Lowongan')
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['candidate', 'status']);
    }
}