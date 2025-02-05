<?php

namespace App\Http\Requests\Monitors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateAdvertisementRequest extends FormRequest
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
            'title' => 'required|string',
            'description' => 'nullable|string',
            'media_path' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,mkv|max:10240', // Accepter des images ou des fichiers vidéo, max 10MB
            'start_date' => 'required|date|before_or_equal:end_date', // Assure que start_date est avant ou égale à end_date
            'end_date' => 'required|date|after_or_equal:start_date', // Assure que end_date est après ou égale à start_date
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
