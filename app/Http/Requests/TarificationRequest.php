<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TarificationRequest extends FormRequest
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
            "nom" => "required|unique:tarifications,nom",
            "prix_sms" => "required|numeric|min:3",
            "prix_email" => "required|numeric|min:3",
            "prix_whatsapp" => "required|numeric|min:3",
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'le nom est obligatoire',
            'prix_sms.required ' => "le prix d'un sms est obligatoire",
            'prix_email.required ' => "le prix d'un email est obligatoire",
            'prix_whatsapp.required ' => "le prix d'un message whatsapp est obligatoire",
            'nom.unique' => "ce nom est déjà attribué",
            'prix_sms.numeric' => "le montant du sms est invalide",
            'prix_email.numeric' => "le montant de l'email est invalide",
            'prix_whatsapp.numeric' => "le montant du message whatsapp est invalide",
            'prix_sms.min ' => "le prix d'un service est au moins de 100 Fcfa",
            'prix_email.min ' => "le prix d'un service est au moins de 100 Fcfa",
            'prix_whatsapp.min ' => "le prix d'un service est au moins de 100 Fcfa",
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
