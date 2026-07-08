<?php

namespace Modules\IAMService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\IAMService\Models\Role;

class RegisterRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('user', 'username')],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('user', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string', Rule::exists('role', 'role')->where(fn ($query) => $query->where('is_active', true)->where('is_deleted', false))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.exists' => 'Role must be one of: '.implode(', ', $this->availableRoles()).'.',
        ];
    }

    /**
     * @return list<string>
     */
    private function availableRoles(): array
    {
        return Role::query()
            ->active()
            ->notDeleted()
            ->orderBy('role')
            ->pluck('role')
            ->all();
    }
}
