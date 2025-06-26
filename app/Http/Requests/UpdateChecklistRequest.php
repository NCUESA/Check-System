<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistRequest extends FormRequest
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
            "sid" => "string|required", 
            "checkin_time" => "date|required", 
            "checkout_time" => "date|required", 
            "checkin_at" => ['required', Rule::in(['jinde', 'baosan', 'other'])], 
            "checkout_at" => ['required', Rule::in(['jinde', 'baosan', 'other'])]
        ];
    }

    public function messages(): array
    {
        return [
            'sid.required' => "請輸入學號", 
            'sid.string' => "學號需為一個字串", 
            'checkin_time.required' => "請輸入簽到時間", 
            'checkin_time.date' => "簽到時間需為一個日期", 
            'checkout_time.required' => "請輸入簽退時間", 
            'checkout_time.date' => "簽退時間需為一個日期", 
            'checkin_at.required' => '請輸入簽到地點', 
            'checkin_at.in' => '簽到地點需為進德、寶山或其他', 
            'checkout_at.required' => '請輸入簽退地點', 
            'checkout_at.in' => '簽退地點需為進德、寶山或其他'
        ];

    }
}
