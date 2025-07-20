<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreOrderRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'regex:/^09\d{9}$/'], // شماره موبایل ایرانی
            'email' => ['required', 'email'],
            'payment_method' => ['required', Rule::in(['manual', 'online'])],
            'category_id' => ['required', 'integer', 'exists:shop_category,id,is_enabled,1'],
            'plan_id' => ['required', 'integer', 'exists:shop_category_child,id,is_enabled,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام الزامی است.',
            'name.max' => 'نام نباید بیشتر از ۵۰ کاراکتر باشد.',
            'phone.regex' => 'شماره موبایل وارد شده معتبر نیست.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل نادرست است.',
            'payment_method.in' => 'روش پرداخت نامعتبر میباشد!',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر یا فعال نیست.',
            'plan_id.exists' => 'پلن انتخاب‌شده معتبر یا فعال نیست.',
        ];
    }

}
