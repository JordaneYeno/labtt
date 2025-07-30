<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Http\Requests\EnterpriseNameRequest;
use App\Http\Requests\WhatsappRequest;
use App\Models\Abonnement;
use App\Models\Demande;
use App\Models\Paiement;
use App\Models\Param;
use App\Models\Transaction;
use App\Services\PaginationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AbonnementController extends Controller
{

    protected $mailAdmin;

    public function __construct(string $mailAdmin = null)
    {
        $this->mailAdmin = Param::getAdminEmail();
    }

    public function solde()
    {
        $abonnement = new Abonnement();
        return response()->json([
            'status' => 'success',
            'message' => 'le solde actuel',
            'solde' => $abonnement->getSolde()
        ]);
    }

    // start International
    public function getIsCustomTemplate()
    {
        $region = Abonnement::where('user_id', auth()->user()->id)->pluck('has_custom_template')->first();
        return $region;
    }

    public function setIsCustomTemplate(Request $request)
    {
        $region = Abonnement::where('user_id', auth()->user()->id)->update(['has_custom_template' => $request->value]);
        if ($region) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'template mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }
    // end International

    // start International
    public function getInternational()
    {
        $region = Abonnement::where('user_id', auth()->user()->id)->pluck('international')->first();
        return $region;
    }

    public function setInternational(Request $request)
    {
        $region = Abonnement::where('user_id', auth()->user()->id)->update(['international' => $request->value]);
        if ($region) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'region mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }
    // end International

    public function setSoldeAfterSendMessage(Request $request)
    {
        $abonnement = new Abonnement();
        return $abonnement->decreditSolde($request);
    }

    public function status()
    {
        $abonnement = new Abonnement();
        return $abonnement->getStatut();
    }


    public static function createDemande($service, $id=null, $status=null)
    {
        // $id = $id ?? auth()->id(); // si $id est null
        $id = $id ?? auth()->user()->id; // si $id est null

        // Vérifier si une demande existe déjà pour ce service et cet utilisateur
        $existingRequest = Demande::where('user_id', $id)
            ->where('service', $service)
            ->whereIn('status', [
                ServiceStatus::ACCEPTED,
                ServiceStatus::REJECTED,
                ServiceStatus::PENDING,
                ServiceStatus::RESET,
            ])  // Recherche uniquement dans les statuts acceptés, rejetés, en attente ou réinitialisés
            ->first();

        // Si aucune demande n'existe, créer une nouvelle demande
        if (!$existingRequest) {
            // Création d'une nouvelle demande en statut "En attente"
            Demande::create([
                'user_id' => $id,
                'service' => $service,
                'status' => ServiceStatus::PENDING,  // En attente
            ]);
        } else {
            // Si une demande existe déjà, mettre à jour la demande si nécessaire
            // Si la demande est acceptée, rejetée ou réinitialisée, on la remet en statut "En attente"
            if (in_array($existingRequest->status, [
                ServiceStatus::ACCEPTED,
                ServiceStatus::REJECTED,
                ServiceStatus::PENDING,
                ServiceStatus::RESET
            ])) {
                if($status == null) {
                    $existingRequest->update([
                        'status' => ServiceStatus::PENDING  // Remettre en statut "En attente" si accepté, rejeté ou réinitialisé
                    ]);

                }else{

                    $existingRequest->update([
                        'status' => ServiceStatus::REJECTED  // rejeté ou réinitialisé
                    ]);
                }
            }
        }
    }


    public function sendEnterpriseName(EnterpriseNameRequest $request)
    {
        $nomEntreprise = $request->safe()['nom_entreprise'];
        $abonnement = Abonnement::where('user_id', auth()->user()->id);
        if (!$abonnement) {
            return response()->json([
                'status' => 'echec',
                'message' => 'aucun service trouvé pour cet utilisateur'
            ], 200);
        }
        if ($abonnement->first()->sms_status == 0) {
            // $this->createDemande('sms');
            $this->createDemande('sms', null);
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


    public function sendCampagnKey(Request $request)
    {
        $codeSms = $request['code_sms'];
        $abonnement = Abonnement::where('user_id', auth()->user()->id);
        if (!$abonnement) {
            return response()->json([
                'status' => 'echec',
                'message' => 'aucun service trouvé pour cet utilisateur'
            ], 200);
        }

        if ($abonnement->first()->entreprese_name !== null) {
            return response()->json([
                'status' => 'success',
                'message' => 'la validation de votre code sms est en cours de traitement'
            ], 200);
        }

        $abonnement->update(['entreprese_name' => $codeSms]);
        // (new SendMailService)->submitMail($this->mailAdmin, 'Activation sms', Param::getEmailAwt());
        return response()->json([
            'status' => 'success',
            'sms_status' => 1,
            'message' => 'le code sms à été enregistré'
        ], 200);
    }


    public function sendEmailAddress(Request $request)
    {
        $emailClient = $request['adresse_email'];
        if (!filter_var($emailClient, FILTER_VALIDATE_EMAIL)) :
            return ['status' => 'echec', 'message' => 'veuillez saisir une adresse email valide'];
        endif;
        $abonnement = Abonnement::where('user_id', auth()->user()->id);
        if (!$abonnement) {
            return response()->json([
                'status' => 'echec',
                'message' => 'aucun service trouvé pour cet utilisateur'
            ], 200);
        }
        if ($abonnement->first()->email_status == 0) {
            // $this->createDemande('email');
            $this->createDemande('email', null);
            $abonnement->update(['email' => $emailClient, 'email_status' => 1]);
            // (new SendMailService)->submitMail($this->mailAdmin, 'Activation email', Param::getEmailAwt());
            return response()->json([
                'status' => 'success',
                'email_status' => 1,
                'message' => 'votre adresse email a été enregistré'
            ], 200);
        }
        if ($abonnement->first()->email_status == 2) {
            // (new SendMailService)->submitMail($this->mailAdmin, 'Modification email', Param::getEmailAwt());
            $abonnement->update(['email' => $emailClient, 'email_status' => 1]);
            return response()->json([
                'status' => 'success',
                'message' => 'votre nouvelle demande a été enregistrée'
            ], 200);
        }
        if ($abonnement->first()->email_status == 1) {
            return response()->json([
                'status' => 'success',
                'message' => 'votre demande de validation du votre adresse email est en cours de traitement'
            ], 200);
        }
        return ['status' => 'echec', 'message' => 'votre abonnement est déjà activé'];
    }


    public function sendWhatsappNumber(WhatsappRequest $request)
    {
        $whatsappNumber = $request['numero_whatsapp'];
        $abonnement = Abonnement::where('user_id', auth()->user()->id);

        if (!$abonnement->exists()) {
            return response()->json([
                'status' => 'echec',
                'message' => 'Aucun service trouvé pour cet utilisateur.'
            ], 200);
        }

        $abonnementData = $abonnement->first();

        // Si le service est "Réinitialisé", on vide les colonnes concernées
        if ($abonnementData->whatsapp_status == ServiceStatus::RESET) {
            // Créer une nouvelle demande d'activation
            // $this->createDemande('whatsapp');
            $this->createDemande('whatsapp', null);
            $abonnement->update([
                'whatsapp' => $whatsappNumber,
                'whatsapp_status' => ServiceStatus::PENDING // Statut en attente
            ]);

            return response()->json([
                'status' => 'success',
                'whatsapp_status' => ServiceStatus::PENDING,
                'message' => 'Votre numéro WhatsApp a été enregistré.'
            ], 200);
        }

        // Si le service est rejeté, on réinitialise
        if ($abonnementData->whatsapp_status == ServiceStatus::REJECTED) {
            $abonnement->update([
                'whatsapp' => $whatsappNumber,
                'whatsapp_status' => ServiceStatus::PENDING // Statut en attente
            ]);

            // Créer une nouvelle demande d'activation
            // $this->createDemande('whatsapp');
            $this->createDemande('whatsapp', null);

            return response()->json([
                'status' => 'success',
                'message' => 'Votre nouvelle demande a été enregistrée.'
            ], 200);
        }

        // Si le service est déjà en attente, on informe l'utilisateur
        if ($abonnementData->whatsapp_status == ServiceStatus::PENDING) {
            return response()->json([
                'status' => 'success',
                'message' => 'Votre demande de validation de votre numéro WhatsApp est en cours de traitement.'
            ], 200);
        }

        // Si le service est déjà accepté
        if ($abonnementData->whatsapp_status == ServiceStatus::ACCEPTED) {
            return response()->json([
                'status' => 'success',
                'message' => 'Votre service WhatsApp est déjà accepté.'
            ], 200);
        }
    }

    // new
    public function requestService($service, Request $request)
    {
        $userId = auth()->user()->id;

        // Vérifie si le service existe dans l'abonnement de l'utilisateur
        $abonnement = Abonnement::where('user_id', $userId)->first();

        if (!$abonnement) {
            return response()->json([
                'status' => 'echec',
                'message' => "Aucun service trouvé pour cet utilisateur."
            ], 404);
        }

        // Vérifie si la demande pour ce service existe déjà
        if ($abonnement->{$service . '_status'} == ServiceStatus::PENDING) {
            return response()->json([
                'status' => 'success',
                'message' => "Votre demande pour le service $service est déjà en attente."
            ]);
        }

        if ($abonnement->{$service . '_status'} == ServiceStatus::ACCEPTED) {
            return response()->json([
                'status' => 'success',
                'message' => "Le service $service est déjà activé."
            ]);
        }

        // Si le service est rejeté ou réinitialisé, on peut recréer la demande
        if ($abonnement->{$service . '_status'} == ServiceStatus::REJECTED || $abonnement->{$service . '_status'} == ServiceStatus::RESET) {
            // Créer une nouvelle demande pour ce service
            Demande::create([
                'user_id' => $userId,
                'service' => $service,
                'status' => ServiceStatus::PENDING,
            ]);

            // Mettre à jour l'abonnement avec le statut "En attente"
            $abonnement->update([
                $service => $request->input('numero_service', null),  // On ajoute le numéro de service, comme WhatsApp
                $service . '_status' => ServiceStatus::PENDING,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Votre demande pour le service $service a été enregistrée et est en attente."
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => "Une erreur est survenue. Veuillez réessayer."
        ]);
    }


    public function getEnterpriseName(Request $request)
    {
        $enterpriseName = Abonnement::where('user_id', auth()->user()->id)->first();
        if (!$enterpriseName) :
            return response()->json(['status' => 'success', 'message' => 'aucun nom trouvé']);
        endif;
        return response()->json([
            'status' => 'success',
            'enterprise_name' => $enterpriseName->sms,
            'sms_status' => Abonnement::setAttributes($enterpriseName->sms_status)
        ], 200);
    }

    public function getCampagnKey(Request $request)
    {
        $codeSms = Abonnement::where('user_id', auth()->user()->id)->first();
        if (!$codeSms) :
            return response()->json(['status' => 'success', 'message' => 'aucun nom trouvé']);
        endif;
        return response()->json([
            'status' => 'success',
            'code_sms' => $codeSms->entreprese_name,
            'sms_status' => Abonnement::setAttributes($codeSms->sms_status)
        ], 200);
    }


    public function getEmail(Request $request)
    {
        $email = Abonnement::where('user_id', auth()->user()->id)->first();
        if (!$email) :
            return response()->json(['status' => 'success', 'message' => 'aucun email trouvé']);
        endif;
        return response()->json([
            'status' => 'success',
            'email' => $email->email,
            'email_status' => Abonnement::setAttributes($email->email_status)
        ], 200);
    }

    public function getWhatsappNumber(Request $request)
    {
        $whatsappNumber = Abonnement::where('user_id', auth()->user()->id)->first();
        if (!$whatsappNumber) :
            return response()->json(['status' => 'success', 'message' => 'aucun numéro trouvé']);
        endif;
        return response()->json([
            'status' => 'success',
            'whatsapp_number' => $whatsappNumber->whatsapp,
            'whatsapp_status' => Abonnement::setAttributes($whatsappNumber->whatsapp_status)
        ], 200);
    }

    public function smsStatus()
    {
        $services = new Abonnement();
        try {
            return $services->getSmsStatus(auth()->user()->id);
        } catch (Throwable $th) {
            return response()->json([
                "status" => "error",
                "message error" => $th
            ]);
        }
    }

    public function emailStatus()
    {
        $services = new Abonnement();
        try {
            return $services->getEmailStatus(auth()->user()->id);
        } catch (Throwable $th) {
            return response()->json([
                "status" => "error",
                "message error" => $th
            ]);
        }
    }

    public function whatsappStatus()
    {
        $services = new Abonnement();
        try {
            return $services->getWhatsappStatus(auth()->user()->id);
        } catch (Throwable $th) {
            return response()->json([
                "status" => "error",
                "message error" => $th
            ]);
        }
    }

    public function getDebit(Request $request)
    {
        $perPage = $request->perPage ? $request->perPage : 25;
        $abonnementId = Abonnement::where('user_id', auth()->user()->id)->pluck('id')->first();
        $transaction = Transaction::where('abonnement_id', $abonnementId)
            ->leftJoin('messages', 'messages.id', 'transactions.message_id')
            ->select([
                'messages.canal',
                'messages.status',
                'messages.finish',
                'messages.created_at',
                'transactions.montant',
                'transactions.nouveau_solde',
                'messages.message',
                'transactions.created_at', 'transactions.message_id'
            ])
            ->orderBy('transactions.created_at', 'DESC')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'toutes les débit',
            'transactions' => $transaction,
        ]);
    }

    public function getCredit(Request $request)
    {
        $perPage = $request->perPage ? $request->perPage : 25;
        $abonnementId = Abonnement::where('user_id', auth()->user()->id)->pluck('id')->first();
        $paiement = Paiement::where('abonnement_id', $abonnementId)
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'toutes les transactions',
            'transactions' => $paiement,
        ]);
    }

    public function resetService(Request $request)
    {
        $abonnement = Abonnement::where('user_id', auth()->user()->id);
        $canal = $request->canal;
        $newAbonnement = $abonnement->update([$canal => '', $canal . '_status' => 0]);
        return $newAbonnement ? response()->json([
            'status' => 'success',
            'message' => 'le service ' . $canal . ' a été réinitialisé avec succes'
        ]) :
            response()->json([
                'status' => 'erreur',
                'message' => 'le service n\'a pas été réinitialisé avec succes'
            ]);
    }

    public function creditAccount(Request $request)
    {
        $abonnement = Abonnement::where('user_id', $request->user_id)->first();
        $credit = $abonnement->increment('solde', $request->montant);
        $paiement = Paiement::create([
            'ref' => Str::random(16),
            'interface_id' => "NA",
            'reference_marchand' => 'NA',
            'type' => 'backoffice',
            'statut' => 200,
            'operateur' => 'NA',
            'numero_client' => 'NA',
            'message' => 'NA',
            'tel_client' => 'NA',
            'abonnement_id' => $abonnement->id,
            'amount' => $request->montant
        ]);
        return $abonnement ?
            response()->json(['status' => 'success', 'message' => 'le compte a été crédité!']) :
            response()->json(['status' => 'echec', 'message' => 'le compte n\'a pas été crédité!']);
    }

    public function decreditAccount(Request $request)
    {
        if ((new Abonnement)->getSolde() == 0) {
            return response()->json(['status' => 'echec', 'message' => 'le solde est à 0!']);
        };
        $abonnement = Abonnement::where('user_id', $request->user_id)->decrement('solde', $request->montant);
        return $abonnement ?
            response()->json(['status' => 'success', 'message' => 'le compte a été débité!']) :
            response()->json(['status' => 'echec', 'message' => 'le compte n\'a pas été débité!']);
    }

    public function listDemande()
    {
        return Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
            ->select('demandes.service', 'users.name', 'users.email', 'users.phone')
            ->where('demandes.status', ServiceStatus::PENDING)
            // ->orWhere('demandes.status', 2)
            ->orWhere('demandes.status', ServiceStatus::REJECTED)
            ->orderBy('demandes.created_at', 'DESC')
            ->paginate(25);
    } // old


    public function getDemandesByService($service)
    {
        // Récupérer les demandes par service avec une jointure sur la table 'users' et 'abonnement'
        $demandes = Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
            ->leftJoin('abonnements', 'abonnements.user_id', '=', 'demandes.user_id')  // Jointure sur la table abonnements
            ->select(
                'demandes.id as demande_id',  // Récupérer l'ID de la demande
                'demandes.service',           // Le service de la demande
                'demandes.status',            // Le statut de la demande
                'users.id as user_id',        // ID de l'utilisateur
                'users.name',                 // Nom de l'utilisateur
                'users.email',                // Email de l'utilisateur
                'users.phone',                // Téléphone de l'utilisateur
                'abonnements.whatsapp',       // Colonne whatsapp de la table abonnements
                'abonnements.sms',            // Colonne sms de la table abonnements
                'abonnements.email as abonnement_email'  // Colonne email de la table abonnements
            )
            ->where('demandes.service', $service)  // Filtrer par service
            ->whereIn('demandes.status', [ServiceStatus::PENDING, ServiceStatus::REJECTED])  // Filtrer les statuts
            ->orderBy('demandes.created_at', 'DESC')  // Trier par date de création
            ->paginate(10);  // Pagination à 10 demandes par page

        // Structurer la réponse pour avoir l'objet "user" et "abonnement" imbriqué
        $demandes->getCollection()->transform(function ($demande) use ($service) {
            // Ajouter les informations de l'utilisateur dans l'objet 'user'
            $demande->client = [
                'id' => $demande->user_id,
                'name' => $demande->name,
                'email' => $demande->email,
                // 'phone' => $demande->phone,
            ];

            // Filtrer les informations d'abonnement en fonction du service
            switch ($service) {
                case 'whatsapp':
                    $demande->request_submit = [
                        'whatsapp' => $demande->whatsapp,
                    ];
                    break;

                case 'sms':
                    $demande->request_submit = [
                        'sms' => $demande->sms,
                    ];
                    break;

                case 'email':
                    $demande->request_submit = [
                        'email' => $demande->abonnement_email,
                    ];
                    break;

                default:
                    $demande->request_submit = null;
                    break;
            }

            // Supprimer les champs inutiles qui ont été intégrés dans 'user' et 'abonnement'
            unset($demande->user_id, $demande->name, $demande->email, $demande->phone);
            unset($demande->whatsapp, $demande->sms, $demande->abonnement_email);

            return $demande;
        });

        // Retourner la réponse avec la pagination et les informations imbriquées
        return response()->json([
            'status' => 'success',
            'message' => 'Demandes récupérées avec succès',
            'data' => $demandes,  // Contient les demandes paginées avec les informations utilisateurs et abonnements imbriquées
        ], 200);
    }

    /**
     *  version !1! du filtre
    */

    // public function getDemandesByService_v2($service, $status)
    // {
    //     // Vérifier que le statut est valide (sauf si 'all' est passé)
    //     $validStatuses = [
    //         ServiceStatus::PENDING,
    //         ServiceStatus::ACCEPTED,
    //         ServiceStatus::REJECTED,
    //         ServiceStatus::RESET,
    //     ];

    //     if ($status !== 'all' && !in_array($status, $validStatuses)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Statut de demande invalide.',
    //         ], 400);
    //     }

    //     // Construire la requête de base
    //     $query = Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
    //         ->leftJoin('abonnements', 'abonnements.user_id', '=', 'demandes.user_id')
    //         ->select(
    //             'demandes.id as demande_id',
    //             'demandes.service',
    //             'demandes.status',
    //             'users.id as user_id',
    //             'users.name',
    //             'users.email',
    //             'users.phone',
    //             'abonnements.whatsapp',
    //             'abonnements.sms',
    //             'abonnements.email as abonnement_email'
    //         );

    //     // Si le service n'est pas 'all', appliquer le filtre sur le service
    //     if ($service !== 'all') {
    //         $query->where('demandes.service', $service);
    //     }

    //     // Si le statut n'est pas 'all', appliquer le filtre sur le statut
    //     if ($status !== 'all') {
    //         $query->where('demandes.status', $status);
    //     }

    //     // Paginée les résultats
    //     $demandes = $query->orderBy('demandes.created_at', 'DESC')->paginate(10);

    //     // Transformation des données
    //     $demandes->getCollection()->transform(function ($demande) use ($service) {
    //         $demande->client = [
    //             'id' => $demande->user_id,
    //             'name' => $demande->name,
    //             'email' => $demande->email,
    //         ];

    //         // Ajouter les infos du service demandé
    //         switch ($service) {
    //             case 'whatsapp':
    //                 $demande->request_submit = ['whatsapp' => $demande->whatsapp];
    //                 break;
    //             case 'sms':
    //                 $demande->request_submit = ['sms' => $demande->sms];
    //                 break;
    //             case 'email':
    //                 $demande->request_submit = ['email' => $demande->abonnement_email];
    //                 break;
    //             default:
    //                 $demande->request_submit = null;
    //         }

    //         unset($demande->user_id, $demande->name, $demande->email, $demande->phone);
    //         unset($demande->whatsapp, $demande->sms, $demande->abonnement_email);

    //         return $demande;
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Demandes récupérées avec succès.',
    //         'data' => $demandes,
    //     ]);
    // }


    /**
     *  version !old! du filtre
    */

    // public function getDemandesByService_v2($service, $status)
    // {
    //     // Vérifier que le statut est valide
    //     $validStatuses = [
    //         ServiceStatus::PENDING,
    //         ServiceStatus::ACCEPTED,
    //         ServiceStatus::REJECTED,
    //         ServiceStatus::RESET,
    //     ];

    //     if (!in_array($status, $validStatuses)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Statut de demande invalide.',
    //         ], 400);
    //     }

    //     // Récupérer les demandes avec les infos de l'utilisateur et de l'abonnement
    //     $demandes = Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
    //         ->leftJoin('abonnements', 'abonnements.user_id', '=', 'demandes.user_id')
    //         ->select(
    //             'demandes.id as demande_id',
    //             'demandes.service',
    //             'demandes.status',
    //             'users.id as user_id',
    //             'users.name',
    //             'users.email',
    //             'users.phone',
    //             'abonnements.whatsapp',
    //             'abonnements.sms',
    //             'abonnements.email as abonnement_email'
    //         )
    //         ->where('demandes.service', $service)
    //         ->where('demandes.status', $status)
    //         ->orderBy('demandes.created_at', 'DESC')
    //         ->paginate(10);

    //     // Transformation des données
    //     $demandes->getCollection()->transform(function ($demande) use ($service) {
    //         $demande->client = [
    //             'id' => $demande->user_id,
    //             'name' => $demande->name,
    //             'email' => $demande->email,
    //         ];

    //         // Ajouter les infos du service demandé
    //         switch ($service) {
    //             case 'whatsapp':
    //                 $demande->request_submit = ['whatsapp' => $demande->whatsapp];
    //                 break;
    //             case 'sms':
    //                 $demande->request_submit = ['sms' => $demande->sms];
    //                 break;
    //             case 'email':
    //                 $demande->request_submit = ['email' => $demande->abonnement_email];
    //                 break;
    //             default:
    //                 $demande->request_submit = null;
    //         }

    //         unset($demande->user_id, $demande->name, $demande->email, $demande->phone);
    //         unset($demande->whatsapp, $demande->sms, $demande->abonnement_email);

    //         return $demande;
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Demandes récupérées avec succès.',
    //         'data' => $demandes,
    //     ]);
    // }


    // public function getDemandesByService_v2($service, $status)
    // {
    //     // Récupérer les paramètres de pagination depuis la requête
    //     $page = request('page', 1);  // Page courante (défaut: 1)
    //     $perPage = request('perPage', 10);  // Nombre d'éléments par page (défaut: 10)

    //     // Vérification du statut si ce n'est pas 'all'
    //     $validStatuses = [
    //         ServiceStatus::PENDING,
    //         ServiceStatus::ACCEPTED,
    //         ServiceStatus::REJECTED,
    //         ServiceStatus::RESET,
    //     ];

    //     if ($status !== 'all' && !in_array($status, $validStatuses)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Statut de demande invalide.',
    //         ], 400);
    //     }

    //     // Construire la requête de base
    //     $query = Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
    //         ->leftJoin('abonnements', 'abonnements.user_id', '=', 'demandes.user_id')
    //         ->select(
    //             'demandes.id as demande_id',
    //             'demandes.service',
    //             'demandes.status',
    //             'users.id as user_id',
    //             'users.name',
    //             'users.email',
    //             'users.phone',
    //             'abonnements.whatsapp',
    //             'abonnements.sms',
    //             'abonnements.email as abonnement_email'
    //         );

    //     // Si le service n'est pas 'all', on applique le filtre sur le service
    //     if ($service !== 'all') {
    //         $query->where('demandes.service', $service);
    //     }

    //     // Si le statut n'est pas 'all', on applique le filtre sur le statut
    //     if ($status !== 'all') {
    //         $query->where('demandes.status', $status);
    //     } else {
    //         // Si le statut est 'all', on récupère toutes les demandes pour tous les statuts possibles
    //         $query->whereIn('demandes.status', $validStatuses);
    //     }

    //     // Paginée les résultats
    //     $demandes = $query->orderBy('demandes.created_at', 'DESC')->paginate(10);

    //     // Transformation des données
    //     $demandes->getCollection()->transform(function ($demande) use ($service) {
    //         $demande->client = [
    //             'id' => $demande->user_id,
    //             'name' => $demande->name,
    //             'email' => $demande->email,
    //         ];

    //         // Ajouter les infos du service demandé
    //         switch ($demande->service) {
    //             case 'whatsapp':
    //                 $demande->request_submit = ['whatsapp' => $demande->whatsapp];
    //                 break;
    //             case 'sms':
    //                 $demande->request_submit = ['sms' => $demande->sms];
    //                 break;
    //             case 'email':
    //                 $demande->request_submit = ['email' => $demande->abonnement_email];
    //                 break;
    //             default:
    //                 $demande->request_submit = null;
    //         }

    //         unset($demande->user_id, $demande->name, $demande->email, $demande->phone);
    //         unset($demande->whatsapp, $demande->sms, $demande->abonnement_email);

    //         return $demande;
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Demandes récupérées avec succès.',
    //         'data' => $demandes,
    //     ]);
    // }




    public function getDemandesByService_v2($service, $status)
    {
        // Récupérer les paramètres de pagination depuis la requête
        $page = request('page', 1);  // Page courante (défaut: 1)
        $perPage = request('perPage', 10);  // Nombre d'éléments par page (défaut: 10)

        // Vérification du statut si ce n'est pas 'all'
        $validStatuses = [
            ServiceStatus::PENDING,
            ServiceStatus::ACCEPTED,
            ServiceStatus::REJECTED,
            ServiceStatus::RESET,
        ];

        if ($status !== 'all' && !in_array($status, $validStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Statut de demande invalide.',
            ], 400);
        }

        // Construire la requête de base
        $query = Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
            ->leftJoin('abonnements', 'abonnements.user_id', '=', 'demandes.user_id')
            ->select(
                'demandes.id as demande_id',
                'demandes.service',
                'demandes.status',
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.phone',
                'abonnements.whatsapp',
                'abonnements.sms',
                'abonnements.email as abonnement_email'
            );

        // Si le service n'est pas 'all', on applique le filtre sur le service
        if ($service !== 'all') {
            $query->where('demandes.service', $service);
        }

        // Si le statut n'est pas 'all', on applique le filtre sur le statut
        if ($status !== 'all') {
            $query->where('demandes.status', $status)/*->where('demandes.status', '!=', 0)*/;

        } else {
            // Si le statut est 'all', on récupère toutes les demandes pour tous les statuts possibles
            $query->whereIn('demandes.status', $validStatuses)->where('demandes.status', '!=', ServiceStatus::RESET);
        }

        // Récupérer toutes les données sans pagination
        $demandes = $query->orderBy('demandes.created_at', 'DESC')->get(); // Utiliser `get()` pour récupérer toutes les demandes

        // Appliquer la pagination manuellement à la collection
        $paginate = new PaginationService();
        $paginatedDemandes = $paginate->paginateCollection($demandes, $perPage);

        // Transformation des données
        $paginatedDemandes->getCollection()->transform(function ($demande) use ($service) {
            $demande->client = [
                'id' => $demande->user_id,
                'name' => $demande->name,
                'email' => $demande->email,
            ];

            // Ajouter les infos du service demandé
            switch ($demande->service) {
                case 'whatsapp':
                    $demande->request_submit = ['whatsapp' => $demande->whatsapp];
                    break;
                case 'sms':
                    $demande->request_submit = ['sms' => $demande->sms];
                    break;
                case 'email':
                    $demande->request_submit = ['email' => $demande->abonnement_email];
                    break;
                default:
                    $demande->request_submit = null;
            }

            unset($demande->user_id, $demande->name, $demande->email, $demande->phone);
            unset($demande->whatsapp, $demande->sms, $demande->abonnement_email);

            return $demande;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Demandes récupérées avec succès.',
            'data' => $paginatedDemandes,
        ]);
    }


    public function listDemandeReject()
    {
        return Demande::leftJoin('users', 'users.id', '=', 'demandes.user_id')
            ->select('demandes.service', 'users.name', 'users.email', 'users.phone')
            ->where('demandes.status', 2)
            ->orderBy('demandes.created_at', 'DESC')
            ->paginate(25);
    }

    public function saveLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|file|mimes:jpeg,png,jpg,webp|max:2048', // Types de fichiers autorisés et taille maximale de 2 Mo (2048 kilo-octets)
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'status' => 'error'], 400);
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $mime = $file->getMimeType();
            if (in_array($mime, ['application/sql', 'text/x-sql'])) {
                return response()->json(['status' => 'error', 'message' => 'Les fichiers SQL ne sont pas autorisés.']);
            }

            Storage::disk('local')->exists(Abonnement::getLogo()) ? Storage::delete(Abonnement::getLogo()) : null;
            $filename = uniqid() . '.' . $extension;
            $path = $file->storeAs('public/banner/logo/' . auth()->user()->id, $filename);
            $lien = str_replace('public/banner/', '/', $path);

            $abonnement = Abonnement::where('user_id', auth()->user()->id)->update(['logo' => $lien]);
            return response()->json(['status' => 'success', 'message' => 'logo modifié avec succès', 'path' => $lien]);
        }
        return response()->json(['status' => 'echec', 'message' => 'aucun fichier envoyé']);
    }

    public function getThemeColor()
    {
        $color = Abonnement::where('user_id', auth()->user()->id)->pluck('cs_color')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'couleur theme',
            'color' => $color
        ]);
    }

    public function setThemeColor(Request $request)
    {
        $color = Abonnement::where('user_id', auth()->user()->id)->update(['cs_color' => $request->value]);
        if ($color) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'couleur info mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function getLogo()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'lien du logo',
            'lien' => Abonnement::getLogo()
        ]);
    }

    public function getContact()
    {
        $contact = Abonnement::where('user_id', auth()->user()->id)->pluck('entreprese_contact')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'contact entreprise',
            'contact' => $contact
        ]);
    }

    public function setContact(Request $request)
    {
        $contact = Abonnement::where('user_id', auth()->user()->id)->update(['entreprese_contact' => $request->value]);
        if ($contact) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'contact info mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function getLocation()
    {
        $location = Abonnement::where('user_id', auth()->user()->id)->pluck('entreprese_localisation')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'location entreprise',
            'location' => $location
        ]);
    }

    public function setLocation(Request $request)
    {
        $location = Abonnement::where('user_id', auth()->user()->id)->update(['entreprese_localisation' => $request->value]);
        if ($location) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'localisation info mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function getCity()
    {
        $ville = Abonnement::where('user_id', auth()->user()->id)->pluck('entreprese_ville')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'ville entreprise',
            'city' => $ville
        ]);
    }

    public function setCity(Request $request)
    {
        $ville = Abonnement::where('user_id', auth()->user()->id)->update(['entreprese_ville' => $request->value]);
        if ($ville) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'ville info mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }
}
