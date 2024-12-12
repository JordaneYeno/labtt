<?php

namespace App\Models;

use App\Services\SendMailService;
use App\Services\SmsCount;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

use function PHPUnit\Framework\isNull;

class Abonnement extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime:d M Y à h:i:s',
        'status' => 'integer',
        'whatsapp_status' => 'integer',
        'email_status' => 'integer',
        'sms_status' => 'integer',
    ];

    protected $fillable = ['user_id'];
    
    public static function getAbo($id)
    {
        return Abonnement::where('user_id', $id)->first();
    }

    public static function getAbonnement(Request $request)
    {
        $id = $request->id ? $request->id : auth()->user()->id;
        return Abonnement::where('user_id', $id)->firstOrFail();
    }

    public static function getLogo()
    {
        $logo = Abonnement::where('user_id', auth()->user()->id)->pluck('logo')->first();
        $url = route('users.profile', ['id' => auth()->user()->id]);
        return $logo === null ? $logo : $url;
    }

    public static function getCurrentWassengerDeviceWithoutAuth($userId)
    {
        $deviceSecret = Abonnement::where('user_id', $userId)->pluck('wa_device_secret')->first();
        return $deviceSecret;
    }

    public static function getCurrentWassengerDevice()
    {
        $deviceSecret = Abonnement::where('user_id', auth()->user()->id)->pluck('wa_device_secret')->first();
        return $deviceSecret;
    }

    public function getWaDeviceClient()
    {
        $user = Abonnement::where('user_id', auth()->user()->id)->pluck('wa_device_secret')->first();
        return $user;
    }

    public function setWaDeviceClient(Request $request)
    {
        $device = Abonnement::where('user_id', auth()->user()->id)->update(['wa_device_secret' => $request->value]);
        if ($device) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'device mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function isAdminGetWaDeviceClient($id)
    {
        $user = Abonnement::where('user_id', $id)->pluck('wa_device_secret')->first();
        return $user;
    }

    public function isAdminSetWaDeviceClient($id, Request $request)
    {
        $device = Abonnement::where('user_id', $id)->update(['wa_device_secret' => $request->value]);
        if ($device) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'device mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function isAdminGetCashClient($id)
    {
        $user = Abonnement::where('user_id', $id)->pluck('solde')->first();
        return $user;
    }

    public function isAdminSetCashClient($id, Request $request)
    {
        $device = Abonnement::where('user_id', $id)->update(['solde' => $request->value]);
        if ($device) {
            return response()->json([
                'status' => 'success',
                'update' => 1,
                'message' => 'solde mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getInternaltional($userId)
    {
        $userId === null ? auth()->user()->id : $userId;
        $international = Abonnement::where('user_id', $userId)->pluck('international')->first();
        return $international;
    }

    public static function getSolde($abonnementId = null)
    {
        $solde = Abonnement::where('user_id', auth()->user()->id)->pluck('solde')->first();
        return $solde;
    }

    public static function __getSolde($userId, $abonnementId = null)
    {
        $userId === null ? auth()->user()->id : $userId;
        $solde = Abonnement::where('user_id', $userId)->pluck('solde')->first();
        return $solde;
    }

    public static function getGridPricing($userId, $abonnementId = null)
    {
        $userId === null ? auth()->user()->id : $userId;
        $pricing = User::where('id', $userId)->pluck('tarification_id')->first();
        return $pricing;
    }

    public function setSlugGenerate()
    {
        $slug = Abonnement::where('user_id', auth()->user()->id)->first();
        if (isset($slug)) {
            $slug->update(['slug' => Str::random(32)]);
            return response()->json([
                'status' => 'succes',
                'message' => "nouveau slug",
                'change' => 1,
            ], 200);
        }
    }

    public function getStatut()
    {
        $status = Abonnement::where('user_id', auth()->user()->id)->pluck('status')->first();
        return $status;
    }

    public function __getStatut($usrid)
    {
        $status = Abonnement::where('user_id', $usrid)->pluck('status')->first();
        return $status;
    }

    public static function getEnterpriseName()
    {
        return Abonnement::where('user_id', auth()->user()->id)->pluck('sms')->first();
    }

    public static function getWhatsappNumber()
    {
        return Abonnement::where('user_id', auth()->user()->id)->pluck('whatsapp')->first();
    }

    public static function getEmail()
    {
        return Abonnement::where('user_id', auth()->user()->id)->pluck('email')->first();
    }

    public function creditSolde($request)
    {
        $xmlData = $request->getContent();
        $xml = simplexml_load_string($xmlData);
        return response()->json([
            '$xmlData'
        ]);
        if ($xmlData->STATUT == 200) {
            $transaction = Paiement::where('ref', $xml->REF)->update([
                'amount' => $xmlData->AMOUNT,
                'token' => $xmlData->TOKEN,
                'fees' => $xmlData->FEES ? $xmlData->FEES : 0,
                'statut' => $xmlData->STATUT
            ]);
        }
    }

    public function decreditSolde($amount)
    {
        try {
            $userId = auth()->user()->id;
            $solde = Abonnement::where('user_id', $userId)->decrement('solde', $amount);
            $newSolde = $this->getSolde();
            return $solde ? [
                'status' => 'success',
                'message' => 'le compte à été débité',
                'current amount' => $newSolde
            ] : "aucun résultat trouvé";
        } catch (Exception $th) {
            return response()->json([
                'status' => 'erreur',
                'message error' => 'une erreur interne est arrivée'
            ]);
        }
    }

    public function crementSolde($abonnementId, $amount)
    {
        try {
            $abonnement = DB::table('abonnements')->where('id', $abonnementId);
            if ($abonnement) :
                $abonnement->increment('solde', json_decode($amount));
                return true;;
            endif;
            return false;
        } catch (Exception $th) {
            return response()->json([
                'status' => 'error',
                'message error' => 'internal server error'
            ], 500);
        }
    }

    public static function setAttributes($status)
    {
        switch ($status) {
            case 0:
                $status = 'aucune demande';
                break;
            case 1:
                $status = 'en attente';
                break;
            case 2:
                $status = 'rejeté';
                break;
            case 3:
                $status = 'accepté';
                break;
        }
        return $status;
    }
    public function getSmsStatus($requestUserId)
    {
        $userId = $requestUserId;
        $abonnement = Abonnement::where('user_id', $userId)->select('sms_status')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'statut du sms récupéré',
            'sms_status' => $this->setAttributes($abonnement->sms_status)
        ]);
    }

    public function getWhatsappStatus($requestUserId)
    {
        $userId = $requestUserId;
        $abonnement = Abonnement::where('user_id', $userId)->select('whatsapp_status')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'statut de whatsapp récupéré',
            'whatsapp_status' => $this->setAttributes($abonnement->whatsapp_status)
        ]);
    }

    public function getEmailStatus($requestUserId)
    {
        $userId = $requestUserId;
        $abonnement = Abonnement::where('user_id', $userId)->select('email_status')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'statut de l\'email récupéré',
            'email_status' => $this->setAttributes($abonnement->email_status)
        ]);
    }

    public static function getEmailAwt()
    {
        return Param::where('ref', 'EMAIL_AWT')->first('secret')->secret;
    }

    public function acceptSms($requestUserId)
    {
        $userId = $requestUserId;
        $smsStatuts = Abonnement::where(['user_id' => $userId, 'sms_status' => 1])
            ->update(['sms_status' => 3, 'status' => 1]);

        if ($smsStatuts == 1) {
            $data = [
                'email' => (User::getUser($requestUserId))->email,
                'from' => $this->getEmailAwt(),
                'title' => 'Activation service de messagerie SMS',
                'decision' => 'accept',
                'canal' => 'de SMS',
                'name' => (User::getUser($requestUserId))->name,
            ];

            (new SendMailService)->decisionMail(
                $data['email'],
                $data['title'],
                $data['decision'],
                $data['from'],
                $data['canal'],
                $data['name']
            );
        }

        // return ['error' => 'Échec de la mise à jour du statut SMS'];
        return [
            'message' => $smsStatuts ? "le nom de l'entreprise à été accepté" : "aucun service trouvé",
            'status' => $smsStatuts ? "success" : "echec"
        ];
    }

    public function acceptWhatsapp($requestUserId)
    {
        $userId = $requestUserId;
        $whatsappStatuts = Abonnement::where(['user_id' => $userId, 'whatsapp_status' => 1])
            ->update(['whatsapp_status' => 3, 'status' => 1]);
        if ($whatsappStatuts == 1) : (new SendMailService)->decisionMail((User::getUser($requestUserId))->email, 'Activation service de messagerie Whatsapp', 'accept', $this->getEmailAwt(), 'de whatsapp', (User::getUser($requestUserId))->name);;
        endif;
        return [
            'message' => $whatsappStatuts ? "le numéro whatsapp a été accepté" : "aucun service trouvé",
            'status' => $whatsappStatuts ? "success" : "echec"
        ];
    }

    public function acceptEmail($requestUserId)
    {
        $userId = $requestUserId;
        $emailStatuts = Abonnement::where(['user_id' => $userId, 'email_status' => 1])
            ->update(['email_status' => 3, 'status' => 1]);
        if ($emailStatuts == 1) : (new SendMailService)->decisionMail((User::getUser($requestUserId))->email, 'Activation service d\'emailing', 'accept', $this->getEmailAwt(), 'd\'email', (User::getUser($requestUserId))->name,);;
        endif;
        return [
            'message' => $emailStatuts ? "l'adresse email a été acceptée" : "aucun service trouvé",
            'status' => $emailStatuts ? "success" : "echec"
        ];
    }

    public function rejectSms($requestUserId)
    {
        $userId = $requestUserId;
        $smsStatuts = Abonnement::where(['user_id' => $userId, 'sms_status' => 1])
            ->update(['sms_status' => 0, 'sms' => NULL]);
        $service = Abonnement::where('user_id', $userId)->get();
        if ($smsStatuts) : (new SendMailService)->decisionMail((User::getUser($requestUserId))->email, 'Activation service de messagerie sms', 'reject', $this->getEmailAwt(), 'de sms', (User::getUser($requestUserId))->name,);;
        endif;
        return [
            'message' => $smsStatuts ? "le nom de l'entreprise à été rejeté" : "aucun service trouvé",
            'status' => $smsStatuts ? "success" : "echec"
        ];
    }

    public function rejectWhatsapp($requestUserId)
    {
        $userId = $requestUserId;
        // return $requestUserId;
        $whatsappStatuts = Abonnement::where('user_id', $userId)
            ->update(['whatsapp_status' => 0, 'whatsapp' => NULL]);
        $service = Abonnement::where('user_id', $userId)->get();
        if ($whatsappStatuts) : (new SendMailService)->decisionMail((User::getUser($requestUserId))->email, 'Activation service de messagerie Whatsapp', 'accept', $this->getEmailAwt(), 'de whatsapp', (User::getUser($requestUserId))->name,);;
        endif;
        return [
            'message' => $whatsappStatuts ? "le numéro whatsapp a été rejeté" : "aucun service trouvé",
            'status' => $whatsappStatuts ? "success" : "echec"
        ];
    }

    public function rejectEmail($requestUserId)
    {
        $userId = $requestUserId;
        $emailStatuts = Abonnement::where(['user_id' => $userId, 'email_status' => 1])
            ->update(['email_status' => 0, 'email' => NULL]);
        $service = Abonnement::where('user_id', $userId)->get();
        if ($emailStatuts) : (new SendMailService)->decisionMail((User::getUser($requestUserId))->email, 'Activation service d\'emailing', 'accept', $this->getEmailAwt(), 'd\'email', (User::getUser($requestUserId))->name,);;
        endif;
        return [
            'message' => $emailStatuts ? "l'adresse email a été rejeté" : "aucun service trouvé",
            'status' => $emailStatuts ? "success" : "echec"
        ];
    }

    public static function creditWhatsapp($destinataires, $messageId)
    {
        if (User::isSuperAdmin()) : return  null;
        endif;
        $user = auth()->user();
        $totalcredit = (new Tarifications)->getWhatsappPrice() * $destinataires;
        $credit = Message::where('user_id', $user->id)->where('id', $messageId)->first();

        if ($credit) {
            $credit->credit += $totalcredit;
            $credit->save();
        }
    }

    public static function creditEmail($destinataires, $messageId)
    {
        if (User::isSuperAdmin()) : return  null;
        endif;
        $user = auth()->user();
        $totalcredit = (new Tarifications)->getEmailPrice() * $destinataires;
        $credit = Message::where('user_id', $user->id)->where('id', $messageId)->first();

        if ($credit) {
            $credit->credit += $totalcredit;
            $credit->save();
        }
    }

    public static function creditSms($destinataires, $messageId)
    {
        if (User::isSuperAdmin()) : return  null;
        endif;
        $user = auth()->user();
        $totalcredit = (new Tarifications)->getSmsPrice() * $destinataires;
        $credit = Message::where('user_id', $user->id)->where('id', $messageId)->first();

        if ($credit) {
            $credit->credit += $totalcredit;
            $credit->save();
        }
    }

    public static function factureEmail($destinataires, $totalSold, $messageId)
    {
        if (User::isSuperAdmin()) : return  null;
        endif;
        $user = auth()->user();
        $totalSold = (new Tarifications)->getEmailPrice() * $destinataires;
        $solde = Abonnement::where('user_id', $user->id)->decrement('solde', $totalSold);
        return $solde;
    }

    public static function factureSms($destinataires, $totalSold, $messageId, $message)
    {
        if (User::isSuperAdmin()) : return  null;
        endif;
        $smsCount = (new SmsCount)->countSmsSend(strip_tags($message));
        $user = auth()->user();
        $totalSold = ((new Tarifications)->getSmsPrice() * $smsCount) * $destinataires;
        $solde = Abonnement::where('user_id', $user->id)->decrement('solde', $totalSold);
        return $solde;
    }

    public static function factureWhatsapp($destinataires, $totalSold, $messageId)
    {
        if (User::isSuperAdmin()) : return  null;
        endif;
        $user = auth()->user();
        $totalSold = (new Tarifications)->getWhatsappPrice() * $destinataires;
        $solde = Abonnement::where('user_id', $user->id)->decrement('solde', $totalSold);
        return $solde;
    }

    public static function __factureNotification($destinataires, $totalSold, $messageId, $roleUser, $userID, $tarifId, $pricing, $myMessage, $isSms)
    {
        if (User::__isSuperAdmin($roleUser)) : return  null;
        endif;
        $smsCount = (new SmsCount)->countSmsSend(strip_tags($myMessage));
        $totalSold = (new Tarifications)->getTransactionPrice($tarifId, $pricing) * $destinataires;
        $isSms === null
            ?
            $solde = Abonnement::where('user_id', $userID)->decrement('solde', $totalSold)
            :
            $totalSold = ((new Tarifications)->getTransactionPrice($tarifId, $pricing) * $smsCount) * $destinataires;
        $solde = Abonnement::where('user_id', $userID)->decrement('solde', $totalSold);
        return $solde;
    }
}
