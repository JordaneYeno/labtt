<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnterpriseNameRequest;
use App\Http\Requests\WhatsappRequest;
use App\Models\Abonnement;
use App\Models\Demande;
use App\Models\Paiement;
use App\Models\Param;
use App\Models\Transaction;
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

    public function createDemande($service)
    {
        Demande::create([
            'service' => $service,
            'status' => 1,
            'user_id' => auth()->user()->id
        ]);
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
            $this->createDemande('email');
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
        if (!$abonnement) {
            return response()->json([
                'status' => 'echec',
                'message' => 'aucun service trouvé pour cet utilisateur'
            ], 200);
        }
        if ($abonnement->first()->whatsapp_status == 0) {
            $this->createDemande('whatsapp');
            $abonnement->update(['whatsapp' => $whatsappNumber, 'whatsapp_status' => 1]);
            // (new SendMailService)->submitMail($this->mailAdmin, 'Activation whatsapp', Param::getEmailAwt());
            return response()->json([
                'status' => 'success',
                'whatsapp_status' => 1,
                'message' => 'votre numéro whatsapp a été enregistré'
            ], 200);
        }
        if ($abonnement->first()->whatsapp_status == 2) {
            $abonnement->update(['whatsapp' => $whatsappNumber, 'whatsapp_status' => 1]);
            // (new SendMailService)->submitMail($this->mailAdmin, 'Modification whatsapp', Param::getEmailAwt());
            return response()->json([
                'status' => 'success',
                'message' => 'votre nouvelle demande a été enregistrée'
            ], 200);
        }
        if ($abonnement->first()->whatsapp_status == 1) {
            return response()->json([
                'status' => 'success',
                'message' => 'votre demande de validation de votre numéro whatsapp est en cours de traitement'
            ], 200);
        }
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
            ->where('demandes.status', 1)
            ->orWhere('demandes.status', 2)
            ->orderBy('demandes.created_at', 'DESC')
            ->paginate(25);
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
