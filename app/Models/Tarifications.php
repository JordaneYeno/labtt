<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarifications extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'prix_sms', 'prix_whatsapp', 'prix_email'];
    public function getTransactionPrice(int $idTarif = null, $service)
    {
        $Price = Tarifications::where('id', $idTarif)->first($service)->$service;
        return $Price;
    }

    // public function getSmsPrice(int $idTarif = null)
    // {
    //     $id = $idTarif ? $idTarif : auth()->user()->tarification_id;
    //     $smsPrice = Tarifications::where('id', $id)->pluck("prix_sms")->firstOrFail();
    //     return $smsPrice;
    // } // old


    public function getSmsPrice(int $idTarif = null)
    {
        $user = User::getCurrentUSer();
        $clientId = $user->owner_id === null ? $user->id : $user->owner_id;

        $client = User::find($clientId);

        if (!$client || !$client->tarification_id) { abort(404, 'Aucune tarification trouvée pour ce client.'); }

        $idTarif = $idTarif ?: $client->tarification_id;

        $tarification = Tarifications::find($idTarif);

        if (!$tarification) { abort(404, 'Tarification non trouvée pour cet utilisateur.'); }

        // Récupérer le prix SMS de la tarification associée
        $smsPrice = $tarification->prix_sms;

        return $smsPrice;
    }

    // public function getEmailPrice(int $idTarif = null)
    // {
    //     $id = $idTarif ? $idTarif : auth()->user()->tarification_id;
    //     $emailPrice = Tarifications::where('id', $id)->pluck("prix_email")->firstOrFail();
    //     return $emailPrice;
    // } // old


    public function getEmailPrice(int $idTarif = null)
    {
        $user = User::getCurrentUSer();
        $clientId = $user->owner_id === null ? $user->id : $user->owner_id;

        $client = User::find($clientId);

        if (!$client || !$client->tarification_id) { abort(404, 'Aucune tarification trouvée pour ce client.'); }

        $idTarif = $idTarif ?: $client->tarification_id;

        $tarification = Tarifications::find($idTarif);

        if (!$tarification) { abort(404, 'Tarification non trouvée pour cet utilisateur.'); }

        // Récupérer le prix EMAIL de la tarification associée
        $emailPrice = $tarification->prix_email;

        return $emailPrice;
    }

    // public function getWhatsappPrice(int $idTarif = null)
    // {
    //     $id = $idTarif ? $idTarif : auth()->user()->tarification_id;
    //     $whatsappPrice = Tarifications::where('id', $id)->pluck("prix_whatsapp")->firstOrFail();
    //     return $whatsappPrice;
    // } // old


    public function getWhatsappPrice(int $idTarif = null)
    {
        $user = User::getCurrentUSer();
        $clientId = $user->owner_id === null ? $user->id : $user->owner_id;

        $client = User::find($clientId);

        if (!$client || !$client->tarification_id) { abort(404, 'Aucune tarification trouvée pour ce client.'); }

        $idTarif = $idTarif ?: $client->tarification_id;

        $tarification = Tarifications::find($idTarif);

        if (!$tarification) { abort(404, 'Tarification non trouvée pour cet utilisateur.'); }

        // Récupérer le prix WHATSAPP de la tarification associée
        $whatsappPrice = $tarification->prix_whatsapp;

        return $whatsappPrice;
    }

    public function getWhatsappMediaPrice($key)
    {
        $whatsappPrice = Tarifications::where('nom', $key)->pluck("prix_whatsapp")->firstOrFail();
        return $whatsappPrice;
    }

    public function getPriceList($key, $value)
    {
        $whatsappPrice = Tarifications::where('nom', $key)->pluck($value)->firstOrFail();
        return $whatsappPrice;
    }

    public function setEmailPrice($prixEmail, $id)
    {
        $emailPrice = Tarifications::where('id', $id)->firstOrFail()->update(['prix_email' => $prixEmail]);
        return $emailPrice ? response()->json([
            'status' => 'success',
            'message' => 'le prix de l\'email a été mis à jour',
            'nouveau_prix' => $prixEmail
        ], 200) :  response()->json([
            'status' => 'echec',
            'message' => 'le prix n\'a pas été mis à jour',
        ], 200);
    }

    public function setWhatsappPrice($prixWhatsapp, $id)
    {
        $whatsappPrice = Tarifications::where('id', $id)->update(['prix_whatsapp' => $prixWhatsapp]);
        return  $whatsappPrice ?
        response()->json([
            'status' => 'success',
            'message' => 'le prix d\'un message whatsapp a été mis à jour',
            'nouveau_prix' => $prixWhatsapp
        ], 200) :  response()->json([
            'status' => 'echec',
            'message' => 'le prix n\'a pas été mis à jour',
        ], 200);
    }

    public function setSmsPrice($prixSms, $id)
    {
        $smsPrice = Tarifications::where('id', $id)->firstOrFail()->update(['prix_sms' => $prixSms]);
        return $smsPrice ? response()->json([
            'status' => 'success',
            'message' => 'le prix d\'un sms a été mis à jour',
            'nouveau_prix' => $prixSms
        ], 200) :  response()->json([
            'status' => 'echec',
            'message' => 'le prix n\'a pas été mis à jour',
        ], 200);
    }
}
