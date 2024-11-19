<?php

namespace App\Models;

use Brick\Math\BigInteger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime:d M Y à H:i:s',
    ];

    protected $fillable = [
        'abonnement_id', 'type', 'montant', 'reference', 'message_id', 'paiement_id', 'nouveau_solde'
    ];

    public function addTransactionAfterSendMessage($type, $amount, $messageId = null, $totalEmail, $totalSms, $totalWhatsapp, $newSolde, $paiementId = null)
    {
        try {
            $userId = auth()->user()->id;
            $abonnementId = Abonnement::where('user_id', $userId)->select('id')->first();
            $paiement = Transaction::create([
                'type' => $type,
                'abonnement_id' => $abonnementId->id,
                'montant' => $amount,
                'reference' => Str::uuid(),
                'message_id' => $messageId,
                'paiement_id' => $paiementId,
                'total_sms' => $totalSms,
                'total_email' => $totalEmail,
                'total_whatsapp' => $totalWhatsapp,
                'nouveau_solde' =>  $newSolde
            ]);
            return $paiement;
        } catch (Throwable $th) {
            throw $th;
        }
    }

    public function     __addTransactionAfterSendMessage($userId, $type, $amount, $messageId = null, $totalSend, $newSolde, $paiementId = null, $canal)
    {
        try {
            $abonnementId = Abonnement::where('user_id', $userId)->select('id')->first();
            $paiement = Transaction::create([
                'type' => $type,
                'abonnement_id' => $abonnementId->id,
                'montant' => $amount,
                'reference' => Str::uuid(),
                'message_id' => $messageId,
                'paiement_id' => $paiementId,
                'total_whatsapp' => $canal == 'whatsapp' ? $totalSend : null,
                'total_email' => $canal == 'email' ? $totalSend : null,
                'total_sms' =>  $canal == 'sms' ? $totalSend : null,
                'nouveau_solde' =>  $newSolde
            ]);
            return $paiement;
        } catch (Throwable $th) {
            throw $th;
        }
    }
}
