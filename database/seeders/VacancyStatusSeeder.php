<?php

namespace Database\Seeders;

use App\Models\VacancyStatus;
use Illuminate\Database\Seeder;

class VacancyStatusSeeder extends Seeder
{
    public function run(): void
    {
        VacancyStatus::firstOrCreate(['status_name' => 'Open']);
        VacancyStatus::firstOrCreate(['status_name' => 'On-Process']);
        VacancyStatus::firstOrCreate(['status_name' => 'Closed']);
    }
}