<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PersonController extends Controller
{
    //

    public function showUserFull(Request $request)
    {
        $user_info = Person::orderBy('stu_id')
            ->get();

        return response()->json(['success' => true, 'data' => $user_info], 200);
    }

    public function addUser(Request $request)
    {
        $name = $request->input('name');
        $inner_code = $request->input('inner_code');
        $stu_id = $request->input('stu_id');
        $status = $request->input('status');

        if ($status == 'd') {
            $status = 0;
        } elseif ($status == 'u') {
            $status = 1;
        }

        $user_exist = Person::where('inner_code', $inner_code)->first();

        if (is_null($user_exist)) {
            // 當沒有資料時的處理
            Person::insert([
                'name' => $name,
                'inner_code' => $inner_code,
                'stu_id' => $stu_id,
                'status' => $status
            ]);
            return Inertia::mixin(); response()->json(['success' => true, 'message' => '資料已新增'], 200);
        } else {
            // 當有資料時的處理
            Person::where('inner_code', $inner_code)->update(
                [
                    'status' => $status,
                    'name' => $name,
                    'stu_id' => $stu_id
                ]
            );
            return response()->json(['success' => true, 'message' => '資料已更新'], 200);
        }
    }

}
