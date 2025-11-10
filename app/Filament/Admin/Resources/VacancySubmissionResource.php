<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VacancySubmissionResource\Pages;
use App\Filament\Admin\Resources\VacancySubmissionResource\RelationManagers;
use App\Models\VacancySubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacancySubmissionResource extends Resource
{
    protected static ?string $model = VacancySubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'Pengajuan Kandidat';

    protected static ?string $modelLabel = 'Pengajuan Kandidat';

    protected static ?string $pluralModelLabel = 'Pengajuan Kandidat';

    protected static ?string $navigationGroup = 'Rekrutmen';

    public static function form(Form $form): Form
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
                        ->helperText('Pilih lowongan untuk kandidat ini')
                        ->default(function () {
                            // Ambil vacancy_id dari URL parameter
                            return request()->query('vacancy_id');
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            // Auto-set submitted_by_user_id ketika vacancy dipilih
                            $set('submitted_by_user_id', auth()->id());
                        }),
                        Forms\Components\Select::make('candidate_id')
                            ->relationship('candidate', 'full_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Kandidat')
                            ->helperText('Pilih kandidat yang akan diajukan'),
                        Forms\Components\Select::make('submission_status_id')
                            ->relationship('status', 'status_name')
                            ->required()
                            ->label('Status')
                            ->default(1) // submitted
                            ->preload()
                            ->native(false),
                        
                        // PERBAIKAN: Gunakan Hidden field instead of disabled
                        Forms\Components\Hidden::make('submitted_by_user_id')
                            ->default(auth()->id())
                            ->required(),
                        
                        // Tampilkan info saja, tapi jangan sebagai input
                        Forms\Components\Placeholder::make('submitted_by_info')
                            ->label('Diajukan Oleh')
                            ->content(auth()->user()->email)
                            ->helperText('Otomatis diisi dengan pengguna saat ini'),
                    ])->columns(2),

                Forms\Components\Section::make('Tanggapan Klien')
                    ->schema([
                        Forms\Components\Textarea::make('client_feedback')
                            ->label('Catatan Tanggapan Klien')
                            ->rows(3)
                            ->placeholder(
                fn ($record) => $record && $record->client_feedback 
                    ? $record->client_feedback 
                    : 'Belum ada tanggapan dari klien.'
            )
            ->readOnly() // Ini yang membuat field readonly
            ->disabled() // Tambahkan disabled juga untuk extra protection
            ->dehydrated() // Tetap simpan value ke database jika ada perubahan lain
            ->helperText(
                fn ($record) => $record && $record->client_feedback 
                    ? 'Tanggapan dari klien - hanya bisa diubah oleh klien melalui portal'
                    : 'Klien belum memberikan tanggapan'
            )
            ->columnSpanFull(),
    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nama Kandidat')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('candidate.unique_talent_id')
                    ->label('ID Talent')
                    ->searchable()
                    ->sortable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('vacancy.position_name')
                    ->label('Posisi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vacancy.client.company_name')
                    ->label('Perusahaan Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'client_interested' => 'success',
                        'client_rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('submittedBy.email')
                    ->label('Diajukan Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vacancy')
                    ->relationship('vacancy', 'position_name')
                    ->label('Filter berdasarkan Lowongan'),
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'status_name')
                    ->label('Filter berdasarkan Status'),
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('vacancy.client', 'company_name')
                    ->label('Filter berdasarkan Klien'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('viewCandidate')
                        ->label('Lihat Kandidat')
                        ->icon('heroicon-o-eye')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\CandidateResource::getUrl('edit', ['record' => $record->candidate_id]))
                        ->color('gray')
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->label('Edit Pengajuan')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('viewVacancy')
                        ->label('Lihat Lowongan')
                        ->icon('heroicon-o-briefcase')
                        ->url(fn ($record) => \App\Filament\Admin\Resources\VacancyResource::getUrl('edit', ['record' => $record->vacancy_id]))
                        ->color('info')
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('quickApprove')
                        ->label('Tandai Tertarik')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai sebagai Tertarik')
                        ->modalDescription('Apakah Anda yakin ingin menandai kandidat ini sebagai tertarik?')
                        ->action(function (VacancySubmission $record) {
                            $record->update([
                                'submission_status_id' => 2, // client_interested
                                'client_feedback' => 'Ditandai sebagai tertarik oleh admin.',
                            ]);
                            
                            // PERBAIKAN: Tambahkan notification trigger
                            \App\Events\ClientFeedbackGiven::dispatch($record);
                        })
                        ->visible(fn (VacancySubmission $record) => $record->submission_status_id === 1),
                    Tables\Actions\Action::make('quickReject')
                        ->label('Tandai Ditolak')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai sebagai Ditolak')
                        ->modalDescription('Apakah Anda yakin ingin menandai kandidat ini sebagai ditolak?')
                        ->action(function (VacancySubmission $record) {
                            $record->update([
                                'submission_status_id' => 3, // client_rejected
                                'client_feedback' => 'Ditandai sebagai ditolak oleh admin.',
                            ]);
                            
                            // PERBAIKAN: Tambahkan notification trigger
                            \App\Events\ClientFeedbackGiven::dispatch($record);
                        })
                        ->visible(fn (VacancySubmission $record) => $record->submission_status_id === 1),
                    Tables\Actions\Action::make('resetStatus')
                        ->label('Reset ke Diajukan')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Status')
                        ->modalDescription('Reset status pengajuan ini kembali ke status diajukan?')
                        ->action(function (VacancySubmission $record) {
                            $record->update([
                                'submission_status_id' => 1, // submitted
                                'client_feedback' => null,
                            ]);
                        })
                        ->visible(fn (VacancySubmission $record) => $record->submission_status_id !== 1),
                ])
                ->button()
                ->label('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markInterested')
                        ->label('Tandai sebagai Tertarik')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'submission_status_id' => 2,
                                    'client_feedback' => 'Ditandai massal sebagai tertarik oleh admin.',
                                ]);
                                \App\Events\ClientFeedbackGiven::dispatch($record);
                            });
                        }),
                    Tables\Actions\BulkAction::make('markRejected')
                        ->label('Tandai sebagai Ditolak')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'submission_status_id' => 3,
                                    'client_feedback' => 'Ditandai massal sebagai ditolak oleh admin.',
                                ]);
                                \App\Events\ClientFeedbackGiven::dispatch($record);
                            });
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
            ->emptyStateDescription('Setelah Anda mengajukan kandidat ke lowongan, mereka akan muncul di sini.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat pengajuan pertama')
                    ->icon('heroicon-o-plus'),
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
            'index' => Pages\ListVacancySubmissions::route('/'),
            'create' => Pages\CreateVacancySubmission::route('/create'),
            'edit' => Pages\EditVacancySubmission::route('/{record}/edit'),
        ];
    }
}