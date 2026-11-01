<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\Course;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'userCount' => User::count(),
            'teacherCount' => User::whereRelation('role', 'name', 'teacher')->count(),
            'studentCount' => User::whereRelation('role', 'name', 'student')->count(),
            'courseCount' => Course::count(),
            'subjectCount' => Subject::count(),
            'recentUsers' => User::with('role')->latest()->limit(5)->get(),
        ]);
    }

    public function users(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('role')
            ->when($request->query('q'), fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('role_id'), fn ($query, $roleId) => $query->where('role_id', $roleId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('id')->get()]);
    }

    public function createUser(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.create', ['roles' => Role::orderBy('id')->get()]);
    }

    public function storeUser(StoreAdminUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Tài khoản đã được cấp thành công.');
    }

    public function editUser(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['user' => $user, 'roles' => Role::orderBy('id')->get()]);
    }

    public function updateUser(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $user->update(array_filter($request->validated(), fn ($value, $key) => $key !== 'password' || $value !== null, ARRAY_FILTER_USE_BOTH));

        return redirect()->route('admin.users.index')->with('status', 'Tài khoản đã được cập nhật.');
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'Bạn không thể tự khóa tài khoản của mình.']);
        }

        $isLocked = $user->status === 'locked';
        $user->update(['status' => $isLocked ? 'active' : 'locked']);

        return back()->with('status', $isLocked ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }
}
