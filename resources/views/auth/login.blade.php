@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="bg-white rounded-xl shadow-sm border p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">🔐 Đăng nhập</h1>
            <p class="text-gray-500 text-sm mt-1">Chào mừng bạn quay trở lại</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="email@example.com">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" id="password" name="password" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="••••••••">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember"
                    class="w-4 h-4 text-slate-600 border-gray-300 rounded focus:ring-slate-400">
                <label for="remember" class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit"
                class="w-full bg-slate-600 hover:bg-slate-700 text-white px-4 py-3 rounded-lg transition font-medium">
                Đăng nhập
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500">
            Chưa có tài khoản?
            <a href="{{ route('register') }}" class="text-slate-600 hover:text-slate-800 font-medium">Đăng ký ngay</a>
        </div>
    </div>
</div>
@endsection
