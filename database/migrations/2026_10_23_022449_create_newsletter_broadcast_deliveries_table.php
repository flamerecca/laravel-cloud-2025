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
        Schema::create('newsletter_broadcast_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_broadcast_id')->constrained()->cascadeOnDelete();
            // Lowercased: one address gets a broadcast once, however many subscriptions it has.
            $table->string('email');
            $table->timestamp('sent_at');
            $table->unique(['newsletter_broadcast_id', 'email'], 'newsletter_broadcast_deliveries_broadcast_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_broadcast_deliveries');
    }
};
