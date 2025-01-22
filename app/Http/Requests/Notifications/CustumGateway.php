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
            'file.mimes' => 'Le fichier doit être au format DOC, DOCX, XLS, PPT, PDF, JPEG, PNG, GIF, MP4, CSV.',
        
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
