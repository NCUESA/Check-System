<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthIpRequest extends FormRequest
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
            'ip_address' => [
                'required', 
                'max:50', 
                'string', 
                'ip', 
                'unique:authip,ip_address'
            ], 
            'description' => 'required|max:50|string'
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.required' => "請輸入IP位置", 
            'ip_address.ip' => "IP位置格式錯誤", 
            "ip_address.unique" => "已存在一個相同的IP位置", 
            "description.required" => "請輸入描述", 
            "description.max:50" => "簡介最長為50字", 
            "description.string" => "請輸入一個字串"
        ];
    }
}
