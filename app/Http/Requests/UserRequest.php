<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
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
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'min:3'],
            'phone' => ['required', 'digits_between:8,12']
            // 'password' => ['required', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'cette adresse email existe déjà',
            'email.required' => 'veuillez saisir une adresse email',
            'email.email' => 'veuillez saisir une adresse email valide',
            'name.required' => 'veuillez saisir un nom',
            'name.min' => 'veuillez saisir avec au moins 3 lettres',
            'phone.required' => 'veuillez saisir un numéro de téléphone',
            'phone.numeric' => 'veuillez saisir un numéro de téléphone valide',
            'phone.digits_between' => 'veuillez saisir un numéro de téléphone valide'
            // 'password.required' => 'veuillez saisir un mot de passe',
            // 'password.min' => 'veuillez saisir un mot de passe avec au moins 6 lettres',
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
