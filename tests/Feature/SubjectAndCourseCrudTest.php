<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectAndCourseCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role_id' => Role::create(['name' => 'teacher', 'display_name' => 'teacher'])->id]);
        $this->student = User::factory()->create(['role_id' => Role::create(['name' => 'student', 'display_name' => 'student'])->id]);
    }

    public function test_teacher_creates_updates_and_deletes_empty_subject(): void
    {
        $this->actingAs($this->teacher)->get(route('subjects.create'))->assertOk();
        $this->post(route('subjects.store'), ['name' => 'Toán', 'description' => 'Mô tả'])->assertRedirect(route('subjects.index'));
        $subject = Subject::firstWhere('name', 'Toán');

        $this->get(route('subjects.edit', $subject))->assertOk();
        $this->put(route('subjects.update', $subject), ['name' => 'Toán cao cấp'])->assertRedirect(route('subjects.index'));
        $this->assertSame('Toán cao cấp', $subject->refresh()->name);
        $this->get(route('subjects.index'))->assertOk()->assertSee('Toán cao cấp');

        $this->delete(route('subjects.destroy', $subject))->assertRedirect(route('subjects.index'));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_subject_name_is_required_and_unique_but_can_be_kept_on_update(): void
    {
        $subject = Subject::factory()->create(['name' => 'Toán']);
        $this->actingAs($this->teacher);

        $this->post(route('subjects.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route('subjects.store'), ['name' => 'Toán'])->assertSessionHasErrors('name');
        $this->put(route('subjects.update', $subject), ['name' => 'Toán'])->assertSessionHasNoErrors();
    }

    public function test_subject_with_courses_cannot_be_deleted(): void
    {
        $course = Course::factory()->create(['teacher_id' => $this->teacher->id]);

        $this->actingAs($this->teacher)->delete(route('subjects.destroy', $course->subject_id))->assertSessionHasErrors('subject');

        $this->assertDatabaseHas('subjects', ['id' => $course->subject_id]);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_student_cannot_access_subjects(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAs($this->student);

        $this->get(route('subjects.index'))->assertForbidden();
        $this->post(route('subjects.store'), ['name' => 'Mới'])->assertForbidden();
        $this->delete(route('subjects.destroy', $subject))->assertForbidden();
    }

    public function test_owner_deletes_course_with_cover_and_enrollments(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('courses/cover.jpg', 'x');
        $course = Course::factory()->create(['teacher_id' => $this->teacher->id, 'cover_image' => 'courses/cover.jpg']);
        Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $this->student->id]);

        $this->actingAs($this->teacher)->delete(route('courses.destroy', $course))->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseCount('enrollments', 0);
        Storage::disk('public')->assertMissing('courses/cover.jpg');
    }

    public function test_non_owner_cannot_delete_course(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->teacher)->delete(route('courses.destroy', $course))->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_course_list_filters_by_subject_and_status_and_keeps_query_in_pagination(): void
    {
        $subject = Subject::factory()->create();
        $match = Course::factory()->create(['teacher_id' => $this->teacher->id, 'subject_id' => $subject->id, 'status' => 'public']);
        Course::factory()->create(['teacher_id' => $this->teacher->id, 'subject_id' => $subject->id, 'status' => 'draft']);
        Course::factory()->create(['teacher_id' => $this->teacher->id, 'status' => 'public']);
        Course::factory()->create(['subject_id' => $subject->id, 'status' => 'public']);

        $courses = $this->actingAs($this->teacher)->get(route('courses.index', ['subject_id' => $subject->id, 'status' => 'public']))->assertOk()->viewData('courses');

        $this->assertSame([$match->id], $courses->pluck('id')->all());
        $this->assertStringContainsString('status=public', $courses->url(2));
    }
}
