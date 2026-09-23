<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_version_id')->constrained()->cascadeOnDelete();

            $table->string('description');
            $table->string('category')->nullable();

            // Cost is the internal/supplier price — optional, some
            // operators don't want to track margin at quotation time.
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('sell_price', 12, 2);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quotation_version_id', 'sort_order']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_line_items');
    }
};
