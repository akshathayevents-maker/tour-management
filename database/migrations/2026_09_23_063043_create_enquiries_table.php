<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Minimum data entry: which customer + what they want.
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('destination');

            // Traceability only, when the enquiry came out of a lead conversion.
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('adults')->nullable();
            $table->unsignedSmallInteger('children')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->string('hotel_category')->nullable();
            $table->boolean('transport_required')->nullable();
            $table->string('meal_plan')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('new');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
