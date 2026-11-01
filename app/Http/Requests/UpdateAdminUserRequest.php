<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:80', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status' => ['required', 'in:active,locked'],
        ];
    }

    /**
     * The acting admin is always active, so blocking self-lock and self-demotion keeps at least one active admin.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $target = $this->route('user');

            if (! $target->is($this->user())) {
                return;
            }

            if ($this->input('status') === 'locked') {
                $validator->errors()->add('status', 'Bạn không thể tự khóa tài khoản của mình.');
            }

            if ((int) $this->input('role_id') !== $target->role_id) {
                $validator->errors()->add('role_id', 'Bạn không thể tự đổi vai trò của mình.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.alpha_dash' => 'Tên đăng nhập chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã tồn tại.',
            'role_id.required' => 'Vui lòng chọn vai trò.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
