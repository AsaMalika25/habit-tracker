<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('category')->index();
        });

        DB::table('habits')
            ->whereNull('start_date')
            ->update(['start_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
