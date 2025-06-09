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
        Schema::table('person', function (Blueprint $table) {

            $table->dropPrimary("inner_code");
            $table->dropColumn("inner_code");
            $table->id();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('person', function (Blueprint $table) {
            $table->dropPrimary('id');
            $table->dropColumn('id');
            $table->string("inner_code");
            $table->primary("inner_code");
        });
    }
};
