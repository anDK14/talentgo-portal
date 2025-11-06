<?php

namespace App\Filament\Admin\Resources\VacancyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

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
            ->recordUrl(null) // Nonaktifkan klik pada seluruh baris
            ->recordAction(null) // Nonaktifkan aksi default
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nama Kandidat')
                    ->searchable()
                    ->sortable()
                    ->url(null), // Nonaktifkan link di kolom nama kandidat
                Tables\Columns\TextColumn::make('candidate.unique_talent_id')
                    ->label('ID Talent')
                    ->searchable()
                    ->sortable()
                    ->url(null), // Nonaktifkan link di kolom talent ID
                Tables\Columns\TextColumn::make('candidate.experience_summary')
                    ->label('Pengalaman')
                    ->limit(50)
                    ->url(null) // Nonaktifkan link di kolom experience
                    ->tooltip(function ($record) {
                        return $record->candidate->experience_summary;
                    }),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->url(null), // Nonaktifkan link di kolom submitted at
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Ubah')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('viewCandidate')
                        ->label('Lihat Kandidat')
                        ->icon('heroicon-o-user')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\CandidateResource::getUrl('edit', ['record' => $record->candidate_id]))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('viewBlindCV')
                        ->label('Lihat CV Blind')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(fn ($record) => 'CV Blind - ' . ($record->candidate->unique_talent_id ?? 'Tidak Diketahui'))
                        ->modalContent(function ($record) {
                            $blindCV = $record->candidate->blind_cv;
                            
                            return view('filament.client.components.blind-cv', [
                                'blindCV' => $blindCV,
                                'submission' => $record,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash'),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateDescription('Setelah Anda mengajukan kandidat untuk lowongan ini, mereka akan muncul di sini.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajukan kandidat pertama')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}