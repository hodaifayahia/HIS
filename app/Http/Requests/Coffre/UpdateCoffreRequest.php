<?php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateCoffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coffre = $this->route('coffre');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('coffres', 'name')->ignore($coffre->id)
            ],
            'current_balance'     => ['sometimes', 'numeric', 'min:0'],
            'location'            => ['sometimes', 'nullable', 'string', 'max:255'],
            'responsible_user_id' => ['sometimes', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Un coffre avec ce nom existe déjà.',
            'current_balance.min' => 'Le solde ne peut pas être négatif.',
            'current_balance.numeric' => 'Le solde doit être un nombre.',
            'responsible_user_id.exists' => 'L\'utilisateur responsable sélectionné n\'existe pas.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Les données fournies ne sont pas valides.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
