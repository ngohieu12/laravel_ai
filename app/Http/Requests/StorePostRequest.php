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
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_alt' => ['nullable', 'string', 'max:255'],
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
            'image.file' => 'Ảnh đại diện phải là một tệp tin hợp lệ.',
            'image.image' => 'Tệp tải lên phải là ảnh.',
            'image.mimes' => 'Ảnh đại diện phải có định dạng JPG, PNG, WEBP hoặc GIF.',
            'image.max' => 'Kích thước ảnh đại diện tối đa là 4MB.',
            'image_alt.max' => 'Mô tả ảnh tối đa 255 ký tự.',
        ];
    }
}
