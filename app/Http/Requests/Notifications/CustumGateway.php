<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomGateway extends FormRequest
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
            // 'file' => 'file|mimes:jpeg,jpg,png,bmp,tiff,doc,docx,xls,xlsx,ppt,pptx,csv,text/csv,application/csv,application/vnd.ms-excel|max:20480',

           'file' => 'extension:csv'
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
            'file.mimes' => 'Erreur de type de fichier (:attribute) - Seuls les types suivants sont autorisés : DOC, DOCX, XLS, XLSX, PPT, PPTX, PDF, JPEG, PNG, CSV. Type reçu : :mimetype',
            'file.max' => 'Le fichier ne doit pas dépasser 20Mo.',
        ];
    }

    /**
     * Surcharge de la méthode pour personnaliser le message d'erreur
     * et afficher le type MIME du fichier en cas d'échec de validation.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstError = $errors->first();
        
        // Personnalisation du message d'erreur pour afficher le type MIME
        if ($firstError instanceof \Illuminate\Validation\ValidationException && $firstError->validator->failed()['mimes']) {
            $mimeType = $this->file->getMimeType();
            $customErrorMessage = str_replace(':mimetype', $mimeType, $this->messages()['file.mimes']);
            throw new HttpResponseException(
                response()->json(['status' => 'echec', 'message' => $customErrorMessage], 400)
            );
        } else {
            throw new HttpResponseException(
                response()->json(['status' => 'echec', 'message' => $firstError], 400)
            );
        }
    }
}