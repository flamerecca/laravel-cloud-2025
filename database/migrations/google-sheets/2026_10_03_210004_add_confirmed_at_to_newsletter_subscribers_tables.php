<?php

use App\Enums\NewsletterTopic;
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
        foreach ($this->tables() as $name) {
            Schema::connection('google-sheets')->table($name, function (Blueprint $table) {
                $table->timestamp('confirmed_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables() as $name) {
            Schema::connection('google-sheets')->table($name, function (Blueprint $table) {
                $table->dropColumn('confirmed_at');
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return ['newsletter_subscribers', NewsletterTopic::Filament->table()];
    }
};
