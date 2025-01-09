<?php

namespace App\Http\Controllers;

use App\Http\Requests\TarificationRequest;
use App\Models\Tarifications;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\PriceService;
use App\Services\SmsCount;
use Exception;
use Illuminate\Http\Request;

class TarificationController extends Controller
{

    public function getPricingClient (Request $request)
    {   
        if(isset($request->countSms)){
            $smsCount = (new SmsCount)->countSmsSend(strip_tags($request->countSms));
            return response()->json([
                'price_sms' => (new Tarifications)->getPriceList('default','prix_sms') * $smsCount,
                'price_email' => (new Tarifications)->getPriceList('default','prix_email'),
                'price_whatsapp' => (new Tarifications)->getPriceList('default','prix_whatsapp'),
                'price_media' => (new Tarifications)->getPriceList('media','prix_whatsapp')
            ]);
        }else{
            return response()->json([
                'price_email' => (new Tarifications)->getPriceList('default','prix_email'),
                'price_whatsapp' => (new Tarifications)->getPriceList('default','prix_whatsapp'),
                'price_media' => (new Tarifications)->getPriceList('media','prix_whatsapp')
            ]);
        }
    }

    public function getSmsPrice (Request $request)
    {
        $priceService = new Tarifications();
        return $priceService->getSmsPrice($request->id);
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'tarification du sms',
        //     'tarif' => ''
        // ]);
    }

    public function getWhatsappPrice (Request $request)
    {
        $priceService = new Tarifications();
        return $priceService->getWhatsappPrice($request->id);
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'tarification du message whatsapp',
        //     'tarif' => ''
        // ]);
    }
    
    public function getEmailPrice (Request $request)
    {
        $priceService = new Tarifications();
        return $priceService->getEmailPrice($request->id);
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'tarification de l\'email',
        //     'tarif' => ''
        // ]);
    }
    public function setSmsPrice(Request $request)
    {
        $price = new Tarifications();
        return $price->setSmsPrice($request->amount, $request->id);
    }

    public function setEmailPrice(Request $request)
    {
        $price = new Tarifications();
        return $price->setEmailPrice($request->amount, $request->id);
    }

    public function setWhatsappPrice(Request $request)
    {
        $price = new Tarifications();
        return $price->setWhatsappPrice($request->amount, $request->id);
    }

    public function getTarification()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'toutes les tarifications',
            'tarifications' => Tarifications::paginate(25)
        ]);
    }
    public function addTarification(Request $request){
        try {
            $tarification = Tarifications::create([
                'nom' => $request->nom,
                'prix_sms' => $request->prix_sms,
                'prix_email' => $request->prix_email,
                'prix_whatsapp' => $request->prix_whatsapp
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'la tarification a été ajoutée avec succès'
            ]);
        } catch (Exception $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th
            ]);
        }        
    }

    public function updateTarification(TarificationRequest $request){
        try {
            $tarification = Tarifications::where('id', $request->id)->update($request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'la tarification a été mise à jour avec succès'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th
            ]);
        }        
    }
    
    public function deleteTarification(Request $request)
    {
        try {
            $tarification = Tarifications::where('id', $request->id)->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'la tarification a été supprimée avec succès'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th
            ]);
        }    
    }

    public function asignTarification(Request $request)
    {
        try {
            $user = User::where('id', $request->user_id)->update(['tarification_id' => $request->tarification]);
            return response()->json([
                'status' => 'success',
                'message' => $user ? 'tarification mise à jour' : 'aucun utilisateur trouvé'
            ]);
        } catch (\Throwable $th) {
            response()->json([
                'status' => 'error',
                'message' => 'la modification n\a pas été faite, veuillez contacter un administrateur'
            ]);
        }
    }
}
