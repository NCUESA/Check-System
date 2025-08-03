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
        Schema::create('checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->references('id')->on('person')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('checkin_time');
            $table->boolean('checkin_operation')->default(0);
            $table->ipAddress('checkin_ip')->default('0.0.0.0');
            $table->dateTime('checkout_time');
            $table->boolean('checkout_operation')->default(0);
            $table->ipAddress('checkout_ip')->default('0.0.0.0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist');
    }
};
