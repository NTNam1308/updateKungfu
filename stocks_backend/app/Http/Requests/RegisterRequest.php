<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
                    'phone'         => ['required', new PhoneNumber],
                    'address'       => 'required',
                    'name'          => 'required|string|max:255',
                    'email'         => [
                                        'required', 'email:rfc','unique:users,email',
                                        function($attribute, $value, $fail) {
                                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                            $fail($attribute . ' không hợp lệ.');
                                            }
                                        }
                                       ],
                    'password'      => 'required|min:6',
                    'password_confirmation' => 'required|same:password',
                    'reference_code' => 'exists:App\Models\User,personal_reference_code',
                ];
            }
            case 'PUT':
            {
                return [];
            }
            case 'PATCH':
            {
                return [];
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
            'name' => 'Tên',
            'email' => 'Email',
            'password' => 'Mật khẩu',
            'password_confirmation' => 'Nhập lại mật khẩu',
        ];
    }
}
