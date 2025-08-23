<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('speech_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speech_id')->constrained('speeches')->cascadeOnDelete();
            $table->string('client_name');
            $table->string('client_email');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->boolean('contract_signed')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speech_orders');
    }
};
