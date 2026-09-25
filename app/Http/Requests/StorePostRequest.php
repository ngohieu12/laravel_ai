<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Closure;
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
            'tags' => ['nullable', 'string', 'max:1000', $this->tagListRule()],
        ];
    }

    /**
     * Each comma separated tag must be short enough and a post may only carry
     * a limited number of tags.
     */
    private function tagListRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $names = Tag::parseNames((string) $value);

            if (count($names) > Tag::MAX_PER_POST) {
                $fail('Mỗi bài viết chỉ được gắn tối đa '.Tag::MAX_PER_POST.' tag.');

                return;
            }

            foreach ($names as $name) {
                if (mb_strlen($name) > Tag::MAX_NAME_LENGTH) {
                    $fail('Mỗi tag tối đa '.Tag::MAX_NAME_LENGTH.' ký tự: "'.$name.'".');

                    return;
                }
            }
        };
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
            'tags.max' => 'Danh sách tag quá dài.',
        ];
    }
}
