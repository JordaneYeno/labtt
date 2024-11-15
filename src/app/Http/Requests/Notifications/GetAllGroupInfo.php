<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetAllGroupInfo extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Vous pouvez ajuster cela en fonction des autorisations spécifiques
    }

    /**
     * Règles de validation pour la requête.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'device' => 'required|string',  // Le paramètre 'device' est requis et doit être une chaîne de caractères
        ];
    }

    /**
     * Messages d'erreur pour les règles de validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device.required' => 'Le paramètre "device" est requis.',
            'device.string' => 'Le paramètre "device" doit être une chaîne de caractères.',
        ];
    }

    /**
     * Gestion des erreurs de validation.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        // Lancer une exception HttpResponseException avec un message d'erreur personnalisé
        throw new HttpResponseException(
            response()->json(['status' => 'echec', 'message' => $errors->first()], 400)
        );
    }
}
