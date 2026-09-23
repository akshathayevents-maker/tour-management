<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('day_number');
            $table->date('date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['itinerary_id', 'day_number']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_days');
    }
};
