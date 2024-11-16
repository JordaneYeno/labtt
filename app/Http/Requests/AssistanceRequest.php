<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssistanceRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'agent' => ['required', 'min:3', 'max:20'],
            'contact' => ['required', 'numeric', 'digits_between:8,16', 'unique:assistances,contact']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agent.required' => 'Le nom de l\'agent est obligatoire',
            'agent.min' => 'Le nom de l\'agent doit avoir au moins 3 caractères',
            'agent.max' => 'Le nom de l\'agent doit avoir au maximum 20 caractères',
            'contact.required' => 'veuillez saisir un numéro de téléphone',
            'contact.numeric' => 'veuillez saisir un numéro de téléphone valide',
            'contact.digits_between' => 'veuillez saisir un numéro de téléphone valide',
            'contact.unique' => 'Ce contact est déjà utilisé',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            response()->json(['status' => 'echec', 'message' => $errors->first()], 200)
        );
    }
}
