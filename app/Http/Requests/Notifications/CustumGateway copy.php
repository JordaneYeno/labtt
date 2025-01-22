<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustumGateway2 extends FormRequest
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
            'file' => 'file|extension:csv'
            
            // 'file' => 'file|max:20480|mimes:jpeg,jpg,png,bmp,tiff,doc,docx,xls,xlsx,ppt,pptx,csv,text/csv,application/csv,application/vnd.ms-excel',
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
            // 'file.file' => 'Le fichier doit être un fichier valide.',
            // 'file.max' => 'Le fichier ne doit pas dépasser 20Mo.',
            'file.mimes' => 'Erreur de type de fichier (:attribute) - Seuls les fichiers DOC, DOCX, XLS, XLSX, PPT, PPTX, PDF, JPEG, PNG, CSV sont autorisés.',
            'file.file' => 'Le fichier doit être un fichier valide.',
            'file.extension' => 'Erreur de type de fichier (:attribute) - Seuls les types suivants sont autorisés : DOC, DOCX, XLS, XLSX, PPT, PPTX, PDF, JPEG, PNG, CSV. Type reçu : :mimetype',
            
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
