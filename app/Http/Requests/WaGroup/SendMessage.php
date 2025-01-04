<?php

namespace App\Http\Requests\WaGroup;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendMessage extends FormRequest
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
            'wid' => ['required', 'min:8'],
            'message' => ['required'],
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
            'wid.required' => 'L\'id du groupe est obligatoire',
            'wid.min' => 'Le clé du groupe doit avoir au moins 8 caractères',
            'message.required' => 'Veuillez écrire un message correct.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            response()->json(['status' => 'echec', 'message' => $errors->first()], 422)
        );
    }
}
