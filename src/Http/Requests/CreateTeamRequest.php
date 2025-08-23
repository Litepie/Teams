<?php

declare(strict_types=1);

namespace Litepie\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateTeamRequest
 * 
 * Validates team creation requests.
 */
class CreateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'settings' => ['nullable', 'array'],
            'settings.timezone' => ['nullable', 'string', 'timezone'],
            'settings.language' => ['nullable', 'string', 'max:10'],
            'settings.visibility' => ['nullable', 'in:public,private,restricted'],
            'settings.allow_invitations' => ['nullable', 'boolean'],
            'settings.max_members' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Team name is required.',
            'name.max' => 'Team name cannot exceed 255 characters.',
            'description.max' => 'Team description cannot exceed 1000 characters.',
            'settings.timezone' => 'Invalid timezone provided.',
            'settings.visibility.in' => 'Visibility must be public, private, or restricted.',
            'settings.max_members.min' => 'Maximum members must be at least 1.',
            'settings.max_members.max' => 'Maximum members cannot exceed 1000.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'team name',
            'description' => 'team description',
            'settings.timezone' => 'timezone',
            'settings.language' => 'language',
            'settings.visibility' => 'visibility',
            'settings.allow_invitations' => 'allow invitations',
            'settings.max_members' => 'maximum members',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default settings if not provided
        if (!$this->has('settings')) {
            $this->merge([
                'settings' => [
                    'timezone' => config('app.timezone', 'UTC'),
                    'language' => config('app.locale', 'en'),
                    'visibility' => 'private',
                    'allow_invitations' => true,
                    'max_members' => 50,
                ],
            ]);
        }
    }
}
