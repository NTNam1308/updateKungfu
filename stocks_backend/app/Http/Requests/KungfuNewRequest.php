<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KungfuNewRequest extends FormRequest
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
                    'title'       => 'required',
                    'category_id'       => 'required',
                    'content'       => 'required',
                    'thumbnail'       => 'required'
                ];
            }
            case 'PUT':
            {
                return [
                    'title'       => 'required',
                    'category_id'       => 'required',
                    'content'       => 'required',
                    'thumbnail'       => 'required'
                ];
            }
            case 'PATCH':
            {
                return [
                    'title'       => 'required',
                    'category_id'       => 'required',
                    'content'       => 'required',
                    'thumbnail'       => 'required'
                ];
            }
            default:
                break;
        }
    }

    public function attributes()
    {
        return [
            'title' => 'Tiêu đề',
            'category_id' => 'Danh mục',
            'content' => 'Nội dung',
            'thumbnail' => 'Ảnh Thumbnail'
        ];
    }
}
