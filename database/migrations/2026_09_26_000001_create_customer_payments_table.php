<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->date('paid_at');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // Payments are insert-only. A wrong entry is voided, never
            // edited or deleted, so the financial trail stays honest —
            // voided payments are excluded from "amount paid" but remain
            // visible for audit.
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
