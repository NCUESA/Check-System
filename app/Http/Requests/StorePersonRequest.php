<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            "name" => "required|string", 
            "stu_id" => "required|string|unique:person,stu_id", 
            "status" => "required|boolean"
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "請輸入姓名", 
            'name.string' => "姓名需為一個字串", 
            'stu_id.required' => "請輸入學號", 
            'stu_id.string' => "學號需為一個字串", 
            'stu_id.unique' => "已存在相同的人員", 
            'status.required' => "請選擇人員狀態", 
            'status.boolean' => "人員狀態需為一布林值"
        ];

    }
}
