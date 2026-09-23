<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Polymorphic on purpose: today it's Lead/Customer/Enquiry,
            // tomorrow Quotation/Booking/Payment/Trip attach the same way
            // with no schema change.
            $table->string('followupable_type');
            $table->unsignedBigInteger('followupable_id');

            $table->dateTime('due_at');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index(['followupable_type', 'followupable_id']);
            // Backs the "today's follow-ups" (overdue/due today/upcoming) queries.
            $table->index(['company_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
