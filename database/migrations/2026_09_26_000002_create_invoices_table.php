<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // Sequential per company (INV-0001, INV-0002, ...).
            $table->string('invoice_number');
            $table->date('issued_at');
            $table->string('status')->default('issued');

            // Snapshotted from the booking's items at the moment of
            // issue — an invoice must stay historically correct even if
            // the booking's items change afterwards, so these are never
            // recomputed from BookingItem once written.
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->decimal('total', 12, 2);

            $table->string('customer_name_snapshot');
            $table->string('billing_details_snapshot')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
