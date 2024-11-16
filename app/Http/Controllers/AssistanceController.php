<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistanceRequest;
use App\Models\Assistance;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;


class AssistanceController extends Controller
{
    protected $assistance;

    public function __construct()
    {
        $this->assistance = new Assistance();
    }

    // public function getMaintenanceStatus($serviceName)
    // {
    //     try {
    //         $rows = Assistance::where('ref', $serviceName)->first('secret')->secret;
    //         if ($rows == 0) {
    //             return  response()->json([
    //                 "status" => "succes",
    //                 "service" => $serviceName,
    //                 "maintenance" => 0,
    //             ]);
    //         } else {
    //             return  response()->json([
    //                 "status" => "succes",
    //                 "service" => $serviceName,
    //                 "maintenance" => 1,
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return "Erreur : " . $e->getMessage();
    //     }
    // }

    public function addAgent(AssistanceRequest $request)
    {
        try {
            $agent = Assistance::create([
                'agent' => $request->agent,
                'contact' => $request->contact,
            ]);

            return $agent ? response()->json([
                'status' => 'success',
                'message' => 'Agent d\'assistance créé avec succès',
            ], Response::HTTP_OK) :
                response()->json([
                    'status' => 'echec',
                    'message' => 'une erreur s\'est produite',
                ], Response::HTTP_OK);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json(['error' => 'erreur format JSON.'], 400);
            } else {
                return response()->json(['error' => 'Une erreur s\'est produite. Veuillez réessayer.'], 500);
            }
        }
    }

    public function getAgentList()
    {

        $agents = Assistance::where('status', 1)->select('contact', 'role')->get();
        $json = response()->json($agents);

        $data = json_decode($json->getContent(), true);

        // Remplace la clé "contact" par "k" pour chaque élément
        $data = array_map(function ($item) {
            return ['phone' => $item['contact'], 'admin' => $item['role']];
        }, $data);

        // Encode le tableau associatif en JSON
        $newJson = json_encode($data);

        $replacements = [
            '"admin":0' => '"admin":false',
            '"admin":1' => '"admin":true'
        ];

        // Parcours chaque remplacement et applique-le au JSON
        foreach ($replacements as $search => $replace) {
            $jsonWithoutSlash = str_replace($search, $replace, $newJson);
        }


        // // Remplace "admin":1 par "admin":true
        // $json = str_replace('"admin":1', '"admin":true', $json);

        // // Remplace "admin":0 par "admin":false
        // $json = str_replace('"admin":0', '"admin":false', $json);

        // $jsonWithoutSlash = str_replace('\\', '', $newJson);
        dd($jsonWithoutSlash);
        return response()->json($jsonWithoutSlash);
        // return response()->json($agents);
    }


    public function sendEnterpriseName(AssistanceRequest $request)
    {
        $nomEntreprise = $request->safe()['nom_entreprise'];
        $abonnement = Abonnement::where('user_id', auth()->user()->id);
        if (!$abonnement) {
            return response()->json([
                'status' => 'echec',
                'message' => 'aucun service trouvé pour cet Agent d\'assistance'
            ], 200);
        }
        if ($abonnement->first()->sms_status == 0) {
            $this->createDemande('sms');
            $abonnement->update(['sms' => $nomEntreprise, 'sms_status' => 1]);
            // (new SendMailService)->submitMail($this->mailAdmin, 'Activation sms', Param::getEmailAwt());
            return response()->json([
                'status' => 'success',
                'sms_status' => 1,
                'message' => 'le nom de l\'entreprise à été enregistré'
            ], 200);
        }
        if ($abonnement->first()->sms_status == 2) {
            $abonnement->update(['sms' => $nomEntreprise, 'sms_status' => 1]);
            // (new SendMailService)->submitMail($this->mailAdmin, 'Modification sms', Param::getEmailAwt());
            return response()->json([
                'status' => 'success',
                'message' => 'votre nouvelle demande à été enregistrée'
            ], 200);
        }
        if ($abonnement->first()->sms_status == 1) {
            return response()->json([
                'status' => 'success',
                'message' => 'votre demande de validation du nom de votre entreprise est en cours de traitement'
            ], 200);
        }
        return ['status' => 'echec', 'message' => 'votre abonnement est déjà activé'];
    }
}
