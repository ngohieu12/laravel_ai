@extends('layouts.app')

@section('title', 'Đăng ký')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="bg-white rounded-xl shadow-sm border p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">📝 Đăng ký</h1>
            <p class="text-gray-500 text-sm mt-1">Tạo tài khoản mới để bắt đầu</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ tên</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="Nguyễn Văn A">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
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
                    placeholder="Tối thiểu 8 ký tự">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="Nhập lại mật khẩu">
            </div>

            <button type="submit"
                class="w-full bg-slate-600 hover:bg-slate-700 text-white px-4 py-3 rounded-lg transition font-medium">
                Đăng ký
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500">
            Đã có tài khoản?
            <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-800 font-medium">Đăng nhập</a>
        </div>
    </div>
</div>
@endsection
