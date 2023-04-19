<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EitAdminRequest extends FormRequest
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
            'email' => 'required|unique:users,email,'.$this->id.'|email',
            'name' => 'required',
            'role' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'ایمیل اجباری میباشد.',
            'email.unique' => 'قبلا این ایمیل  اضافه شده است!',
            'email.email' => 'لطفا یک آدرس ایمیل معتبر وارد نمایید',
            'name.required' => 'نام اجباری میباشد!',
            'role.required' => 'نقش کاربری را انتخاب نمایید!',
        ];
    }

}
