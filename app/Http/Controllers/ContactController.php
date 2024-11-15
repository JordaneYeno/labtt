<?php

namespace App\Http\Controllers;

use App\Imports\ImportContact;
use App\Models\Contact;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function importContacts(Request $request){
        $validator = Validator::make(
            $request->only('csv_file'),
            [
                'csv_file' => 'required|file',

            ],
            [
                'csv_file.required' => 'veuillez sélectionner un fichier',
                'csv_file.file' => 'veuillez envoyer un fichier valide',
                'csv_file.mimes' => 'veuillez envoyer un fichier de type .csv'

            ]
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
        }

        $file = $request->file('csv_file');

        $exportation = Excel::import(new ImportContact, $file);
        return response()->json(['message' => $exportation]);
    }

    public function getContacts(){
        $contacts = User::find(auth()->user()->id)->contacts;
        return response()->json([
            'status' => 'success',
            'contacts' => $contacts
        ]);
    }

    public function deleteContact(Request $request){
        $contact = Contact::where('id', $request->contact_id)->delete();
        if($contact){
            return response()->json([
                'status' => 'succes',
                'message' => 'contact supprimé avec succès'
            ]);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'une erreur s\'est produite'
        ]);
    }
}
