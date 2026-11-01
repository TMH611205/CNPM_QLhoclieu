@extends('layouts.app')
@section('content')
@php($statusLabels = ['draft' => ['Nháp', 'secondary'], 'public' => ['Công khai', 'success'], 'archived' => ['Lưu trữ', 'dark']])
<x-page-header eyebrow="KHÓA HỌC" title="Danh sách khóa học" description="Tạo, chỉnh sửa khóa học và quản lý học viên tham gia.">
    <x-slot:actions><x-button :href="route('courses.create')"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Tạo khóa học</x-button></x-slot:actions>
</x-page-header>
<x-card class="p-3 p-md-4">
    <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end mb-4">
        <div class="col-md"><label for="subject_id" class="form-label">Môn học</label><select name="subject_id" id="subject_id" class="form-select"><option value="">Tất cả</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
        <div class="col-md"><label for="status" class="form-label">Trạng thái</label><select name="status" id="status" class="form-select"><option value="">Tất cả</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label[0] }}</option>@endforeach</select></div>
        <div class="col-md-auto"><x-button variant="outline-primary">Lọc</x-button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Khóa học</th><th class="d-none d-md-table-cell">Giảng viên</th><th>Trạng thái</th><th>Học viên</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td><div class="fw-semibold">{{ $course->title }}</div><div class="small text-muted">{{ $course->subject->name }}</div></td>
                        <td class="d-none d-md-table-cell">{{ $course->teacher->name }}</td>
                        <td><x-badge :tone="$statusLabels[$course->status][1]">{{ $statusLabels[$course->status][0] }}</x-badge></td>
                        <td>{{ $course->enrollments_count }}</td>
                        <td class="text-end text-nowrap">
                            <x-button :href="route('courses.students.index', $course)" variant="outline-primary" class="btn-sm">Học viên</x-button>
                            <x-button :href="route('courses.edit', $course)" variant="outline-secondary" class="btn-sm">Sửa</x-button>
                            <form method="POST" action="{{ route('courses.destroy', $course) }}" class="d-inline" onsubmit="return confirm('Xóa khóa học này cùng danh sách học viên của nó?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="outline-danger" class="btn-sm">Xóa</x-button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Chưa có khóa học" message="Không tìm thấy khóa học phù hợp." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $courses->links('pagination::bootstrap-5') }}</div>
</x-card>
@endsection
