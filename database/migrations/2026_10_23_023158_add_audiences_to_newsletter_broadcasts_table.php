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
        Schema::table('newsletter_broadcasts', function (Blueprint $table) {
            // Topic values, plus 'general' for the general newsletter. Null means everyone.
            $table->json('audiences')->nullable()->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_broadcasts', function (Blueprint $table) {
            $table->dropColumn('audiences');
        });
    }
};
