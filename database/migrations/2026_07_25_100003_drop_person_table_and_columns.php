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
        // The `person_id` foreign key doesn't exist on every environment (it's
        // missing on `cards` in some deployments), so only drop it when present.
        foreach (['cards', 'checklist'] as $table) {
            $hasPersonIdForeignKey = collect(Schema::getForeignKeys($table))
                ->contains(fn ($foreignKey) => $foreignKey['columns'] === ['person_id']);

            if ($hasPersonIdForeignKey) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropForeign(['person_id']);
                });
            }
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('person_id');
        });
        Schema::table('checklist', function (Blueprint $table) {
            $table->dropColumn('person_id');
        });

        Schema::dropIfExists('person');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('person', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('stu_id')->unique();
            $table->boolean('status')->default(1);
            $table->string('inner_code')->nullable();
            $table->string('inner_code_backup')->nullable();
            $table->timestamps();
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('person')->cascadeOnDelete();
        });
        Schema::table('checklist', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('person')->restrictOnDelete()->cascadeOnUpdate();
        });
    }
};
