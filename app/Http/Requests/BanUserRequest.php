<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // An admin cannot ban themselves.
        return $this->user()
            && $this->user()->isAdmin()
            && (int) $this->route('user')->id !== (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'ban_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ban_reason.max' => 'Lý do khóa không được vượt quá :max ký tự.',
        ];
    }
}
