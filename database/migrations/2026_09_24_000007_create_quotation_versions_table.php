<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('version_number');
            $table->string('status')->default('draft');

            // Set once, on send(); the version becomes immutable from that
            // point on (enforced in the model/service, not just the UI).
            $table->timestamp('sent_at')->nullable();
            $table->date('valid_until')->nullable();

            $table->text('terms')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['quotation_id', 'version_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_versions');
    }
};
