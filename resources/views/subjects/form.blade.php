@extends('layouts.app')
@section('content')
<x-page-header eyebrow="MÔN HỌC" :title="$subject->exists ? 'Chỉnh sửa môn học' : 'Tạo môn học mới'" />
<div class="row"><div class="col-xl-8"><x-card class="p-4 p-md-5">
    <form method="POST" action="{{ $subject->exists ? route('subjects.update', $subject) : route('subjects.store') }}">
        @csrf
        @if($subject->exists) @method('PUT') @endif
        <x-form.input name="name" label="Tên môn học" :value="$subject->name" required />
        <div class="mb-3">
            <label for="description" class="form-label">Mô tả</label>
            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $subject->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="border-top pt-4 mt-2 d-flex gap-2">
            <x-button>{{ $subject->exists ? 'Lưu thay đổi' : 'Tạo môn học' }}</x-button>
            <x-button :href="route('subjects.index')" variant="outline-primary">Hủy</x-button>
        </div>
    </form>
</x-card></div></div>
@endsection
