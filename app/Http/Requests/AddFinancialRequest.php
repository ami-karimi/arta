<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddFinancialRequest extends FormRequest
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
            'type' => 'required',
            'price' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'نوع ضروری میباشد!',
            'price.required' => 'مبلغ ضروری میباشد!',
            'price.integer' => 'قیمت باید عددی باشد!',
        ];
    }
    public function all($keys = null){
        if(empty($keys)){
            return parent::json()->all();
        }

        return collect(parent::json()->all())->only($keys)->toArray();
    }


}
