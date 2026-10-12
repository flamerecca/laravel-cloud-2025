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
        Schema::table('newsletter_issues', function (Blueprint $table) {
            // Bumped each time an issue is turned back into unsent, so it can go out to everyone again.
            $table->unsignedInteger('send_round')->default(1)->after('number');
        });

        Schema::table('newsletter_deliveries', function (Blueprint $table) {
            $table->unsignedInteger('send_round')->default(1)->after('newsletter_subscriber_id');

            // Added before the old one is dropped so the foreign key always has an index.
            $table->unique(['newsletter_issue_id', 'newsletter_subscriber_id', 'send_round'], 'newsletter_deliveries_issue_subscriber_round_unique');
            $table->dropUnique(['newsletter_issue_id', 'newsletter_subscriber_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_deliveries', function (Blueprint $table) {
            $table->dropUnique('newsletter_deliveries_issue_subscriber_round_unique');
            $table->unique(['newsletter_issue_id', 'newsletter_subscriber_id']);
            $table->dropColumn('send_round');
        });

        Schema::table('newsletter_issues', function (Blueprint $table) {
            $table->dropColumn('send_round');
        });
    }
};
