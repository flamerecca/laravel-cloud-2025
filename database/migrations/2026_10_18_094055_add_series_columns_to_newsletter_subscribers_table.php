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
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->timestamp('series_started_at')->nullable()->after('unsubscribed_at');
            $table->timestamp('series_next_at')->nullable()->after('series_started_at');
            // The next issue is only picked from issue numbers at or above this value.
            $table->unsignedInteger('series_start_number')->default(1)->after('series_next_at');

            $table->index(['series_started_at', 'series_next_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropIndex(['series_started_at', 'series_next_at']);
            $table->dropColumn(['series_started_at', 'series_next_at', 'series_start_number']);
        });
    }
};
