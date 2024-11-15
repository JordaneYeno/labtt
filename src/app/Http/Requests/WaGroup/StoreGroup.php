<?php

namespace App\Http\Requests\WaGroup;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGroup extends FormRequest
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
            'groupe' => ['required', 'min:3', 'max:55'],
            'members' => ['required']
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
            'groupe.required' => 'Le nom du groupe est obligatoire',
            'groupe.min' => 'Le nom du groupe doit avoir au moins 3 caractères',
            'groupe.max' => 'Le nom du groupe doit avoir au maximum 55 caractères',
            'members.required' => 'veuillez ajouter des participants',
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
