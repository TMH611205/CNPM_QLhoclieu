@extends('layouts.app')
@section('content')
<x-page-header eyebrow="MÔN HỌC" title="Danh sách môn học" description="Tạo, chỉnh sửa và xóa môn học dùng để phân loại khóa học.">
    <x-slot:actions><x-button :href="route('subjects.create')"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Tạo môn học</x-button></x-slot:actions>
</x-page-header>
<x-card class="p-3 p-md-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Môn học</th><th>Khóa học</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                @forelse($subjects as $subject)
                    <tr>
                        <td><div class="fw-semibold">{{ $subject->name }}</div><div class="small text-muted">{{ $subject->description }}</div></td>
                        <td>{{ $subject->courses_count }}</td>
                        <td class="text-end text-nowrap">
                            <x-button :href="route('subjects.edit', $subject)" variant="outline-secondary" class="btn-sm">Sửa</x-button>
                            <form method="POST" action="{{ route('subjects.destroy', $subject) }}" class="d-inline" onsubmit="return confirm('Xóa môn học này?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="outline-danger" class="btn-sm">Xóa</x-button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-empty-state title="Chưa có môn học" message="Hãy tạo môn học đầu tiên." icon="collection" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $subjects->links('pagination::bootstrap-5') }}</div>
</x-card>
@endsection
