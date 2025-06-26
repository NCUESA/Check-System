<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckRequest extends FormRequest
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
            "inner_code" => 'required|exists:cards,inner_code'
        ];
    }

    public function messages(): array
    {
        return [
            'inner_code.required' => "請輸入卡號", 
            'inner_code.exists' => "查無此卡"
        ];

    }
}
