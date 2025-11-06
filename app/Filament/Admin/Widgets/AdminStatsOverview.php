<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\Vacancy;
use App\Models\Candidate;
use App\Models\VacancySubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalClients = Client::count();
        $openVacancies = Vacancy::whereHas('status', function ($query) {
            $query->where('status_name', 'Open');
        })->count();
        $totalCandidates = Candidate::count();
        $pendingFeedback = VacancySubmission::whereHas('status', function ($query) {
            $query->where('status_name', 'submitted');
        })->count();

        // Calculate trends (you can replace with real data later)
        $clientTrend = $this->getTrendData([2, 3, 5, 7, 8, 10, $totalClients]);
        $vacancyTrend = $this->getTrendData([1, 2, 3, 2, 4, 5, $openVacancies]);
        $candidateTrend = $this->getTrendData([3, 5, 8, 10, 12, 15, $totalCandidates]);
        $feedbackTrend = $this->getTrendData([2, 4, 3, 5, 6, 4, $pendingFeedback]);

        return [
            Stat::make('Total Klien', $totalClients)
                ->description('Perusahaan klien aktif')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success')
                ->chart($clientTrend)
                ->chartColor('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url($totalClients > 0 ? \App\Filament\Admin\Resources\ClientResource::getUrl('index') : null),

            Stat::make('Lowongan Terbuka', $openVacancies)
                ->description('Posisi pekerjaan aktif')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('warning')
                ->chart($vacancyTrend)
                ->chartColor('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url($openVacancies > 0 ? \App\Filament\Admin\Resources\VacancyResource::getUrl('index') : null),

            Stat::make('Total Kandidat', $totalCandidates)
                ->description('Kandidat dalam database')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info')
                ->chart($candidateTrend)
                ->chartColor('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url($totalCandidates > 0 ? \App\Filament\Admin\Resources\CandidateResource::getUrl('index') : null),

            Stat::make('Menunggu Umpan Balik', $pendingFeedback)
                ->description('Menunggu review klien')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger')
                ->chart($feedbackTrend)
                ->chartColor('danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url($pendingFeedback > 0 ? \App\Filament\Admin\Resources\VacancySubmissionResource::getUrl('index', ['tableFilters[status][value]' => '1']) : null),
        ];
    }

    /**
     * Generate realistic trend data based on current value
     */
    private function getTrendData(array $data): array
    {
        // Ensure we have at least 7 data points for the chart
        while (count($data) < 7) {
            array_unshift($data, max(1, $data[0] - 1));
        }
        
        // Limit to last 7 data points
        return array_slice($data, -7);
    }

    public static function canView(): bool
    {
        return auth()->user()->role->role_name === 'admin';
    }
}