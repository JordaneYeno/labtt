<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ClientTemplateController extends Controller
{
    /**
     * Upload template .
     */
    public function uploadTemplate(Request $request)
    {
        $request->validate(['template' => 'required|file|mimes:html,blade.php|max:2048', 'template_name' => 'required|string|max:255',]);  // Accepte HTML ou Blade

        $client = Abonnement::where('user_id', auth()->user()->id)->get();
        $clientId = $client->where('user_id', auth()->user()->id)->pluck('user_id')->first();
        $templatePath = resource_path("views/mail/clients/{$clientId}");
        if (!File::exists($templatePath)) {
            File::makeDirectory($templatePath, 0755, true);
        }
        // save
        $uploadedFile = $request->file('template');
        $fileName = $request->template_name . '.blade.php';
        $uploadedFile->move($templatePath, $fileName);

        (new Abonnement)->setIsCustomTemplate($clientId, true); // active le custom theme

        $template = Template::create([
            'user_id' => $clientId,
            'name' => $request->template_name,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json([
            'message' => 'Template créé avec succès',
            'template' => $template,
        ], 201);        
    }

     /**
     * Vérifie si un client a un template personnalisé.
     */
    public function getTemplateStatus()
    {
        // Trouver le client
        $client = Abonnement::where('user_id', auth()->user()->id)->get();
        $clientId = $client->where('user_id', auth()->user()->id)->pluck('user_id')->first();
        
        $name = Template::where('user_id', $clientId)->pluck('name')->first();
        $templateExists = (new Abonnement)->getIsCustomTemplate($clientId) == 1 && File::exists(resource_path("views/mail/clients/{$clientId}/{$name}.blade.php"));
        return response()->json([
            'client_id' => $clientId,
            'has_custom_template' => (new Abonnement)->getIsCustomTemplate($clientId),
            'template_exists' => $templateExists,
        ], 200);
    }

    
    public function getClientTemplateStatus($auth)
    {
        // Trouver le client
        $client = Abonnement::where('user_id', $auth)->get();
        $clientId = $client->where('user_id', $auth)->pluck('user_id')->first();
        
        $name = Template::where('user_id', $clientId)->pluck('name')->first();
        $templateExists = (new Abonnement)->getIsCustomTemplate($clientId) == 1 && File::exists(resource_path("views/mail/clients/{$clientId}/{$name}.blade.php"));
        return response()->json([
            'client_id' => $clientId,
            'has_custom_template' => (new Abonnement)->getIsCustomTemplate($clientId),
            'template_exists' => $templateExists,
            '$name' => $templateExists==true? $name : null,
        ], 200);
    }
    
    public function updateTemplate(Request $request, $clientId, $templateName)
    {
        $request->validate([
            'template_content' => 'required|string',
        ]);

        $templatePath = resource_path("views/emails/clients/{$clientId}/{$templateName}.blade.php");

        if (!File::exists($templatePath)) {
            return response()->json(['message' => 'Template non trouvé.'], 404);
        }

        File::put($templatePath, $request->template_content);

        return response()->json(['message' => 'Template mis à jour avec succès.'], 200);
    }


    /**
     * Vérifie si un client a un template personnalisé.
     */
    // public function getTemplateStatus($clientId)
    // {
    //     // Trouver le client
    //     $client = Client::findOrFail($clientId);

    //     // Vérifier l'existence du template
    //     $templateExists = $client->has_custom_template && File::exists(resource_path("views/emails/clients/{$clientId}/template.blade.php"));

    //     return response()->json([
    //         'client_id' => $client->id,
    //         'has_custom_template' => $client->has_custom_template,
    //         'template_exists' => $templateExists,
    //     ], 200);
    // }
}
