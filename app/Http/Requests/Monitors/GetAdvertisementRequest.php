<?php

namespace App\Http\Requests\Monitors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetAdvertisementRequest extends FormRequest
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
            //
            'perPage' => 'integer|min:1',
            'filters' => 'nullable|array',
            'filters.*.ref' => 'required|integer',
            'filters.*.end_date' => 'nullable|date_format:Y-m-d H:i:s',
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
            'title.required' => 'Le titre est requis et doit être une chaîne de caractères.',
            'title.string' => 'Le titre doit être une chaîne de caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            // 'media_path.required' => 'Le média est requis.',
            // 'media_path.file' => 'Le fichier du média doit être un fichier valide.',
            // 'media_path.mimes' => 'Le fichier doit être une image ou une vidéo de type jpeg, png, jpg, gif, pdf ou mp4.',
            // 'media_path.max' => 'Le fichier du média ne doit pas dépasser 10MB.',
            'start_date.required' => 'La date de début est requise.',
            'start_date.date' => 'La date de début doit être une date valide.',
            'start_date.before_or_equal' => 'La date de début doit être avant ou égale à la date de fin.',
            'end_date.required' => 'La date de fin est requise.',
            'end_date.date' => 'La date de fin doit être une date valide.',
            'end_date.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
            'client_id.required' => 'Le client_id est requis.',
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
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
