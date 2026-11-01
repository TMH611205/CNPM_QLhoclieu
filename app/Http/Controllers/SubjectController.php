<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        return view('subjects.index', ['subjects' => Subject::withCount('courses')->orderBy('name')->paginate(10)]);
    }

    public function create(): View
    {
        return view('subjects.form', ['subject' => new Subject]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        Subject::create($request->validated());

        return redirect()->route('subjects.index')->with('status', 'Môn học đã được tạo.');
    }

    public function edit(Subject $subject): View
    {
        return view('subjects.form', ['subject' => $subject]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()->route('subjects.index')->with('status', 'Môn học đã được cập nhật.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        // courses.subject_id cascades on delete, so removing a subject in use would silently delete its courses.
        if ($subject->courses()->exists()) {
            return back()->withErrors(['subject' => 'Không thể xóa môn học đang có khóa học.']);
        }

        $subject->delete();

        return redirect()->route('subjects.index')->with('status', 'Môn học đã được xóa.');
    }
}
