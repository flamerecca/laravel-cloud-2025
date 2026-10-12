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
        Schema::create('newsletter_issues', function (Blueprint $table) {
            $table->id();
            // A NewsletterTopic value.
            $table->string('topic');
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->timestamp('scheduled_at')->nullable();
            // Set the moment delivery jobs are queued, so an issue is never queued twice.
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['topic', 'number']);
            $table->index(['scheduled_at', 'dispatched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_issues');
    }
};
