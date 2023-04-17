<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditRasRequest extends FormRequest
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
            'ipaddress' => 'required|unique:nas,ipaddress,'.$this->id.'|max:20|ip',
            'name' => 'required',
            'secret' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'ipaddress.required' => 'آدرس آی پی ضروری میباشد.',
            'ipaddress.unique' => 'قبلا این سرور اضافه شده است!',
            'ipaddress.max' => 'آی پی حداکثر میتواند 20 کاراکتر باشد!',
            'ipaddress.ip' => 'لطفا یک آدرس آی پی معتبر وارد نمایید!',
            'name.required' => 'نام ضروری است!',
            'secret.required' => 'secret ضروری است!',
        ];
    }
}
