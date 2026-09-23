<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description');
            $table->string('category')->nullable();

            // Selling price is optional by design — "XYZ Resort" can be
            // recorded before the price is settled (progressive entry).
            $table->decimal('sell_price', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();

            // Generic date range, not check_in/check_out — a hotel, a
            // transport leg and a one-off activity all fit this shape
            // without category-specific columns.
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            $table->string('confirmation_number')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            // Snapshotted at confirmation time so a later change to the
            // supplier's own phone number doesn't rewrite this booking's
            // history of who was actually contacted.
            $table->string('supplier_name_snapshot')->nullable();
            $table->string('supplier_phone_snapshot')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'booking_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
