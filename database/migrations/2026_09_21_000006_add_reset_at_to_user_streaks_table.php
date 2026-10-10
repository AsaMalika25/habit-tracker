<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_streaks', function (Blueprint $table) {
            $table->timestamp('reset_at')->nullable()->after('last_active_date');
        });
    }

    public function down(): void
    {
        Schema::table('user_streaks', function (Blueprint $table) {
            $table->dropColumn('reset_at');
        });
    }
};
