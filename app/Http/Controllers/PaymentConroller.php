<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Abonnement;
use App\Models\Paiement;
use App\Models\Param;
use App\Models\Transaction;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentConroller extends Controller
{
    public function initializePayment(PaymentRequest $request)
    {
        $min = Param::where('ref', 'MINE_PRICE')->first('secret')->secret;
        if ($request->montant < $min) :
            return response()->json(['status' => 'echec', 'message' => 'le montant minimum est de ' . $min . ' Fcfa']);
        endif;

        $shuffle = str_shuffle('123456789');
        $shuffle = substr($shuffle, 0, 1);

        $_year = Carbon::now()->year;
        $_year = substr($_year, -1);
        $lasttime = Carbon::create(1970, 1, 1, 0, 0, 0)->diffInSeconds(Carbon::now());

        $refKeyGen = "K" . $_year . "C" . str_pad($shuffle + 1, 3, 'T', STR_PAD_LEFT) . $lasttime;
        $abonnementId = Abonnement::where('user_id', auth()->user()->id)
            ->pluck('id')
            ->first();

        $reference = $refKeyGen;
        $numero = $request->numero;

        $client = new Client(['verify' => false]);
        $response = $client->post('https://mypvitapi.pro/api/pvit-secure-full-api-v3.kk', [
            'form_params' => [
                'code_marchand' => $request->operateur == 'AM' ? Param::getMarchandAirtel() : Param::getMarchandMoov(),
                'montant' => $request->montant,
                'reference_marchand' => $reference,
                'numero_client' => $numero,
                'token' => Param::getTokenPvit(),
                'action' => 1,
                'service' => 'REST',
                'operateur' => $request->operateur,
                'agent' => Param::getAgent(),
            ],
        ]);

        $xmlContent = $response->getBody()->getContents();
        $xml = simplexml_load_string($xmlContent);


        $paiement = Paiement::create([
            'ref' => $xml->REF,
            'interface_id' => $xml->INTERFACEID,
            'reference_marchand' => $xml->REFERENCE_MARCHAND,
            'type' => $xml->TYPE,
            'statut' => $xml->STATUT,
            'operateur' => $xml->OPERATEUR,
            'numero_client' => $xml->NUMERO_CLIENT,
            'message' => $xml->MESSAGE,
            'tel_client' => $xml->TEL_CLIENT,
            'abonnement_id' => $abonnementId,
            'amount' => $request->montant,
        ]);

        if ($xml->STATUT != 200) :
            return response()->json(['status' => 'echec', 'message' => $xml->MESSAGE]);
        endif;

        $ref = $xml->REF[0];
        return response()->json([
            'status' => 'success',
            'message' => 'paiement initialisé',
            'reference' => $ref,
        ]);
    }

    public function receiveCallback(Request $request)
    {
        $xml = $request->getContent();
        $xmlData = simplexml_load_string($xml);
        $paiement = Paiement::where('ref', trim($xmlData->REF))->first();
        
        $abonnementID = $paiement->abonnement_id;
        if ($paiement->final_status == 0) {
            if ($xmlData->STATUT == 200) {
                (new Abonnement)->crementSolde($abonnementID, $xmlData->AMOUNT);
                $paiementUpdated = $paiement->update([
                    // 'amount' => $xmlData->AMOUNT,
                    'amount' => $paiement->amount,
                    'token' => $xmlData->TOKEN,
                    'fees' => $xmlData->FEES,
                    'final_status' => $xmlData->STATUT,
                    'num_transaction' => $xmlData->NUM_TRANSACTION,
                    'agent' => $xmlData->AGENT,
                    'final_message' => $xmlData->MESSAGE,
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'the account was credited',
                ]);
            }
        }
        return response()->json([
            'status' => 1212,
            'message' => 'steal receive',
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $ref = $request->reference;
        if (!$ref) {
            return response()->json(['status' => 'error', 'message' => 'veuillez saisir une référence']);
        }

        $paiement = Paiement::where('ref', $ref)->firstOrFail();
        $abonnement = Abonnement::where('id', $paiement->abonnement_id)->firstOrFail();
        return response()->json([
            "status" => "success",
            "message" => $paiement->final_message,
            "final_status" => $paiement->final_status,
            "solde" => $abonnement->solde,
        ], 200);
    }
}
