<?php

namespace Database\Seeders;

use App\Models\SubmissionStatus;
use Illuminate\Database\Seeder;

class SubmissionStatusSeeder extends Seeder
{
    public function run(): void
    {
        SubmissionStatus::firstOrCreate(['status_name' => 'submitted']);
        SubmissionStatus::firstOrCreate(['status_name' => 'client_interested']);
        SubmissionStatus::firstOrCreate(['status_name' => 'client_rejected']);
    }
}