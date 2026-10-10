<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_streaks', 'id')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::create('user_streaks_bigint', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedInteger('current_streak')->default(0);
                $table->unsignedInteger('longest_streak')->default(0);
                $table->date('last_active_date')->nullable();
                $table->timestamp('reset_at')->nullable();
            });

            foreach (DB::table('user_streaks')->get() as $streak) {
                DB::table('user_streaks_bigint')->insert([
                    'user_id' => $streak->user_id,
                    'current_streak' => $streak->current_streak,
                    'longest_streak' => $streak->longest_streak,
                    'last_active_date' => $streak->last_active_date,
                    'reset_at' => $streak->reset_at,
                ]);
            }

            Schema::drop('user_streaks');
            Schema::rename('user_streaks_bigint', 'user_streaks');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // The generated auto-increment IDs do not need to be converted back.
    }
};
