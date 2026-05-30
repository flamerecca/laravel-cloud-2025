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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->comment('用戶 ID');
            $table->string('title')->comment('公告標題');
            $table->text('content')->comment('公告內容');
            $table->tinyInteger('status')->default(0)->comment('公告狀態 (0: unpublished, 1: published)');
            $table->boolean('is_pinned')->default(true)->comment('是否置頂');
            $table->timestamp('published_at')->nullable()->comment('發佈時間');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
