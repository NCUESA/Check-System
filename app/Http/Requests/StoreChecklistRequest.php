<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistRequest extends FormRequest
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
            "checkin_at" => Rule::in(['jinde', 'baosan']), 
            "checkout_at" => Rule::in(['jinde', 'baosan'])
        ];
    }
}
