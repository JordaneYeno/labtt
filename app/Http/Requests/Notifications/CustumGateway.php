<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustumGateway extends FormRequest
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
            'title' => 'required',
            'message' => 'required',
            'recipients' => 'required',
            // 'file' => 'file|max:2048|mimes:jpeg,png,pdf', // Retirez 'required' pour le rendre facultatif
            // 'file' => 'file|max:15360|mimes:jpeg,png,pdf', // 15 Mo = 15360 Ko

            // 'file' => 'file|max:15360|mimetypes:
            //     application/msword,
            //     application/vnd.openxmlformats-officedocument.wordprocessingml.document,
            //     application/vnd.ms-excel,
            //     application/vnd.ms-powerpoint,
            //     application/pdf,
            //     image/jpeg,
            //     image/png,
            //     image/gif,
            //     video/mp4,
            //     text/csv,
            //     application/csv',

            'file' => 'file|max:15360|mimes:doc,docx,xls,xlsx,ppt,pptx,pdf,jpg,jpeg,png,gif,mp4,csv',

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
            'title.required' => 'Veuillez indiquer le titre du message.',
            'message.required' => 'Veuillez indiquer le contenu du message.',
            'recipients.required' => 'Veuillez indiquer la liste de destinataires.',
            'file.file' => 'Le fichier doit être un fichier valide.',
            'file.max' => 'Le fichier ne doit pas dépasser 15Mo.',
            // 'file.mimes' => 'Le fichier doit être au format JPEG, PNG ou PDF.',
            'file.mimes' => 'Type de fichier non autorisé',
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            response()->json(['status' => 'echec', 'message' => $errors->first()], 400)
        );
    }
}
