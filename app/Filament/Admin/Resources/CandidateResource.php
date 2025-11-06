<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CandidateResource\Pages;
use App\Filament\Admin\Resources\CandidateResource\RelationManagers;
use App\Models\Candidate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Kandidat';

    protected static ?string $modelLabel = 'Kandidat';

    protected static ?string $pluralModelLabel = 'Kandidat';

    protected static ?string $navigationGroup = 'Rekrutmen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')
                    ->schema([
                        Forms\Components\TextInput::make('unique_talent_id')
                            ->label('ID Talent Unik')
                            ->default('AUTO-GENERATED')
                            ->disabled()
                            ->dehydrated(false)
                            ->hidden(fn ($operation) => $operation === 'create')
                            ->visible(fn ($operation) => $operation === 'edit')
                            ->helperText('Dibuat otomatis oleh sistem'),
                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Lengkap')
                            ->placeholder('Masukkan nama lengkap kandidat')
                            ->helperText('Nama lengkap kandidat'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Alamat Email')
                            ->placeholder('kandidat@email.com')
                            ->helperText('Email utama untuk komunikasi'),
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(20)
                            ->label('Nomor Telepon')
                            ->placeholder('+62 812-3456-7890')
                            ->helperText('Sertakan kode negara jika internasional'),
                    ])->columns(2),

                Forms\Components\Section::make('Profil Online')
                    ->schema([
                        Forms\Components\TextInput::make('linkedin_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Profil LinkedIn')
                            ->placeholder('https://linkedin.com/in/username')
                            ->helperText('Tautan ke profil LinkedIn'),
                        Forms\Components\TextInput::make('portfolio_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Website Portfolio')
                            ->placeholder('https://portfolio-site.com')
                            ->helperText('Tautan ke portfolio atau website pribadi'),
                    ])->columns(2),

                Forms\Components\Section::make('Ringkasan Profesional')
                    ->schema([
                        Forms\Components\Textarea::make('experience_summary')
                            ->rows(3)
                            ->label('Ringkasan Pengalaman')
                            ->placeholder('Ringkasan pengalaman profesional, tahun pengalaman, pencapaian utama...')
                            ->helperText('Gambaran umum latar belakang dan pengalaman profesional')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('skills_summary')
                            ->rows(3)
                            ->label('Keahlian Teknis')
                            ->placeholder('Daftar keahlian teknis, bahasa pemrograman, framework, tools...')
                            ->helperText('Kompetensi teknis dan keterampilan')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('education_summary')
                            ->rows(2)
                            ->label('Latar Belakang Pendidikan')
                            ->placeholder('Kualifikasi pendidikan, gelar, sertifikasi...')
                            ->helperText('Latar belakang akademik dan sertifikasi')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Ketersediaan & Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Tersedia untuk peluang baru')
                            ->default(true)
                            ->helperText('Kandidat sedang aktif mencari peluang kerja'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unique_talent_id')
                    ->label('ID Talent')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Kandidat')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email),
                Tables\Columns\TextColumn::make('skills_summary')
                    ->label('Keahlian')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(function ($record) {
                        return $record->skills_summary;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('experience_summary')
                    ->label('Pengalaman')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(function ($record) {
                        return $record->experience_summary;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label('Pengajuan')
                    ->badge()
                    ->color(function ($state) {
                        if ($state == 0) return 'gray';
                        if ($state <= 3) return 'info';
                        return 'success';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_available')
                    ->label('Hanya Kandidat Tersedia')
                    ->query(fn (Builder $query): Builder => $query->where('is_available', true)),
                Tables\Filters\Filter::make('has_skills')
                    ->label('Memiliki Keahlian')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('skills_summary')->where('skills_summary', '!=', '')),
                Tables\Filters\Filter::make('recently_added')
                    ->label('Ditambahkan 7 Hari Terakhir')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit Kandidat')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('viewSubmissions')
                        ->label('Lihat Pengajuan')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('index', ['tableFilters[candidate_id][value]' => $record->id]))
                        ->color('info'),
                    Tables\Actions\Action::make('submitToVacancy')
                        ->label('Ajukan ke Lowongan')
                        ->icon('heroicon-o-plus')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('create', ['candidate_id' => $record->id]))
                        ->color('success'),
                    Tables\Actions\Action::make('toggleAvailability')
                        ->label(fn ($record) => $record->is_available ? 'Tandai Tidak Tersedia' : 'Tandai Tersedia')
                        ->icon(fn ($record) => $record->is_available ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge')
                        ->color(fn ($record) => $record->is_available ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_available' => !$record->is_available
                            ]);
                        }),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash'),
                    Tables\Actions\BulkAction::make('markAvailable')
                        ->label('Tandai sebagai Tersedia')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_available' => true]);
                        }),
                    Tables\Actions\BulkAction::make('markUnavailable')
                        ->label('Tandai sebagai Tidak Tersedia')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['is_available' => false]);
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada kandidat')
            ->emptyStateDescription('Mulai bangun database kandidat Anda dengan menambahkan kandidat pertama.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Kandidat Pertama')
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
            'index' => Pages\ListCandidates::route('/'),
            'create' => Pages\CreateCandidate::route('/create'),
            'edit' => Pages\EditCandidate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['submissions']);
    }
}