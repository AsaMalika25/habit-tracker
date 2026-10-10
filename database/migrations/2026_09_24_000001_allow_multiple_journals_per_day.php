<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->index('user_id');
            $table->dropUnique(['user_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unique(['user_id', 'entry_date']);
            $table->dropIndex(['user_id']);
        });
    }
};
