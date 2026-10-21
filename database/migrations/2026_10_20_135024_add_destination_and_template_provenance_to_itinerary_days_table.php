<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_days', function (Blueprint $table) {
            // Nullable — every existing day predates multi-destination
            // itineraries and simply has no destination grouping.
            $table->string('destination')->nullable()->after('day_number');

            // Traceability only (per source_itinerary_template_id doc
            // comment on ItineraryTemplateDay) — deleting the source
            // template must never cascade into generated trip data, so
            // this is nullOnDelete rather than a hard dependency.
            $table->foreignId('source_itinerary_template_day_id')->nullable()
                ->after('notes')
                ->constrained('itinerary_template_days')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_itinerary_template_day_id');
            $table->dropColumn('destination');
        });
    }
};
