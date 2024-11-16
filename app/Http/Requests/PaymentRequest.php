<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class PaymentRequest extends FormRequest
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
            'montant' => ['required', 'integer', 'min:100'],
            'numero' => ['required', 'digits:9'],
            // 'reference' => ['required', 'min:6'],
            'operateur' => ['required', 'min:2', 'max:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.integer' => 'veuillez saisir un montant avec uniquement des chiffres',
            'montant.required' => 'veuillez saisir un montant',
            'montant.min' => 'la recharge minimale est de 1 000 Fcfa',
            'numero.required' => 'veuillez saisir un numéro valide',
            'numero.digits_between' => 'veuillez saisir un numéro valide'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            response()->json(['status' => 'echec', 'message' => $errors->first() ], 200)
        );
    }
}
