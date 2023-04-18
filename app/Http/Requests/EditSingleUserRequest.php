<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditSingleUserRequest extends FormRequest
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
            'username' => 'required|unique:users,username,'.$this->id.'|max:20|regex:/^[a-zA-Z0-9]+$/u',
            'password' => 'required',
            'group_id' => 'required',
            'creator' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'نام کاربری ضروری میباشد.',
            'username.unique' => 'نام کاربری در دیتابیس موجود است!',
            'username.max' => 'نام کاربری میتوانید حداکثر 20 کاراکتر باشد!',
            'username.regex' => 'نام کاربری فقط میتوانید شامل حروف انگلیسی A-Z و اعداد 0-9 باشد!',
            'group.required' => 'گروه ضروری است',
            'creator.required' => 'ایجاد کننده ضروری است',
        ];
    }
}
