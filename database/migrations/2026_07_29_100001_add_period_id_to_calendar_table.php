<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 名額歸屬到某個值勤期間。
     *
     * 可為 null：臨時增額開出來的名額不屬於任何期間，
     * 這種名額一律視為已 Lock，只有管理員能異動。
     */
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->foreignId('period_id')
                ->nullable()
                ->after('id')
                ->constrained('duty_periods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->dropConstrainedForeignId('period_id');
        });
    }
};
