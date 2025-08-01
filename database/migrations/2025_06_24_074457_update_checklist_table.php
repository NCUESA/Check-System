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
        //
        Schema::table('checklist', function (Blueprint $table) {
            $table->dropColumn("inner_code_backup");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('checklist', function (Blueprint $table) {
            $table->string("inner_code_backup");
        });
    }
};
