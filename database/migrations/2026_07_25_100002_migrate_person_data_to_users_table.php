<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // `person.stu_id` was never enforced unique in production, so the same
        // student can have multiple `person` rows. Group by stu_id and merge
        // them into a single user, remapping every duplicate's cards/checklist
        // history onto that one user so attendance history isn't split apart.
        $peopleByStuId = DB::table('person')->orderBy('updated_at')->get()->groupBy('stu_id');

        foreach ($peopleByStuId as $stuId => $duplicates) {
            $primary = $duplicates->last();

            $userId = DB::table('users')->insertGetId([
                'name' => $primary->name,
                'stu_id' => $stuId,
                'status' => $primary->status,
                'inner_code' => $primary->inner_code ?? null,
                'inner_code_backup' => $primary->inner_code_backup ?? null,
                'created_at' => $primary->created_at,
                'updated_at' => $primary->updated_at,
            ]);

            $personIds = $duplicates->pluck('id')->all();
            DB::table('cards')->whereIn('person_id', $personIds)->update(['user_id' => $userId]);
            DB::table('checklist')->whereIn('person_id', $personIds)->update(['user_id' => $userId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cards')->update(['user_id' => null]);
        DB::table('checklist')->update(['user_id' => null]);
        DB::table('users')->whereNotNull('stu_id')->delete();
    }
};
