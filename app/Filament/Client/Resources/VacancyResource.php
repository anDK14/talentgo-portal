<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\VacancyResource\Pages;
use App\Filament\Client\Resources\VacancyResource\RelationManagers;
use App\Models\Vacancy;
use App\Models\VacancyStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VacancyResource extends Resource
{
    protected static ?string $model = Vacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Lowongan';

    protected static ?string $modelLabel = 'Lowongan';

    protected static ?string $pluralModelLabel = 'Daftar Lowongan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Posisi')
                    ->schema([
                        Forms\Components\TextInput::make('position_name')
                            ->label('Nama Posisi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Senior Backend Developer')
                            ->helperText('Masukkan nama posisi yang dicari'),
                        Forms\Components\Select::make('level')
                            ->label('Level')
                            ->options([
                                'Junior' => 'Junior',
                                'Middle' => 'Middle', 
                                'Senior' => 'Senior',
                                'Lead' => 'Lead',
                                'Principal' => 'Principal',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Pilih level experience yang dibutuhkan'),
                    ])->columns(2),

                Forms\Components\Section::make('Deskripsi Pekerjaan & Requirements')
                    ->schema([
                        Forms\Components\Textarea::make('job_description')
                            ->label('Deskripsi Pekerjaan')
                            ->rows(4)
                            ->required()
                            ->placeholder('Jelaskan tanggung jawab, tugas utama, dan deskripsi pekerjaan...')
                            ->helperText('Deskripsikan secara detail tentang peran dan tanggung jawab posisi ini')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('required_skills')
                            ->label('Keahlian yang Dibutuhkan')
                            ->rows(3)
                            ->required()
                            ->placeholder('Contoh: Laravel, PostgreSQL, AWS, REST API, JavaScript')
                            ->helperText('Pisahkan setiap skill dengan koma. Tim TalentGO akan mencari kandidat dengan skills ini.')
                            ->columnSpanFull(),
                    ]),

                // Hidden fields untuk auto-set values
                Forms\Components\Hidden::make('client_id')
                    ->default(Auth::user()->client_id),
                Forms\Components\Hidden::make('status_id')
                    ->default(1), // Default status: Open
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // Nonaktifkan klik pada seluruh baris
            ->recordAction(null) // Nonaktifkan aksi default
            ->columns([
                Tables\Columns\TextColumn::make('position_name')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom posisi
                    ->description(fn ($record) => $record->level)
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Status')
                    ->badge()
                    ->url(null) // Nonaktifkan link di kolom status
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'success',
                        'On-Process' => 'warning', 
                        'Closed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Open' => 'Dibuka',
                        'On-Process' => 'Proses Rekrutmen',
                        'Closed' => 'Ditutup',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label('Kandidat')
                    ->badge()
                    ->url(null) // Nonaktifkan link di kolom kandidat
                    ->color(function ($state) {
                        if ($state == 0) return 'gray';
                        if ($state <= 3) return 'info';
                        if ($state <= 6) return 'warning';
                        return 'success';
                    })
                    ->formatStateUsing(fn ($state) => "{$state} kandidat"),
                Tables\Columns\TextColumn::make('pending_feedback_count')
                    ->label('Menunggu Review')
                    ->getStateUsing(function ($record) {
                        return $record->submissions()
                            ->where('submission_status_id', 1) // submitted status
                            ->count();
                    })
                    ->badge()
                    ->url(null) // Nonaktifkan link di kolom menunggu review
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} menunggu" : 'Sudah direview'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom dibuat
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom diupdate
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'status_name')
                    ->label('Status Lowongan')
                    ->preload(),
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'Junior' => 'Junior',
                        'Middle' => 'Middle',
                        'Senior' => 'Senior', 
                        'Lead' => 'Lead',
                        'Principal' => 'Principal',
                    ])
                    ->label('Level Posisi')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('viewCandidates')
                        ->label('Lihat Kandidat')
                        ->icon('heroicon-o-user-group')
                        ->url(fn ($record) => VacancyResource::getUrl('view', ['record' => $record]))
                        ->color('info'),
                    Tables\Actions\Action::make('closeVacancy')
                        ->label('Tutup Lowongan')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tutup Lowongan')
                        ->modalDescription('Apakah Anda yakin ingin menutup lowongan ini? Lowongan yang ditutup tidak akan menerima kandidat baru.')
                        ->action(function ($record) {
                            $record->update(['status_id' => 3]); // Closed status
                        })
                        ->visible(fn ($record) => $record->status_id !== 3),
                    Tables\Actions\Action::make('reopenVacancy')
                        ->label('Buka Kembali')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Buka Kembali Lowongan')
                        ->modalDescription('Apakah Anda yakin ingin membuka kembali lowongan ini?')
                        ->action(function ($record) {
                            $record->update(['status_id' => 1]); // Open status
                        })
                        ->visible(fn ($record) => $record->status_id === 3),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Lowongan Terpilih')
                        ->icon('heroicon-o-trash'),
                    Tables\Actions\BulkAction::make('closeSelected')
                        ->label('Tutup Lowongan Terpilih')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['status_id' => 3]);
                        }),
                    Tables\Actions\BulkAction::make('openSelected')
                        ->label('Buka Lowongan Terpilih')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['status_id' => 1]);
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada lowongan')
            ->emptyStateDescription('Buat lowongan pertama Anda untuk mulai mencari kandidat talent terbaik.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Lowongan Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacancies::route('/'),
            'create' => Pages\CreateVacancy::route('/create'),
            'view' => Pages\ViewVacancy::route('/{record}'),
            'edit' => Pages\EditVacancy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('client_id', Auth::user()->client_id)
            ->withCount(['submissions']);
    }
}