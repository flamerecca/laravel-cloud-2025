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
        Schema::create('newsletter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');

            $table->unique(['newsletter_issue_id', 'newsletter_subscriber_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_deliveries');
    }
};
