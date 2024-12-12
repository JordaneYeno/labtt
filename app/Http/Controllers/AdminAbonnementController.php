<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Demande;
use App\Models\Param;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    public function listRequest()
    {
        $sms = DB::table('users')->leftJoin('abonnements', 'abonnements.user_id', 'users.id')->where('sms_status', 1)->select('users.id', 'users.email', 'users.name', 'abonnements.sms', 'abonnements.sms_status')->get();
        $email = DB::table('users')->leftJoin('abonnements', 'abonnements.user_id', 'users.id')->where('email_status', 1)->select('users.id', 'users.email', 'users.name', DB::raw('abonnements.email as service_email'), 'abonnements.email_status')->get();
        $whatsapp = DB::table('users')->leftJoin('abonnements', 'abonnements.user_id', 'users.id')->where('whatsapp_status', 1)->select('users.id', 'users.email', 'users.name', 'abonnements.whatsapp', 'abonnements.whatsapp_status')->get();
        return response()->json(['status' => 'success', 'demande_sms' => $sms, 'demande_email' => $email, 'demande_whatsapp' => $whatsapp]);
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

    public function acceptWhatsapp(Request $request)
    {
        return $this->changeStatus($request, "acceptWhatsapp");
    }

    public function rejectDemande($services, $userId)
    {
        Demande::create([
            'service' => $services,
            'status' => 2,
            'user_id' => $userId
        ]);
    }
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
}
