<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinalOrderRequest extends FormRequest
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
            'order_code' => [
                'required',
                'string',
                'exists:shop_orders,order_id',
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:1024',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_code.required' => 'کد سفارش الزامی است.',
            'order_code.exists' => 'کد سفارش معتبر نیست.',
            'file.required' => 'فایل رسید الزامی است.',
            'file.file' => 'فایل معتبر ارسال نشده است.',
            'file.mimes' => 'فرمت فایل باید یکی از jpg, jpeg, png باشد.',
            'file.max' => 'حجم فایل نباید بیشتر از ۱ مگابایت باشد.',
        ];
    }

}
