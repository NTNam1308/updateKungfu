<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
                    'content'       => 'required',
                    'name'          => 'required|string|max:255',
                    'email'         => [
                        'required', 'email:rfc',
                        function($attribute, $value, $fail) {
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail($attribute . ' không hợp lệ.');
                            }
                        }
                       ]
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
            'content' => 'Nội Dung',
            'name' => 'Tên',
            'email' => 'Email',
        ];
    }
}
