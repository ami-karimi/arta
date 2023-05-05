<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'group_type' => ['required'],
            'name' => 'required',
        ];
    }
    public function messages()
    {
        return [
            'group_type.required' => 'نوع انقضا ضروری میباشد.',
            'name.required' => 'نام غذا ضروری میباشد!',
            'expire_type.in' => 'نوع انقضا نامعتبر میباشد!',
            'expire_value.required' => 'مقدار انقضا ضروری میباشد!',
        ];
    }
}
