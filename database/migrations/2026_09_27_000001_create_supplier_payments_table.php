<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_item_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->date('paid_at');

            // Unlike CustomerPayment, method is optional here — an
            // operator paying a driver ₹500 cash is far less likely to
            // record how than a customer's large deposit.
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // Snapshotted at the moment this payment is recorded, from
            // the BookingItem's own supplier snapshot at that time — not
            // read live from Supplier or BookingItem later. If the
            // BookingItem's supplier is later changed (Scenario H), this
            // payment must still show who the money actually went to.
            $table->string('supplier_name_snapshot')->nullable();
            $table->string('supplier_phone_snapshot')->nullable();

            // Payments are insert-only. A wrong entry is voided, never
            // edited or deleted — same rule as customer_payments.
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'booking_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
