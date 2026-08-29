<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'is_published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'summary.required' => 'Vui lòng nhập tóm tắt.',
            'content.required' => 'Vui lòng nhập nội dung.',
            'category.required' => 'Vui lòng nhập danh mục.',
        ];
    }
}
