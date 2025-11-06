<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Pengguna Portal';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Alamat Email')
                            ->placeholder('pengguna@perusahaan.com')
                            ->unique(ignoreRecord: true)
                            ->helperText('Email ini akan digunakan untuk login'),
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
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan untuk mencegah login'),
                    ])->columns(1),
                
                // Hidden fields for auto-assignment
                Forms\Components\Hidden::make('role_id')
                    ->default(2), // Default to client role
                Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->recordUrl(null) // Nonaktifkan klik pada seluruh baris
            ->recordAction(null) // Nonaktifkan aksi default
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom email
                    ->description(fn ($record) => $record->role->role_name),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->url(null) // Nonaktifkan link di kolom status
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom login terakhir
                    ->placeholder('Belum Pernah')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->url(null) // Nonaktifkan link di kolom dibuat
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->label('Hanya Pengguna Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('Belum Pernah Login')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pengguna')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Tambah Pengguna Portal')
                    ->modalDescription('Buat akun pengguna baru untuk klien ini'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Ubah')
                        ->icon('heroicon-o-pencil')
                        ->modalHeading('Ubah Akun Pengguna'),
                    Tables\Actions\Action::make('sendResetPassword')
                        ->label('Kirim Link Reset')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function ($record) {
                            // Placeholder for password reset functionality
                            // You can implement Laravel's password reset here
                        })
                        ->visible(fn () => auth()->user()->role->role_name === 'admin'),
                    Tables\Actions\Action::make('toggleActive')
                        ->label(fn ($record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_active ? 'Nonaktifkan Pengguna' : 'Aktifkan Pengguna')
                        ->modalDescription(fn ($record) => $record->is_active 
                            ? "Anda yakin ingin menonaktifkan {$record->email}? Mereka tidak akan dapat login." 
                            : "Anda yakin ingin mengaktifkan {$record->email}? Mereka akan dapat login kembali.")
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => !$record->is_active
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Akun Pengguna')
                        ->modalDescription('Anda yakin ingin menghapus akun pengguna ini? Tindakan ini tidak dapat dibatalkan.'),
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
            ->emptyStateHeading('Belum ada pengguna untuk klien ini')
            ->emptyStateDescription('Tambahkan pengguna untuk mengizinkan klien mengakses portal.')
            ->emptyStateIcon('heroicon-o-user-plus')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pengguna Pertama')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}