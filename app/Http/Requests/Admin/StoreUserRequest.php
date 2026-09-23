<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        // Minimum data entry: name + email + role. Password is generated
        // by the service and emailed/reset by the invited user, not typed
        // here by the admin.
        $allowedRoles = $this->user()->isSuperAdmin()
            ? ['company_admin', 'company_user']
            : ['company_user'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:'.implode(',', $allowedRoles)],
            'company_id' => [
                $this->user()->isSuperAdmin() ? 'required' : 'prohibited',
                'integer',
                'exists:companies,id',
            ],
        ];
    }
}
