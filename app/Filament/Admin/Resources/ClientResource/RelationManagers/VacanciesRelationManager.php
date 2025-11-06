<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\VacancyStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VacanciesRelationManager extends RelationManager
{
    protected static string $relationship = 'vacancies';

    protected static ?string $title = 'Lowongan Pekerjaan';

    protected static ?string $modelLabel = 'Lowongan';

    protected static ?string $pluralModelLabel = 'Lowongan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Position Details')
                    ->schema([
                        Forms\Components\TextInput::make('position_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Posisi')
                            ->placeholder('contoh: Senior Backend Developer')
                            ->helperText('Masukkan nama posisi pekerjaan'),
                        Forms\Components\Select::make('level')
                            ->options([
                                'Junior' => 'Junior',
                                'Middle' => 'Menengah',
                                'Senior' => 'Senior',
                                'Lead' => 'Lead',
                                'Principal' => 'Principal',
                            ])
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Level Pengalaman')
                            ->helperText('Pilih level pengalaman yang dibutuhkan'),
                        Forms\Components\Select::make('status_id')
                            ->relationship('status', 'status_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Status')
                            ->default(1) // Open
                            ->helperText('Set the vacancy status'),
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
                            ->placeholder('contoh: Laravel, PostgreSQL, AWS, REST API, JavaScript')
                            ->helperText('Daftar keahlian yang dibutuhkan (pisahkan dengan koma)')
                            ->columnSpanFull(),
                    ]),

                // Hidden field for auto-assignment
                Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position_name')
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
                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->url(null) // Nonaktifkan link di kolom level
                    ->color(fn (string $state): string => match ($state) {
                        'Junior' => 'gray',
                        'Middle' => 'info',
                        'Senior' => 'warning',
                        'Lead' => 'success',
                        'Principal' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Status')
                    ->badge()
                    ->url(null) // Nonaktifkan link di kolom status
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
                    ->url(null) // Nonaktifkan link di kolom kandidat
                    ->color(function ($state) {
                        if ($state == 0) return 'gray';
                        if ($state <= 3) return 'info';
                        if ($state <= 6) return 'warning';
                        return 'success';
                    })
                    ->formatStateUsing(fn ($state) => "{$state} kandidat")
                    ->sortable(),
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
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} menunggu" : 'Semua direview'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom dibuat
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'status_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Status'),
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'Junior' => 'Junior',
                        'Middle' => 'Menengah',
                        'Senior' => 'Senior',
                        'Lead' => 'Lead',
                        'Principal' => 'Principal',
                    ])
                    ->searchable()
                    ->label('Filter berdasarkan Level'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Lowongan')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Buat Lowongan Baru')
                    ->modalDescription('Tambahkan lowongan pekerjaan baru untuk klien ini'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->modalHeading('Edit Lowongan'),
                    Tables\Actions\Action::make('viewSubmissions')
                        ->label('Lihat Kandidat')
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
                        ->modalDescription('Apakah Anda yakin ingin menutup lowongan ini? Tidak ada kandidat baru yang akan diterima.')
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
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Lowongan')
                        ->modalDescription('Apakah Anda yakin ingin menghapus lowongan ini? Tindakan ini tidak dapat dibatalkan.'),
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
            ->emptyStateDescription('Buat lowongan pertama untuk memulai proses rekrutmen untuk klien ini.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Lowongan Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['submissions']);
    }
}