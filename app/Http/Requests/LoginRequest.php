<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            event(new Failed('login', $this->user(), []));

            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ]);
        }

        $this->session()->regenerate();
    }

    /**
     * Ensure the login request is not rate limited.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (RateLimiter::tooManyAttempts('login', 5)) {
            $seconds = RateLimiter::availableIn('login');

            throw ValidationException::withMessages([
                'email' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau '.ceil($seconds / 60).' phút.',
            ]);
        }

        RateLimiter::hit('login', 300);
    }

    /**
     * Get the rate limiting key for the request.
     */
    public function throttleKey(): string
    {
        return Str::lower($this->input('email')).'|'.$this->ip();
    }
}
