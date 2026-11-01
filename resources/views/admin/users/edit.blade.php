@extends('layouts.app')
@section('content')
<x-page-header eyebrow="QUẢN TRỊ / TÀI KHOẢN" title="Chỉnh sửa tài khoản" description="Cập nhật thông tin, vai trò, trạng thái hoặc đặt lại mật khẩu." />
<div class="row"><div class="col-xl-8"><x-card class="p-4 p-md-5">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6"><x-form.input name="name" label="Họ và tên" :value="$user->name" required /></div>
            <div class="col-md-6"><x-form.input name="username" label="Tên đăng nhập" :value="$user->username" help="Chỉ dùng chữ, số, gạch ngang hoặc gạch dưới." required /></div>
            <div class="col-md-6"><x-form.input name="email" label="Email" type="email" :value="$user->email" required /></div>
            <div class="col-md-6"><div class="mb-3">
                <label for="role_id" class="form-label">Vai trò <span class="text-accent" aria-hidden="true">*</span></label>
                <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                    @foreach($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->display_name }}</option>@endforeach
                </select>
                @error('role_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div></div>
            <div class="col-md-6"><x-form.input name="password" label="Mật khẩu mới" type="password" help="Để trống nếu không đặt lại mật khẩu." autocomplete="new-password" /></div>
            <div class="col-md-6"><x-form.input name="password_confirmation" label="Xác nhận mật khẩu mới" type="password" autocomplete="new-password" /></div>
            <div class="col-md-6"><div class="mb-3">
                <label for="status" class="form-label">Trạng thái <span class="text-accent" aria-hidden="true">*</span></label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="active" @selected(old('status', $user->status) === 'active')>Đang hoạt động</option>
                    <option value="locked" @selected(old('status', $user->status) === 'locked')>Khóa tài khoản</option>
                </select>
                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div></div>
        </div>
        <div class="border-top pt-4 mt-2 d-flex gap-2">
            <x-button>Lưu thay đổi</x-button>
            <x-button :href="route('admin.users.index')" variant="outline-primary">Hủy</x-button>
        </div>
    </form>
</x-card></div></div>
@endsection
