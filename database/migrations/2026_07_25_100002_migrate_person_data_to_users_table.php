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
        $people = DB::table('person')->get();

        foreach ($people as $person) {
            $userId = DB::table('users')->insertGetId([
                'name' => $person->name,
                'stu_id' => $person->stu_id,
                'status' => $person->status,
                'inner_code' => $person->inner_code,
                'inner_code_backup' => $person->inner_code_backup,
                'created_at' => $person->created_at,
                'updated_at' => $person->updated_at,
            ]);

            DB::table('cards')->where('person_id', $person->id)->update(['user_id' => $userId]);
            DB::table('checklist')->where('person_id', $person->id)->update(['user_id' => $userId]);
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
