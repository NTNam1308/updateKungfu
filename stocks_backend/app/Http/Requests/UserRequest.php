<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
            case 'DELETE':
            {
                return [];
            }
            case 'POST':
            {
                return [
                    'email'         => 'required|email',
                    'password'      => 'required|min:6',
                ];
            }
            case 'PUT':
            {
                return [
                    'phone'     => ['required', new PhoneNumber],
                     'address'     => 'required',
                     'promotion_months'     => 'nullable|gte:0',
                ];
            }
            case 'PATCH':
            {
                return [
                    'phone'     => ['required', new PhoneNumber],
                     'address'     => 'required',
                     'promotion_months'     => 'nullable|gt:0',
                ];
            }
            default:
                break;
        }
    }
    public function attributes()
    {
        return [
            'phone' => 'Điện Thoại',
            'address' => 'Địa Chỉ',
            'email' => 'Email',
            'password' => 'Mật khẩu',
            'promotion_months' => 'Số tháng khuyến mãi',
        ];
    }
}
