@extends('layouts.app')
@section('content')
<x-page-header eyebrow="QUẢN TRỊ / TÀI KHOẢN" title="Danh sách tài khoản" description="Quản lý quyền truy cập của những người dùng trong hệ thống."><x-slot:actions><x-button :href="route('admin.users.create')"><i class="bi bi-person-plus me-2" aria-hidden="true"></i>Cấp tài khoản</x-button></x-slot:actions></x-page-header>
<x-card class="p-3 p-md-4">
    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end mb-4">
        <div class="col-md"><label for="q" class="form-label">Tìm kiếm</label><input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="Tên, username hoặc email" class="form-control"></div>
        <div class="col-md-3"><label for="role_id" class="form-label">Vai trò</label><select name="role_id" id="role_id" class="form-select"><option value="">Tất cả</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->display_name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label for="status" class="form-label">Trạng thái</label><select name="status" id="status" class="form-select"><option value="">Tất cả</option><option value="active" @selected(request('status') === 'active')>Đang hoạt động</option><option value="locked" @selected(request('status') === 'locked')>Đã khóa</option></select></div>
        <div class="col-md-auto"><x-button variant="outline-primary">Lọc</x-button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Họ và tên</th><th class="d-none d-md-table-cell">Username / Email</th><th>Vai trò</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><div class="fw-semibold">{{ $user->name }}</div><div class="small text-muted d-md-none">{{ $user->email }}</div></td>
                        <td class="d-none d-md-table-cell"><div>{{ $user->username }}</div><div class="small text-muted">{{ $user->email }}</div></td>
                        <td><x-badge tone="primary">{{ $user->role?->display_name }}</x-badge></td>
                        <td><x-badge :tone="$user->status === 'active' ? 'success' : 'danger'">{{ $user->status === 'active' ? 'Đang hoạt động' : 'Đã khóa' }}</x-badge></td>
                        <td class="text-end text-nowrap">
                            <x-button :href="route('admin.users.edit', $user)" variant="outline-secondary" class="btn-sm">Sửa</x-button>
                            @unless($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline" onsubmit="return confirm('{{ $user->status === 'active' ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?' }}')">
                                    @csrf
                                    @method('PATCH')
                                    <x-button :variant="$user->status === 'active' ? 'outline-danger' : 'outline-primary'" class="btn-sm">{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}</x-button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Không có tài khoản" message="Không tìm thấy tài khoản phù hợp." icon="people" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links('pagination::bootstrap-5') }}</div>
</x-card>
@endsection
