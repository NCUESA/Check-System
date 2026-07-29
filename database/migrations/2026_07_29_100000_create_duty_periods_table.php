<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 值勤填寫期間
     *
     * duty_start_date / duty_end_date 是這批班表涵蓋的「值勤日期」範圍，
     * open_at / close_at 則是學生「可以填寫或取消」的時間區間。
     * 兩者是不同的軸：例如 8/20~8/27 開放填寫 9/1~9/30 的班。
     */
    public function up(): void
    {
        Schema::create('duty_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->date('duty_start_date');
            $table->date('duty_end_date');
            $table->dateTime('open_at');
            $table->dateTime('close_at');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_periods');
    }
};
