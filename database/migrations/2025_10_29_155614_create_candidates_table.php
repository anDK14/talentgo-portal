<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('unique_talent_id')->unique()->nullable();
            $table->string('full_name', 255);
            $table->string('email', 255);
            $table->string('phone_number', 20)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('portfolio_url', 255)->nullable();
            $table->text('experience_summary')->nullable();
            $table->text('education_summary')->nullable();
            $table->text('skills_summary')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};