<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Demande;
use App\Models\Param;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

// const 
use App\Enums\ServiceStatus;
use App\Enums\ServiceType;
use App\Models\User;
use App\Services\SendMailService;
use Illuminate\Support\Facades\Log;

class AdminAbonnementController extends Controller 
{
    protected $abonnement;

    public function __construct()
    {
        $this->abonnement = new Abonnement();
    }

    public function getMaintenanceStatus($serviceName)
    {
        try {
            $rows = Param::where('ref', $serviceName)->first('secret')->secret;
            if ($rows == 0) {
                 return  response()->json([
                    "status" => "succes",
                    "service" => $serviceName,
                    "maintenance" => 0,
                ]);
            } else {return  response()->json([
                    "status" => "succes",
                    "service" => $serviceName,
                    "maintenance" => 1,
                ]);
               
            }
        } catch (\Exception $e) {
            return "Erreur : " . $e->getMessage();
        }
    }

    private function disableService($serviceName)
    {
        try {
            $row = Param::where('ref', $serviceName)->update(['secret' => 0]);
            return  response()->json([
                "status" => "succes",
                "service" => $serviceName,
                "message" => "maintenance " . str_replace("_status", "", $serviceName) . " activée"
            ]);
        } catch (\Exception $e) {
            return "Erreur : " . $e->getMessage();
        }
    }

    private function enableService($serviceName)
    {
        try {
            $row = Param::where('ref', $serviceName)->update(['secret' => 1]);
            return  response()->json([
                "status" => "succes",
                "service" => $serviceName,
                "message" => "maintenance " . str_replace("_status", "", $serviceName) . " désactivée"
            ]);
        } catch (\Exception $e) {
            return "Erreur : " . $e->getMessage();
        }
    }


    public function getMaintenanceSms(Request $request)
    {
        return $this->getMaintenanceStatus("SMS_STATUS");
    }

    public function getMaintenanceEmail(Request $request)
    {
        return $this->getMaintenanceStatus("EMAIL_STATUS");
    }

    public function getMaintenanceWhatsapp(Request $request)
    {
        return $this->getMaintenanceStatus("WHATSAPP_STATUS");
    }
    //

    public function disableSms(Request $request)
    {
        return $this->disableService("SMS_STATUS");
    }

    public function disableEmail(Request $request)
    {
        return $this->disableService("EMAIL_STATUS");
    }

    public function disableWhatsapp(Request $request)
    {
        return $this->disableService("WHATSAPP_STATUS");
    }

    public function enableSms(Request $request)
    {
        return $this->enableService("SMS_STATUS");
    }

    public function enableEmail(Request $request)
    {
        return $this->enableService("EMAIL_STATUS");
    }

    public function enableWhatsapp(Request $request)
    {
        return $this->enableService("WHATSAPP_STATUS");
    }

    // public function listRequest()
    // {
    //     $sms = DB::table('users')->leftJoin('abonnements', 'abonnements.user_id', 'users.id')->where('sms_status', 1)->select('users.id', 'users.email', 'users.name', 'abonnements.sms', 'abonnements.sms_status')->get();
    //     $email = DB::table('users')->leftJoin('abonnements', 'abonnements.user_id', 'users.id')->where('email_status', 1)->select('users.id', 'users.email', 'users.name', DB::raw('abonnements.email as service_email'), 'abonnements.email_status')->get();
    //     $whatsapp = DB::table('users')->leftJoin('abonnements', 'abonnements.user_id', 'users.id')->where('whatsapp_status', 1)->select('users.id', 'users.email', 'users.name', 'abonnements.whatsapp', 'abonnements.whatsapp_status')->get();
    //     return response()->json(['status' => 'success', 'demande_sms' => $sms, 'demande_email' => $email, 'demande_whatsapp' => $whatsapp]);
    // } // old

    public function listRequest()
    {
        $sms = DB::table('users')
            ->leftJoin('abonnements', 'abonnements.user_id', 'users.id')
            ->where('sms_status', ServiceStatus::PENDING)
            ->select('users.id', 'users.email', 'users.name', 'abonnements.sms', 'abonnements.sms_status')
            ->get();

        $email = DB::table('users')
            ->leftJoin('abonnements', 'abonnements.user_id', 'users.id')
            ->where('email_status', ServiceStatus::PENDING)
            ->select('users.id', 'users.email', 'users.name', DB::raw('abonnements.email as service_email'), 'abonnements.email_status')
            ->get();

        $whatsapp = DB::table('users')
            ->leftJoin('abonnements', 'abonnements.user_id', 'users.id')
            ->where('whatsapp_status', ServiceStatus::PENDING)
            ->select('users.id', 'users.email', 'users.name', 'abonnements.whatsapp', 'abonnements.whatsapp_status')
            ->get();

        return response()->json([
            'status' => 'success',
            'demande_sms' => $sms,
            'demande_email' => $email,
            'demande_whatsapp' => $whatsapp
        ]);
    }


