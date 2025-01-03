<?php

namespace App\Http\Controllers;

use App\Http\Requests\WaGroup\AddMembers;
use App\Http\Requests\WaGroup\CreateGroup;
use App\Http\Requests\WaGroup\Members;
use App\Http\Requests\WaGroup\MembersRole;
use App\Http\Requests\WaGroup\Revoke;
use App\Http\Requests\WaGroup\SearchByName;
use App\Http\Requests\WaGroup\SendMessage;
use App\Models\Abonnement;
use App\Models\Assistance;
use App\Models\Param;
use App\Models\Wagroup;
use App\Services\PaginationService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaGroupController extends Controller
{
    protected $API_KEY_WHATSAPP;
    protected $WA_DEVICE;

    public function __construct()
    {
        $this->API_KEY_WHATSAPP =  Param::getTokenWhatsapp();   
    }

    public function checkDevice($device)
    {
        if ($device === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device introuvable.',
            ], 400); // Code 400 : Bad Request
        }

        return $device; // Code 200 : OK
    }

    public function getMemberOfGroup(CreateGroup $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            dd($reponse);
        }
    }

    public function createGroup(CreateGroup $request)
    {
        $phone = (str_replace(' ', '', $request->phone));

        if (!is_numeric($phone)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Numero invalide',
            ], 422);
        }

        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $data = ["name" => $request->group, "description" => $request->description, "participants" => [["phone" => trim($phone)]]];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            return response()->json([
                'status' => 'success',
                "response" => $reponse,
            ], 200);
        }
    }

    public function sendAtGroups(SendMessage $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $data = ["group" => $request->wid, "message" => $request->message, "device" => $device];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/messages",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            return response()->json([
                'status' => 'success',
                "response" => $reponse,
            ], 200);
        }
    }

    public function getAllGroups(Request $request)
    {
        $perPage = $request->perPage ? $request->perPage : 9;
        $paginate = new PaginationService();

        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }

        // $device = $request->device === null ? $this->checkDevice($this->WA_DEVICE) : $request->device;       

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            $paginator = $paginate->wa_paginate($reponse, $perPage, request('page', 1));

            return response()->json([
                'status' => 'success',
                "response" => $paginator,
            ], 200);
        }
    }

    public function allmembers(Members $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $wid = request('wid');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/chat/$device/chats/$wid/participants",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);

            return  response()->json([
                'status' => 'success',
                'total' => count($reponse),
                'data' => $reponse,
            ], Response::HTTP_OK);
        }
    }

    public function verifyIsSent($m_wid)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/chat/$device/messages/$m_wid/ackinfo",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);

            return  response()->json([
                'data' => $reponse,
            ], Response::HTTP_OK);
        }
    }

    public function getInfoGroup($wid)
    {

        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups/$wid",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Désactive SSL
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response()->json([
                'status' => 'error',
                'message' => "cURL Error #: $err",
            ], 500);
        } else {
            $reponse = json_decode($response);

            if (isset($reponse->name)) {
                return $reponse->name;
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'groupe introuvable.',
                    'wid' => $wid,
                ], 404);
            }
        }
    }

    public function isExistOnWa($phone)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }

        $data = ["phone" => trim($phone)];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/numbers/exists",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Désactive SSL
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);  //dd($response);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response()->json([
                'status' => 'error',
                'message' => "cURL Error #: $err",
            ], 500);
        } else {
            $reponse = json_decode($response);

            if (isset($reponse->exists)) {
                return $reponse->exists;
            } else {
                return false;
                // return response()->json([
                //     'status' => 'error',
                //     'message' => 'introuvable.',
                //     'phone' => $phone,
                // ], 404);
            }
        }
    }

    // public function getInfoGroup(Request  $request)
    // {
    //     $device = $this->checkDevice($this->WA_DEVICE);

    //     if ($device instanceof \Illuminate\Http\JsonResponse) { return $device; }
    //     $wid = request('wid');

    //     $curl = curl_init();
    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups/$wid",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_SSL_VERIFYPEER => false, //ssl off
    //         CURLOPT_ENCODING => "",
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 30,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => "GET",
    //         CURLOPT_POSTFIELDS => '',
    //         CURLOPT_HTTPHEADER => [
    //             "Content-Type: application/json",
    //             "Token: $this->API_KEY_WHATSAPP",
    //         ],
    //     ]);

    //     $response = curl_exec($curl);
    //     $err = curl_error($curl);

    //     curl_close($curl);

    //     if ($err) {
    //         echo "cURL Error #:" . $err;
    //     } else {
    //         $reponse = json_decode($response);
    //         return $reponse->name;

    //         // return  response()->json([
    //         //     'status' => 'success',
    //         //     'name' => $reponse->name,
    //         // ], Response::HTTP_OK);
    //     }
    // }

    public function getGroupByWid(Members $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $wid = request('wid');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups/$wid",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            return  response()->json([
                'status' => 'success',
                'members' => count($reponse->participants),
                'data' => $reponse,
            ], Response::HTTP_OK);
        }
    }

    public function storeAllGroups()
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $apiKey = $this->API_KEY_WHATSAPP;
        $userId = auth()->user()->id;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $apiKey",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return response()->json([
                'status' => 'echec',
                'message' => "cURL Error #: $err",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = json_decode($response);

        if (!is_array($response)) {
            return response()->json([
                'status' => 'echec',
                'message' => 'Réponse invalide du serveur',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::beginTransaction();

        try {
        
            Wagroup::where('user_id', $userId)->delete();
            foreach ($response as $data) 
            {
                $existingGroup = Wagroup::where('kwid', $data->wid)
                    ->where('name', $data->name)
                    ->first();
    
                if ($existingGroup) { $existingGroup->delete(); }
    
                Wagroup::create([
                    'user_id' => $userId,
                    'total' => $data->totalParticipants,
                    'name' => $data->name,
                    'kwid' => $data->wid,
                    'kid' => $data->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'modal' => 1,
                'total' => count($response),
                'message' => 'Données groupes mises à jour avec succès',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
    
            return response()->json([
                'status' => 'echec',
                'message' => 'Une erreur s\'est produite lors de la mise à jour des données',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getStore(Request $request)
    {
        // $groups = Wagroup::get();
        $userId = auth()->id();
        $groups = Wagroup::where('user_id', $userId)->get();

        $perPage = $request->perPage ? $request->perPage : 9;
        $paginate = new PaginationService();
        $paginator = $paginate->wa_paginateCollection($groups, $perPage, request('page', 1));

        if (!$paginator) {
            return response()->json([
                'status' => 'echec',
                'message' => 'Une erreur s\'est produite',
            ], 422);
        } else {
            return  response()->json([
                'status' => 'success',
                "response" => $paginator,
            ], Response::HTTP_OK);
        }
    }

    public function getAssistance(Request $request)
    {
        $assistance = Assistance::get();
        $perPage = $request->perPage ? $request->perPage : 9;
        $paginate = new PaginationService();
        $paginator = $paginate->wa_paginateCollection($assistance, $perPage, request('page', 1));

        if (!$paginator) {
            return response()->json([
                'status' => 'echec',
                'message' => 'Une erreur s\'est produite',
            ], 422);
        } else {
            return  response()->json([
                'status' => 'success',
                "response" => $paginator,
            ], Response::HTTP_OK);
        }
    }

    public function getStoreByName(SearchByName $request)
    {
        $search = request('search');
        $perPage = $request->perPage ? $request->perPage : 9;

        if (!empty($search)) :
            $canal = $request->canal;
            try {
                $searchName = $search;
                $waInfos = DB::table('wagroups')
                    ->where('wagroups.name', 'like', '%' . $searchName . '%')
                    ->select(
                        'wagroups.created_at',
                        'wagroups.total',
                        'wagroups.name',
                        'wagroups.kwid',
                        'wagroups.kid'
                    )
                    ->orderBy('created_at', 'DESC')
                    ->paginate($perPage);
                return response()->json([
                    'status' => 'success',
                    'response' => $waInfos,
                ], 200);
            } catch (Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'message error' => $th
                ]);
            }
        else :
            return response()->json([
                'status' => 'params error',
                'message' => 'please send a valid params'
            ], 200);;
        endif;
    }

    public function switchStatus(MembersRole $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $wid = request('wid');

        $data = ["participants" => [["phone" => $request->phone, "admin" => $request->admin]]];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups/$wid/participants",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            return  response()->json([
                'status' => 'success',
                'total' => 'membres ' . $reponse->totalParticipants,
                'data' => $reponse,
            ], Response::HTTP_OK);
        }
    }

    public function revokeMembers(Revoke $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());

        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $wid = request('wid');

        $data = ["phone" => $request->phone];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.wassenger.com/v1/devices/$device/groups/$wid/participants",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, //ssl off
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => json_encode([$data['phone']]),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $reponse = json_decode($response);
            return  response()->json([
                'status' => 'success',
                'data' => $reponse,
            ], Response::HTTP_OK);
        }
    }


    public function addMembers(AddMembers $request)
    {
        $device = $this->checkDevice((new Abonnement)->getCurrentWassengerDevice());


        if ($device instanceof \Illuminate\Http\JsonResponse) {
            return $device;
        }
        $wid = $request->input('wid');
        $participants = $request->input('participants');
        // $wid = request('wid');

        $response = $this->sendCurlRequest("https://api.wassenger.com/v1/devices/$device/groups/$wid/participants", 'POST', [
            'participants' => $participants
        ]);

        if (isset($response->status) && $response->status == 400 && $response->errorCode == "participant:not_found") {
            return response()->json([
                'status' => 'echec',
                'message' => 'Désolé ce numéro n\'est pas sur WhatsApp.',
            ], 422);
        }

        if (isset($response->status) && $response->status == 403 && $response->errorCode == "group:participants:privacy_restriction") {
            $participant = $response->meta->participant ?? null;
            if ($participant) {
                $response = $this->sendCurlRequest("https://api.wassenger.com/v1/devices/$device/groups/$wid/participants", 'POST', [
                    'participants' => [['phone' => $participant]]
                ]);

                if (isset($response->status) && $response->status == 409 && $response->message == "Participant already exist in the group.") {
                    return response()->json([
                        'status' => 'echec',
                        'message' => 'Désolé ce contact est déjà membre de ce groupe.',
                    ], 422);
                }

                return response()->json([
                    'status' => 'echec',
                    'message' => 'Désolé ce contact est déjà membre de ce groupe.',
                ], 422);
            }
        }

        if (isset($response->totalParticipants)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Vous avez été ajoutés au groupe avec succès',
                'participant' => $response->totalParticipants,
            ], Response::HTTP_OK);
        }

        if (isset($response->status) && $response->status == 409 && $response->message == "Participant already exist in the group.") {
            return response()->json([
                'status' => 'echec',
                'message' => 'Désolé ce contact est déjà membre de ce groupe.',
            ], 422);
        }

        return response()->json([
            'status' => 'echec',
            'message' => 'Participant non valide',
        ], 422);
    }

    private function sendCurlRequest($url, $method, $data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_SSL_VERIFYPEER => false, // Désactiver la vérification SSL
            CURLOPT_SSL_VERIFYPEER => true, // off ssl controle
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $this->API_KEY_WHATSAPP",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("cURL Error #: $err");
        }

        return json_decode($response);
    }
}
