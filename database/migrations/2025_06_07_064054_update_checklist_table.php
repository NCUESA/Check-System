<?php

use App\Models\Person;
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
            $table->unsignedBigInteger("person_id")->nullable();
            $table->foreign("person_id")->references("id")->on("person")->restrictOnDelete()->cascadeOnUpdate();
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