    public function getAllAbonnements()
    {
        try {
            return response()->json([
                'abonnement' => Abonnement::orderBy('created_at', 'desc')->paginate(25),
                'status' => 'success'
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error-message' => $th
            ]);
        }
    }

    private function changeStatus($request, $serviceUpdate)
    {
        if (!$request->userId) {
            return response()->json([
                "status" => "error",
                "message" => "the user id is required"
            ]);
        }

        // return response()->json('rep',$request->userId);
        
        $result = $this->abonnement->$serviceUpdate($request->userId);
        return response()->json($result);
    }

    public function acceptSms(Request $request)
    {
        return $this->changeStatus($request, "acceptSms");
    }

    public function acceptEmail(Request $request)
    {
        return $this->changeStatus($request, "acceptEmail");
    }

    // public function acceptWhatsapp(Request $request)
    // {
    //     return $this->changeStatus($request, "acceptWhatsapp");
    // } // old

    public function acceptWhatsapp($requestUserId)
    {
        $userId = $requestUserId;
        
        // Mise à jour avec un statut explicite
        $whatsappStatuts = Abonnement::where(['user_id' => $userId, 'whatsapp_status' => ServiceStatus::PENDING])
            ->update(['whatsapp_status' => ServiceStatus::ACCEPTED, 'status' => ServiceStatus::ACCEPTED]);

        // Envoi du mail si l'activation est réussie
        if ($whatsappStatuts == 1) {
            (new SendMailService)->decisionMail(
                (User::getUser($requestUserId))->email,
                'Activation service de messagerie Whatsapp',
                'accept',
                $this->getEmailAwt(),
                'de whatsapp',
                (User::getUser($requestUserId))->name
            );
        }

        return response()->json([
            'message' => $whatsappStatuts ? "Le numéro WhatsApp a été accepté" : "Aucun service trouvé",
            'status' => $whatsappStatuts ? "success" : "echec"
        ]);
    }

    // public function rejectDemande($services, $userId)
    // {
    //     Demande::create([
    //         'service' => $services,
    //         'status' => 2,
    //         'user_id' => $userId
    //     ]);
    // } // old

    // public function rejectDemande($services, $userId)
    // {
    //     Demande::create([
    //         'service' => $services,
    //         'status' => ServiceStatus::REJECTED,
    //         'user_id' => $userId
    //     ]);
    // }

    public function rejectSms(Request $request)
    {
        $this->rejectDemande('sms', $request->userId);
        return $this->changeStatus($request, "rejectSms");
    }
    public function rejectEmail(Request $request)
    {
        $this->rejectDemande('email', $request->userId);
        return $this->changeStatus($request, "rejectEmail");
    }
    public function rejectWhatsapp(Request $request)
    {
        $this->rejectDemande('whatsapp', $request->userId);
        return $this->changeStatus($request, "rejectWhatsapp");
    }

    public function deleteService(Request $request)
    {
        $service = Abonnement::where('user_id', $request->id)->upadte(['status' => 0, '']);
    }
    
    public static function getEmailAwt()
    {
        return Param::where('ref', 'EMAIL_AWT')->first('secret')->secret;
    }

