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
        Schema::table('checklist', function (Blueprint $table) {
            //
            $table->ipAddress('checkin_ip')->default('0.0.0.0');
            $table->ipAddress('checkout_ip')->default('0.0.0.0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist', function (Blueprint $table) {
            //
            $table->dropColumn("checkout_ip");
            $table->dropColumn("checkin_ip");
        });
    }
};
