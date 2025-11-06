<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ClientResource\Pages;
use App\Filament\Admin\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Client';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Client';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perusahaan')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Perusahaan')
                            ->placeholder('Masukkan nama legal perusahaan')
                            ->helperText('Nama resmi terdaftar perusahaan'),
                        Forms\Components\TextInput::make('contact_person')
                            ->required()
                            ->maxLength(255)
                            ->label('Contact Person')
                            ->placeholder('Nama lengkap kontak utama')
                            ->helperText('Orang yang dapat dihubungi untuk rekrutmen'),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Kontak')
                    ->schema([
                        Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Email Kontak')
                            ->placeholder('kontak@perusahaan.com')
                            ->helperText('Email utama untuk komunikasi'),
                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Telepon Kontak')
                            ->placeholder('+62 812-3456-7890')
                            ->helperText('Nomor telepon utama dengan kode negara'),
                    ])->columns(2),

                Forms\Components\Section::make('Alamat Perusahaan')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->label('Alamat Perusahaan')
                            ->placeholder('Masukkan alamat lengkap perusahaan...')
                            ->helperText('Alamat lengkap perusahaan untuk korespondensi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Nama Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->contact_person),
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->iconColor('primary'),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Telepon')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->iconColor('success'),
                Tables\Columns\TextColumn::make('vacancies_count')
                    ->counts('vacancies')
                    ->label('Lowongan Aktif')
                    ->badge()
                    ->color(function ($state) {
                        if ($state == 0) return 'gray';
                        if ($state <= 2) return 'info';
                        if ($state <= 5) return 'warning';
                        return 'success';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Pengguna Portal')
                    ->badge()
                    ->color(function ($state) {
                        if ($state == 0) return 'danger';
                        if ($state == 1) return 'warning';
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
                Tables\Filters\Filter::make('has_vacancies')
                    ->label('Memiliki Lowongan Aktif')
                    ->query(fn (Builder $query): Builder => $query->has('vacancies')),
                Tables\Filters\Filter::make('has_users')
                    ->label('Memiliki Pengguna Portal')
                    ->query(fn (Builder $query): Builder => $query->has('users')),
                Tables\Filters\Filter::make('recently_added')
                    ->label('Ditambahkan 30 Hari Terakhir')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('viewVacancies')
                        ->label('Lihat Lowongan')
                        ->icon('heroicon-o-briefcase')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancyResource::getUrl('index', ['tableFilters[client][value]' => $record->id]))
                        ->color('info'),
                    Tables\Actions\Action::make('createVacancy')
                        ->label('Buat Lowongan')
                        ->icon('heroicon-o-plus')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancyResource::getUrl('create', ['client_id' => $record->id]))
                        ->color('success'),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash'),
                    Tables\Actions\BulkAction::make('exportContacts')
                        ->label('Ekspor Kontak')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            // Placeholder untuk fungsi ekspor
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada klien')
            ->emptyStateDescription('Mulai dengan menambahkan perusahaan klien pertama ke sistem.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Klien Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('company_name', 'asc')
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\VacanciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['vacancies', 'users']);
    }
}