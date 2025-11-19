<?php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreCoffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true ;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255', 'unique:coffres,name'],
            'current_balance'     => ['required', 'numeric', 'min:0'],
            'location'            => ['nullable', 'string', 'max:255'],
            'responsible_user_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du coffre est requis.',
            'name.unique' => 'Un coffre avec ce nom existe déjà.',
            'current_balance.required' => 'Le solde actuel est requis.',
            'current_balance.min' => 'Le solde ne peut pas être négatif.',
            'current_balance.numeric' => 'Le solde doit être un nombre.',
            'responsible_user_id.required' => 'L\'utilisateur responsable est requis.',
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
