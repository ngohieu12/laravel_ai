@extends('layouts.dashboard')

@section('title', 'Chỉnh sửa người dùng: ' . $user->name)

@section('actions')
    <a href="{{ route('admin.users.show', $user) }}" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg text-sm">← Xem hồ sơ</a>
@endsection

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">✏️ Chỉnh sửa người dùng</h1>

    <div class="bg-white rounded-xl shadow-sm border p-6 max-w-2xl">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 pb-3 border-b">
                <div class="w-12 h-12 bg-gradient-to-br
                    {{ $user->isAdmin() ? 'from-amber-500 to-red-600' : ($user->isCreator() ? 'from-sky-500 to-indigo-600' : 'from-slate-400 to-slate-600') }}
                    rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                </div>
                @if($user->id === auth()->id())
                    <span class="ml-auto text-xs bg-sky-100 text-sky-700 px-2 py-1 rounded-full">Tài khoản của bạn</span>
                @endif
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required maxlength="255"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required maxlength="255"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Vai trò</label>
                <select name="role" id="role" required
                    @if($user->id === auth()->id()) disabled @endif
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400 disabled:bg-gray-100">
                    @foreach(App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if($user->id === auth()->id())
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <p class="text-xs text-amber-600 mt-1">Bạn không thể thay đổi vai trò của chính mình.</p>
                @endif
                @error('role')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Mật khẩu mới <span class="text-gray-400 font-normal">(để trống nếu không đổi)</span>
                </label>
                <input type="password" name="password" id="password" minlength="6"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                <input type="password" name="password_confirmation" id="password_confirmation" minlength="6"
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</a>
                <button type="submit" class="px-5 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-medium">
                    💾 Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
