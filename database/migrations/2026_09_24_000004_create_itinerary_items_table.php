<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('itinerary_day_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->time('time')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['itinerary_day_id', 'sort_order']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_items');
    }
};
