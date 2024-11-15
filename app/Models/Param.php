<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Param extends Model
{

    protected $fillable = ['secret', 'ref'];

    public static function getStatusWhatsapp()
    {
        return Param::where('ref', 'WHATSAPP_STATUS')->first('secret')->secret;
    }

    public function setStatusWhatsapp(Request $request)
    {
        $token = Param::where('ref', 'WHATSAPP_STATUS')->update(['secret' => $request->status]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'Recharge minimum mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getStatusEmail()
    {
        return Param::where('ref', 'EMAIL_STATUS')->first('secret')->secret;
    }

    public function setStatusEmail(Request $request)
    {
        $token = Param::where('ref', 'EMAIL_STATUS')->update(['secret' => $request->status]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'Recharge minimum mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getStatusSms()
    {
        return Param::where('ref', 'SMS_STATUS')->first('secret')->secret;
    }

    public function setStatusSms(Request $request)
    {
        $token = Param::where('ref', 'SMS_STATUS')->update(['secret' => $request->status]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'Recharge minimum mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getApp()
    {
        return Param::where('ref', 'ENV_DEPLOY')->first('secret')->secret;
    }

    public function setApp(Request $request)
    {
        $token = Param::where('ref', 'ENV_DEPLOY')->update(['secret' => $request->location]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'environnement app mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function apiUrl()
    {
        return Param::where('ref', 'URL_API')->first('secret')->secret;
    }

    public function setApiUrl(Request $request)
    {
        $token = Param::where('ref', 'URL_API')->update(['secret' => $request->adress]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'lien app mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getMinPrice()
    {
        return Param::where('ref', 'MINE_PRICE')->first('secret')->secret;
    }

    public function setMinPrice(Request $request)
    {
        $token = Param::where('ref', 'MINE_PRICE')->update(['secret' => $request->mine_price]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'Recharge minimum mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }


    public static function getTokenWhatsapp()
    {
        return Param::where('ref', 'TOKEN_WASSENGER')->first('secret')->secret;
    }

    public function setTokenWhatsapp(Request $request)
    {
        $token = Param::where('ref', 'TOKEN_WASSENGER')->update(['secret' => $request->token_wassenger]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'token mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getTokenPvit()
    {
        return Param::where('ref', 'TOKEN_PVIT')->first('secret')->secret;
    }

    public function setTokenPvit(Request $request)
    {
        $token = Param::where('ref', 'TOKEN_PVIT')->update(['secret' => $request->token_pvit]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'token mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getEmailAwt()
    {
        return Param::where('ref', 'EMAIL_AWT')->first('secret')->secret;
    }

    public function setEmailAwt(Request $request)
    {
        $email = Param::where('ref', 'EMAIL_AWT')->update(['secret' => $request->email]);
        if ($email) {
            return response()->json([
                'status' => 'success',
                'message' => 'email mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getAdminEmail()
    {
        return Param::where('ref', 'EMAIL_ADMIN')->first('secret')->secret;
    }

    public static function getSmsSender()
    {
        return Param::where('ref', 'SMS_SENDER')->first('secret')->secret;
    }

    public function setSmsSender(Request $request)
    {
        $email = Param::where('ref', 'SMS_SENDER')->update(['secret' => $request->wa_device]);
        if ($email) {
            return response()->json([
                'status' => 'success',
                'message' => 'clé device mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getWassengerDevice()
    {
        return Param::where('ref', 'WA_DEVICE')->first('secret')->secret;
    }

    public function setWassengerDevice(Request $request)
    {
        $email = Param::where('ref', 'WA_DEVICE')->update(['secret' => $request->wa_device]);
        if ($email) {
            return response()->json([
                'status' => 'success',
                'message' => 'clé device mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function setBaseurlFront(Request $request)
    {
        $email = Param::where('ref', 'URL_FRONT')->update(['secret' => $request->urlFront]);
        if ($email) {
            return response()->json([
                'status' => 'success',
                'message' => 'url front mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getBaseurlFront()
    {
        return Param::where('ref', 'URL_FRONT')->first('secret')->secret;
    }

    public function setAdminEmail(Request $request)
    {
        $email = Param::where('ref', 'EMAIL_ADMIN')->update(['secret' => $request->email_admin]);
        if ($email) {
            return response()->json([
                'status' => 'success',
                'message' => 'email mis à jour'
            ], 200);
        }

        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getMarchandAirtel()
    {
        return Param::where('ref', 'AM_MARCHAND')->first('secret')->secret;
    }

    public function setMarchandAirtel(Request $request)
    {
        $token = Param::where('ref', 'AM_MARCHAND')->update(['secret' => $request->marchand]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'marchand mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getMarchandMoov()
    {
        return Param::where('ref', 'MC_MARCHAND')->first('secret')->secret;
    }

    public function setMarchandMoov(Request $request)
    {
        $token = Param::where('ref', 'MC_MARCHAND')->update(['secret' => $request->marchand]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'marchand mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public function setAgent(Request $request)
    {
        $token = Param::where('ref', 'AGENT')->update(['secret' => $request->agent]);
        if ($token) {
            return response()->json([
                'status' => 'success',
                'message' => 'agent mis à jour'
            ], 200);
        }
        return response()->json([
            'status' => 'echec',
            'message' => 'erreur lors de la mise à jour'
        ], 200);
    }

    public static function getAgent()
    {
        return Param::where('ref', 'AGENT')->first('secret')->secret;
    }
}
