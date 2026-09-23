<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Minimum data entry: name + phone + source.
            $table->string('name');
            $table->string('phone');
            $table->string('source');

            $table->string('destination')->nullable();
            $table->date('travel_month')->nullable();
            $table->unsignedSmallInteger('travellers_count')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('new');

            // Set once the lead is converted; the lead row itself is never
            // deleted, so lead source/history survives for reporting.
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'phone']);
            $table->index(['company_id', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
