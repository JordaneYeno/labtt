<?php

namespace App\Http\Requests\WaGroup;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGroup extends FormRequest
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
            'group' => ['required', 'min:3', 'max:55'],
            'description' => ['required'],
            'phone' => ['required']
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
            'group.required' => 'Le nom du groupe est obligatoire',
            'group.min' => 'Le nom du groupe doit avoir au moins 3 caractères',
            'group.max' => 'Le nom du groupe doit avoir au maximum 55 caractères',
            'description.required' => 'Veuillez ajouter du description pour ce groupe',
            'phone.required' => 'Veuillez ajouter des participants',
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
