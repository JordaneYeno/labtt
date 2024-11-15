<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WhatsappRequest extends FormRequest
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
            'numero_whatsapp' => ['required', 'digits_between:8,9', 'unique:abonnements,whatsapp']
        ];
    }

    public function messages(): array
    {
        return [
            'numero_whatsapp.required' => "Le numéro whatsapp est obligatoire",
            'numero_whatsapp.digits' => "Le numéro whatsapp doit avoir entre 8 et 9 chiffres",
            'numero_whatsapp.unique' => "Ce numéro whatsapp existe déjà",
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