    // new
    public function updateServiceStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service' => 'required|in:' . implode(',', ServiceType::all()),
            'action' => 'required|in:accept,reject,reset'
        ]);

        $userId = $request->user_id;
        $service = $request->service;
        $action = $request->action;

        $statusMap = [
            'accept' => ServiceStatus::ACCEPTED,
            'reject' => ServiceStatus::REJECTED,
            'reset' => ServiceStatus::RESET,
        ];

        $update = Abonnement::where('user_id', $userId)
            ->update([
                $service . '_status' => $statusMap[$action],
                'status' => $action === 'accept' ? 1 : 0
            ]);

        // Logiquement : créer une demande de rejet ou envoyer un mail d’acceptation
        if ($action === 'accept') {
            $user = User::getUser($userId);
            
            try {
                // Tentative d'envoi de l'email
                (new SendMailService)->decisionMail(
                    $user->email,
                    'Activation du service de messagerie ' . ucfirst($service),
                    'accept',
                    $this->getEmailAwt(),
                    'de ' . $service,
                    $user->name
                );
                
                // Si l'email est envoyé avec succès, on peut logguer l'information (facultatif)
                Log::info("Email d'activation envoyé avec succès à l'utilisateur : " . $user->email);

            } catch (\Exception $e) {
                // En cas d'erreur, on capture l'exception et on log l'erreur
                Log::error("Erreur lors de l'envoi du mail à l'utilisateur $user->email : " . $e->getMessage());

            }
        }

        if ($action === 'reject') {
            Demande::create([
                'service' => $service,
                'status' => ServiceStatus::REJECTED,
                'user_id' => $userId
            ]);
        }

        return response()->json([
            'status' => $update ? 'success' : 'error',
            'message' => $update ? "Le service $service a été " . ($action === 'reset' ? 'réinitialisé' : "$action avec succès") : "Erreur lors de la mise à jour"
        ]);
    }

    public function acceptService($service, $userId)
    {
        // Récupérer l'instance de l'abonnement pour cet utilisateur et ce service
        $serviceField = $service . '_status';
        
        // Vérifie si l'utilisateur a une demande en attente pour ce service
        $abonnement = Abonnement::where('user_id', $userId)->first(); 
        if ($abonnement && $abonnement->$serviceField === ServiceStatus::PENDING) 
        {
            // Mettre à jour le statut du service à "accepté"
            $abonnement->$serviceField = ServiceStatus::ACCEPTED;
            $abonnement->status = ServiceStatus::ACCEPTED; // Mettre aussi le statut global en accepté
            $abonnement->save();

            // Vérifie si l'utilisateur a une demande en attente pour ce service
            $demande = Demande::where('user_id', $userId)
                ->where('service', $service)
                ->where('status', ServiceStatus::PENDING) // On ne veut mettre à jour que les demandes en attente
                ->first();

            if ($demande) 
            { 
                // Met à jour le statut de la demande en "Accepté"
                $demande->update([ 'status' => ServiceStatus::ACCEPTED ]);
            }

            // Envoi du mail d'acceptation
            $user = User::getUser($userId);

            try {
                // Tentative d'envoi de l'email
                (new SendMailService)->decisionMail(
                    $user->email,
                    'Activation du service ' . ucfirst($service),
                    'accept',
                    $this->getEmailAwt(),
                    'de ' . $service,
                    $user->name
                );
                
                // Si l'email est envoyé avec succès, on peut logguer l'information (facultatif)
                Log::info("Email d'activation envoyé avec succès à l'utilisateur : " . $user->email);

            } catch (\Exception $e) {
                // En cas d'erreur, on capture l'exception et on log l'erreur
                Log::error("Erreur lors de l'envoi du mail à l'utilisateur $user->email : " . $e->getMessage());

                return response()->json([
                    'status' => 'success',
                    'message' => 'Le service ' . $service . ' a été accepté avec succès.'
                ]);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Le service ' . $service . ' n\'est pas en attente ou n\'existe pas.'
        ]);
    }


    public function rejectService($service, $userId)
    {
        // Récupérer l'instance de l'abonnement pour cet utilisateur et ce service
        $serviceField = $service . '_status';
        
        // Vérifie si l'utilisateur a une demande en attente pour ce service
        $abonnement = Abonnement::where('user_id', $userId)->first();

        if ($abonnement && $abonnement->$serviceField === ServiceStatus::PENDING) {
            // Mettre à jour le statut du service à "rejeté"
            $abonnement->$serviceField = ServiceStatus::REJECTED;
            $abonnement->status = ServiceStatus::REJECTED; // Mettre aussi le statut global en rejeté
            $abonnement->save();

            AbonnementController::createDemande($service, $userId, ServiceStatus::REJECTED/**/);

            return response()->json([
                'status' => 'success',
                'message' => 'Le service ' . $service . ' a été rejeté avec succès.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Le service ' . $service . ' n\'est pas en attente ou n\'existe pas.'
        ]);
    }


    public function resetService($service, $userId)
    {
        // Vérifier si l'utilisateur a un abonnement existant
        $abonnement = Abonnement::where('user_id', $userId)->first();

        if (!$abonnement) {
            return response()->json([
                'status' => 'error',
                'message' => "Aucun abonnement trouvé pour l'utilisateur avec ID $userId."
            ]);
        }

        // Déterminer le champ du service à réinitialiser
        $serviceField = $service . '_status';

        // Vérifier si le service est dans un statut valide (accepté, rejeté ou en attente)
        if (in_array($abonnement->$serviceField, [ServiceStatus::ACCEPTED, ServiceStatus::REJECTED, ServiceStatus::PENDING])) {
            
            // Réinitialiser le service dans l'abonnement
            $abonnement->$serviceField = ServiceStatus::RESET;
            
            // Réinitialiser également l'ensemble du statut du service
            $abonnement->status = ServiceStatus::RESET;
            $abonnement->save();

            // Vérifier si la demande existe pour ce service
            $demande = Demande::where('user_id', $userId)
                ->where('service', $service)
                ->first();

            if (!$demande) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Aucune demande trouvée pour le service $service de cet utilisateur."
                ]);
            }

            // Réinitialiser le statut de la demande
            $demande->update([
                'status' => ServiceStatus::RESET
            ]);

            
            // Réinitialiser la colonne du service dans Abonnement (whatsapp, sms, email)
            if ($service == 'whatsapp') 
            {                
                $abonnement->whatsapp = null;
                $abonnement->whatsapp_status = ServiceStatus::RESET;
                $abonnement->save();
            } elseif ($service == 'sms') 
            {                                
                $abonnement->sms = null;
                $abonnement->sms_status = ServiceStatus::RESET;
                $abonnement->save();
            } elseif ($service == 'email') 
            {                                                
                $abonnement->email = null;
                $abonnement->email_status = ServiceStatus::RESET;
                $abonnement->save();
            }

            // Retourner un message de succès
            return response()->json([
                'status' => 'success',
                'message' => "Le service $service a été réinitialisé avec succès."
            ]);
        }

        // Si le service n'est pas dans un statut valide
        return response()->json([
            'status' => 'error',
            'message' => "Le service $service n'a pas pu être réinitialisé, statut invalide."
        ]);
    }



    public function getActiveServices($userId)
    {
        try {
            // Récupérer l'utilisateur
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur introuvable.'
                ]);
            }

            // Récupérer les services actifs pour cet utilisateur
            $activeServices = [];

            // Vérifier les services un par un (whatsapp, sms, email, etc.)
            if ($user->abonnement->whatsapp_status !== ServiceStatus::REJECTED) {
                $serviceData = [
                    'service' => ServiceType::WHATSAPP,
                    'status' => ServiceStatus::getStatusText($user->abonnement->whatsapp_status)
                ];
                
                // Si le service est "Accepté", on ajoute le champ "activation"
                if ($user->abonnement->whatsapp_status === ServiceStatus::ACCEPTED) {
                    $serviceData['activation'] = true;
                }

                $activeServices[] = $serviceData;
            }

            if ($user->abonnement->sms_status !== ServiceStatus::REJECTED) {
                $serviceData = [
                    'service' => ServiceType::SMS,
                    'status' => ServiceStatus::getStatusText($user->abonnement->sms_status)
                ];
                
                // Si le service est "Accepté", on ajoute le champ "activation"
                if ($user->abonnement->sms_status === ServiceStatus::ACCEPTED) {
                    $serviceData['activation'] = true;
                }

                $activeServices[] = $serviceData;
            }

            if ($user->abonnement->email_status !== ServiceStatus::REJECTED) {
                $serviceData = [
                    'service' => ServiceType::EMAIL,
                    'status' => ServiceStatus::getStatusText($user->abonnement->email_status)
                ];

                // Si le service est "Accepté", on ajoute le champ "activation"
                if ($user->abonnement->email_status === ServiceStatus::ACCEPTED) {
                    $serviceData['activation'] = true;
                }

                $activeServices[] = $serviceData;
            }

            // Si aucun service actif n'a été trouvé
            if (empty($activeServices)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aucun service actif pour cet utilisateur.'
                ]);
            }

            // Retourner la liste des services actifs avec le champ "activation"
            return response()->json([
                'status' => 'success',
                'active_services' => $activeServices
            ]);

        } catch (\Exception $e) {
            // Gérer les erreurs
            Log::error("Erreur lors de la récupération des services actifs pour l'utilisateur $userId : " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des services actifs.'
            ]);
        }
    }

}
