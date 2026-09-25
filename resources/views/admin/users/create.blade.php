@extends('layouts.dashboard')

@section('title', 'Thêm người dùng mới')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg text-sm">← Quay lại danh sách</a>
@endsection

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">➕ Thêm người dùng mới</h1>

    <div class="bg-white rounded-xl shadow-sm border p-6 max-w-2xl">
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400"
                    placeholder="Nguyễn Văn A">
                @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required maxlength="255"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400"
                    placeholder="email@example.com">
                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Vai trò</label>
                <select name="role" id="role" required
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                    @foreach(App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', App\Models\User::ROLE_USER) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" id="password" required minlength="6"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                <p class="text-xs text-gray-500 mt-1">Tối thiểu 6 ký tự.</p>
                @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</a>
                <button type="submit" class="px-5 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-medium">
                    💾 Tạo người dùng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
