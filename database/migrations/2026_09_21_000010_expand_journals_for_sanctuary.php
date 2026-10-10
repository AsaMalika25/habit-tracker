<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('title')->default('Untitled Reflection')->after('entry_date');
            $table->string('mood', 24)->default('calm')->after('mood_score');
            $table->json('tags')->nullable()->after('mood');
            $table->string('audio_url')->nullable()->after('image_url');
            $table->boolean('is_favorite')->default(false)->after('audio_url');
            $table->boolean('is_bookmarked')->default(false)->after('is_favorite');
            $table->timestamp('published_at')->nullable()->after('is_bookmarked');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'mood',
                'tags',
                'audio_url',
                'is_favorite',
                'is_bookmarked',
                'published_at',
            ]);
        });
    }
};
