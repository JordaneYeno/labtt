<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EnterpriseNameRequest extends FormRequest
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
            'nom_entreprise' => ['required', 'min:3', 'max:20', 'unique:abonnements,sms']
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
            'nom_entreprise.required' => 'Le nom de l\'entreprise est obligatoire',
            'nom_entreprise.min' => 'Le nom de l\'entreprise doit avoir au moins 3 caractères',
            'nom_entreprise.max' => 'Le nom de l\'entreprise doit avoir au maximum 20 caractères',
            'nom_entreprise.unique' => 'Ce nom de d\'entreprise existe déjà. Veuillez vérifier si vous n\'avez pas une demande en cours de traitement'
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
