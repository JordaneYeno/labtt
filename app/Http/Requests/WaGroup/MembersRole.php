<?php

namespace App\Http\Requests\WaGroup;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MembersRole extends FormRequest
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
            'phone' => ['required'],
            'admin' => ['required'],
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
            'wid.min' => 'Le nom du groupe doit avoir au moins 3 caractères',
            'phone.required' => 'Le contact du membre est obligatoire',
            'admin.required' => 'Rôle admin = true non admin = false',
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
