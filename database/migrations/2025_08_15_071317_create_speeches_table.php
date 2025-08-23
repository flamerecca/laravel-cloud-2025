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
        Schema::create('speeches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('speaker_id')->constrained('speakers')->cascadeOnDelete();
            $table->decimal('fee', 10)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'paid', 'completed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speeches');
    }
};
