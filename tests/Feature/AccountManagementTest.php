<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $teacherRole;

    private Role $studentRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin', 'display_name' => 'Quản trị viên']);
        $this->teacherRole = Role::create(['name' => 'teacher', 'display_name' => 'Giảng viên']);
        $this->studentRole = Role::create(['name' => 'student', 'display_name' => 'Học viên']);
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
    }

    private function student(array $attributes = []): User
    {
        return User::factory()->create(['role_id' => $this->studentRole->id, ...$attributes]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $user, array $overrides = []): array
    {
        return [...$user->only(['name', 'username', 'email', 'role_id', 'status']), ...$overrides];
    }

    public function test_list_can_be_searched_and_filtered(): void
    {
        $match = $this->student(['name' => 'Nguyễn Tìm Thấy']);
        $locked = $this->student(['name' => 'Bị Khóa', 'status' => 'locked']);
        $teacher = User::factory()->create(['role_id' => $this->teacherRole->id]);
        $this->actingAs($this->admin);

        $ids = fn (array $query) => $this->get(route('admin.users.index', $query))->assertOk()->viewData('users')->pluck('id')->all();

        $this->assertSame([$match->id], $ids(['q' => 'Tìm Thấy']));
        $this->assertSame([$locked->id], $ids(['status' => 'locked']));
        $this->assertSame([$teacher->id], $ids(['role_id' => $this->teacherRole->id]));
        $this->assertSame([], $ids(['q' => 'Tìm Thấy', 'status' => 'locked']));
    }

    public function test_search_does_not_escape_other_filters(): void
    {
        $this->student(['name' => 'Trùng Tên', 'email' => 'a@example.com']);
        $teacher = User::factory()->create(['role_id' => $this->teacherRole->id, 'name' => 'Trùng Tên']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['q' => 'Trùng', 'role_id' => $this->teacherRole->id]));

        $this->assertSame([$teacher->id], $response->viewData('users')->pluck('id')->all());
    }

    public function test_admin_updates_info_and_role_without_touching_password(): void
    {
        $user = $this->student();
        $password = $user->password;
        $this->actingAs($this->admin)->get(route('admin.users.edit', $user))->assertOk();

        $this->put(route('admin.users.update', $user), $this->payload($user, ['name' => 'Tên Mới', 'role_id' => $this->teacherRole->id, 'password' => '', 'password_confirmation' => '']))
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Tên Mới', $user->name);
        $this->assertSame($this->teacherRole->id, $user->role_id);
        $this->assertSame($password, $user->password);
    }

    public function test_admin_resets_password(): void
    {
        $user = $this->student();

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), $this->payload($user, ['password' => 'mat-khau-moi', 'password_confirmation' => 'mat-khau-moi']))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('mat-khau-moi', $user->refresh()->password));
    }

    public function test_update_rejects_taken_username_and_unconfirmed_password(): void
    {
        $user = $this->student();
        $other = $this->student();

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), $this->payload($user, ['username' => $other->username, 'password' => 'mat-khau-moi', 'password_confirmation' => 'khac']))
            ->assertSessionHasErrors(['username', 'password']);
    }

    public function test_update_allows_keeping_own_username_and_email(): void
    {
        $user = $this->student();

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), $this->payload($user))->assertSessionHasNoErrors();
    }

    public function test_admin_locks_and_unlocks_other_account(): void
    {
        $user = $this->student();
        $this->actingAs($this->admin);

        $this->patch(route('admin.users.status', $user))->assertRedirect();
        $this->assertSame('locked', $user->refresh()->status);

        $this->patch(route('admin.users.status', $user))->assertRedirect();
        $this->assertSame('active', $user->refresh()->status);
    }

    public function test_admin_cannot_lock_or_demote_themselves(): void
    {
        $this->actingAs($this->admin);

        $this->patch(route('admin.users.status', $this->admin))->assertSessionHasErrors('status');
        $this->put(route('admin.users.update', $this->admin), $this->payload($this->admin, ['status' => 'locked', 'role_id' => $this->teacherRole->id]))->assertSessionHasErrors(['status', 'role_id']);

        $this->admin->refresh();
        $this->assertSame('active', $this->admin->status);
        $this->assertSame($this->adminRole->id, $this->admin->role_id);
    }

    public function test_locked_user_is_logged_out_on_next_request(): void
    {
        $user = $this->student();
        $this->actingAs($user)->get(route('student.dashboard'))->assertOk();

        $user->update(['status' => 'locked']);

        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_non_admin_cannot_manage_accounts(): void
    {
        $user = $this->student();
        $this->actingAs(User::factory()->create(['role_id' => $this->teacherRole->id]));

        $this->get(route('admin.users.edit', $user))->assertForbidden();
        $this->put(route('admin.users.update', $user), $this->payload($user, ['role_id' => $this->adminRole->id]))->assertForbidden();
        $this->patch(route('admin.users.status', $user))->assertForbidden();

        $this->assertSame($this->studentRole->id, $user->refresh()->role_id);
    }
}
