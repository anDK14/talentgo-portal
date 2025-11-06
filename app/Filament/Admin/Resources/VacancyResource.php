<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VacancyResource\Pages;
use App\Filament\Admin\Resources\VacancyResource\RelationManagers;
use App\Models\Vacancy;
use App\Models\Client;
use App\Models\VacancyStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacancyResource extends Resource
{
    protected static ?string $model = Vacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Lowongan';

    protected static ?string $modelLabel = 'Lowongan';

    protected static ?string $pluralModelLabel = 'Lowongan';

    protected static ?string $navigationGroup = 'Rekrutmen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Lowongan')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Perusahaan Klien')
                            ->helperText('Pilih perusahaan klien'),
                        Forms\Components\Select::make('status_id')
                            ->relationship('status', 'status_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Status')
                            ->default(1) // Open
                            ->helperText('Atur status lowongan'),
                        Forms\Components\TextInput::make('position_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Posisi')
                            ->placeholder('Contoh: Senior Backend Developer')
                            ->helperText('Masukkan judul posisi pekerjaan'),
                        Forms\Components\Select::make('level')
                            ->options([
                                'Junior' => 'Junior',
                                'Middle' => 'Middle',
                                'Senior' => 'Senior',
                                'Lead' => 'Lead',
                                'Principal' => 'Principal',
                            ])
                            ->searchable()
                            ->preload()
                            ->label('Level Pengalaman')
                            ->placeholder('Pilih level')
                            ->helperText('Pilih level pengalaman yang dibutuhkan'),
                    ])->columns(2),

                Forms\Components\Section::make('Deskripsi & Persyaratan Pekerjaan')
                    ->schema([
                        Forms\Components\Textarea::make('job_description')
                            ->required()
                            ->rows(4)
                            ->label('Deskripsi Pekerjaan')
                            ->placeholder('Jelaskan peran, tanggung jawab, dan ekspektasi...')
                            ->helperText('Deskripsi detail tentang peran pekerjaan')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('required_skills')
                            ->required()
                            ->rows(3)
                            ->label('Keahlian yang Dibutuhkan')
                            ->placeholder('Contoh: Laravel, PostgreSQL, AWS, REST API, JavaScript')
                            ->helperText('Daftar keahlian yang dibutuhkan dipisahkan dengan koma')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->client->contact_person)
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('position_name')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Junior' => 'gray',
                        'Middle' => 'info',
                        'Senior' => 'warning',
                        'Lead' => 'success',
                        'Principal' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'success',
                        'On-Process' => 'warning',
                        'Closed' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label('Kandidat')
                    ->badge()
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
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} menunggu" : 'Semua direview'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Klien'),
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'status_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Status'),
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'Junior' => 'Junior',
                        'Middle' => 'Middle',
                        'Senior' => 'Senior',
                        'Lead' => 'Lead',
                        'Principal' => 'Principal',
                    ])
                    ->searchable()
                    ->label('Filter berdasarkan Level'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('viewSubmissions')
                        ->label('Lihat Pengajuan')
                        ->icon('heroicon-o-user-group')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('index', ['tableFilters[vacancy][value]' => $record->id]))
                        ->color('info'),
                    Tables\Actions\Action::make('addCandidate')
                        ->label('Tambah Kandidat')
                        ->icon('heroicon-o-plus')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('create', ['vacancy_id' => $record->id]))
                        ->color('success'),
                    Tables\Actions\Action::make('closeVacancy')
                        ->label('Tutup Lowongan')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tutup Lowongan')
                        ->modalDescription('Apakah Anda yakin ingin menutup lowongan ini? Kandidat baru tidak akan diterima.')
                        ->action(function ($record) {
                            $record->update(['status_id' => 3]); // Closed status
                        })
                        ->visible(fn ($record) => $record->status_id !== 3),
                    Tables\Actions\Action::make('reopenVacancy')
                        ->label('Buka Kembali Lowongan')
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
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash'),
                    Tables\Actions\BulkAction::make('closeSelected')
                        ->label('Tutup yang Dipilih')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['status_id' => 3]);
                        }),
                    Tables\Actions\BulkAction::make('openSelected')
                        ->label('Buka yang Dipilih')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['status_id' => 1]);
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada lowongan')
            ->emptyStateDescription('Buat lowongan pertama Anda untuk memulai proses rekrutmen.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Lowongan Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
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
            'edit' => Pages\EditVacancy::route('/{record}/edit'),
            // HAPUS view page
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['submissions'])
            ->with(['client', 'status']);
    }
}