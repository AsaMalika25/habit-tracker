<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $idType = Schema::getColumnType('habits', 'id');

        if (in_array($idType, ['bigint', 'integer'], true)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            $this->createBigintTables();
            $habitIdMap = $this->copyHabits();
            $this->copyHabitLogs($habitIdMap);

            Schema::drop('habit_logs');
            Schema::drop('habits');
            Schema::rename('habits_bigint', 'habits');
            Schema::rename('habit_logs_bigint', 'habit_logs');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // UUID values cannot be reconstructed after conversion to auto-increment IDs.
    }

    private function createBigintTables(): void
    {
        Schema::create('habits_bigint', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('mindfulness');
            $table->date('start_date')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('habit_logs_bigint', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained('habits_bigint')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->timestamp('completed_at');

            $table->unique(['habit_id', 'log_date']);
            $table->index(['user_id', 'log_date']);
        });
    }

    /** @return array<string, int> */
    private function copyHabits(): array
    {
        $habitIdMap = [];

        foreach (DB::table('habits')->orderBy('created_at')->get() as $habit) {
            $newId = DB::table('habits_bigint')->insertGetId([
                'user_id' => $habit->user_id,
                'title' => $habit->title,
                'category' => $habit->category,
                'start_date' => $habit->start_date,
                'is_active' => $habit->is_active,
                'created_at' => $habit->created_at,
                'updated_at' => $habit->updated_at,
            ]);

            $habitIdMap[(string) $habit->id] = $newId;
        }

        return $habitIdMap;
    }

    /** @param array<string, int> $habitIdMap */
    private function copyHabitLogs(array $habitIdMap): void
    {
        foreach (DB::table('habit_logs')->orderBy('completed_at')->get() as $log) {
            DB::table('habit_logs_bigint')->insert([
                'habit_id' => $habitIdMap[(string) $log->habit_id],
                'user_id' => $log->user_id,
                'log_date' => $log->log_date,
                'completed_at' => $log->completed_at,
            ]);
        }
    }
};
