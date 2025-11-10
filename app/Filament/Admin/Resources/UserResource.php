<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Role;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'User';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'company_name')
                            ->searchable()
                            ->preload()
                            ->label('Perusahaan Klien')
                            ->helperText('Tetapkan ke perusahaan klien (kosongkan untuk pengguna admin)'),
                        Forms\Components\Select::make('role_id')
                            ->relationship('role', 'role_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Peran')
                            ->helperText('Peran pengguna dalam sistem')
                            ->default(1), // Default ke admin
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Alamat Email')
                            ->placeholder('pengguna@perusahaan.com')
                            ->unique(ignoreRecord: true)
                            ->helperText('Ini akan digunakan untuk login'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->maxLength(255)
                            ->label('Kata Sandi')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn ($operation) => $operation === 'create')
                            ->helperText(fn ($operation) => $operation === 'create' 
                                ? 'Atur kata sandi awal' 
                                : 'Kosongkan untuk mempertahankan kata sandi saat ini'),
                    ])->columns(2),

                Forms\Components\Section::make('Status Akun')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan untuk mencegah login'),
                        Forms\Components\Placeholder::make('last_login_at')
                            ->label('Login Terakhir')
                            ->content(fn ($record) => $record?->last_login_at 
                                ? $record->last_login_at->format('d M Y H:i') 
                                : 'Belum pernah login'),
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Dibuat Pada')
                            ->content(fn ($record) => $record?->created_at 
                                ? $record->created_at->format('d M Y H:i') 
                                : '-'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->client?->company_name ?? 'Tidak Ada Klien'),
                Tables\Columns\TextColumn::make('role.role_name')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'client' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Tidak Ada Klien'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Belum Pernah')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'role_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Peran'),
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->label('Filter berdasarkan Klien'),
                Tables\Filters\Filter::make('is_active')
                    ->label('Hanya Pengguna Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('Belum Pernah Login')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at')),
                Tables\Filters\Filter::make('recently_created')
                    ->label('Dibuat 7 Hari Terakhir')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Edit Pengguna')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('sendResetPassword')
                        ->label('Kirim Tautan Reset')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function ($record) {
                        }),
                    Tables\Actions\Action::make('toggleActive')
                        ->label(fn ($record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_active ? 'Nonaktifkan Pengguna' : 'Aktifkan Pengguna')
                        ->modalDescription(fn ($record) => $record->is_active 
                            ? "Apakah Anda yakin ingin menonaktifkan {$record->email}? Mereka tidak akan bisa login." 
                            : "Apakah Anda yakin ingin mengaktifkan {$record->email}? Mereka akan bisa login kembali.")
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => !$record->is_active
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
                    Tables\Actions\BulkAction::make('activateSelected')
                        ->label('Aktifkan yang Dipilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        }),
                    Tables\Actions\BulkAction::make('deactivateSelected')
                        ->label('Nonaktifkan yang Dipilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Buat akun pengguna pertama untuk memulai.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Pengguna Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['role', 'client']);
    }
}