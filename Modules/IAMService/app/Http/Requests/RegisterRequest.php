<?php

namespace Modules\IAMService\Http\Requests;

use Illuminate\Validation\Rule;
use Shared\Constants\AvailableRoleConstantsHelper;

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
            'role' => ['nullable', 'string', Rule::in($this->availableRoles())],
        ];
    }

    /**
     * @return list<string>
     */
    private function availableRoles(): array
    {
        return [
            AvailableRoleConstantsHelper::SUPERADMIN,
            AvailableRoleConstantsHelper::ADMIN,
            AvailableRoleConstantsHelper::WARGA,
            AvailableRoleConstantsHelper::USER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Role must be one of: '.implode(', ', $this->availableRoles()).'.',
        ];
    }
}
