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
            $table->foreignId("person_id")->references("id")->on("person")->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('checklist', function (Blueprint $table) {
            $table->dropConstrainedForeignId("person_id");
        });
    }
};
