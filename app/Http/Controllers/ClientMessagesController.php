<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\PaginationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ClientMessagesController extends Controller
{

    public function getAllMessagesByUser(Request $request)
    {
        $paginate = new PaginationService();
        try {
            $user = auth()->user();
            $perPage = $request->perPage ? $request->perPage : 25;
            $messages =  Message::where('messages.user_id', $user->id)
                ->select(
                    'messages.id',
                    'messages.ed_reference',
                    'messages.credit',
                    'messages.credit',
                    'messages.status',
                    'messages.finish',
                    'messages.message',
                    'messages.canal',
                    'messages.title',
                    'messages.destinataires',
                    'messages.created_at',
                    'messages.start',
                    'messages.date_envoie'
                )
                ->orderBy('created_at', 'DESC')
                ->paginate($perPage); 

            $messages->transform(function ($mess) {
                $recipients = DB::table('notifications')
                    ->where('message_id', $mess->id)
                    ->select('destinataire', 'delivery_status', 'canal')->get();
                $mess->destinataires = $recipients;
                return $mess;
            });

            // return $messages;
            if (!$messages) {
                return response()->json([
                    'statut' => 'error',
                    'message' => 'Aucun résultat trouvé'
                ], 200);
            } else {
                return response()->json([
                    'statut' => 'success',
                    'userName' => $user->name,
                    "response" => $messages,
                ], 200);
            }
        } catch (Exception $th) {
            return response()->json([
                'statut' => $th,
                'message' => 'une erreur s\'est produite veuillez reessayer'
            ]);
        }
    }

    public function getMessagesByCanalAndUser(Request $request)
    {
        if (!empty($request->canal)) {
            try {
                $user = auth()->user();
                $searchCanal = $request->canal;
                $messages = DB::table('messages')
                    ->where('messages.user_id', $user->id)
                    ->where('messages.canal', 'like', '%' . $searchCanal . '%')
                    ->select(
                        'messages.id',
                        'messages.message',
                        'messages.canal',
                        'messages.title',
                        'messages.date_envoie',
                        'messages.destinataires',
                        'messages.created_at'
                    )
                    ->orderBy('created_at', 'DESC')
                    ->paginate(25);
                return response()->json([
                    'status' => 'success',
                    'userName' => $user->name,
                    "response" => $messages,
                ], 200);
            } catch (Exception $th) {
                return response()->json([
                    'status' => 'Error',
                    'message_error' => $th
                ]);
            }
        }
        return response()->json([
            'statut' => 'echec',
            'message' => 'veuillez renseigner un canal'
        ], 200);
    }

    public function getMessagesByPeriodAndUser(Request $request)
    {
        if (!empty($request->dateStart) && !empty($request->dateEnd)) {
            try {
                $dateStart = $request->dateStart;
                $dateEnd = $request->dateEnd;
                if ($dateStart > $dateEnd) {
                    return response()->json([
                        'status' => 'echec',
                        'message' => 'la date de début doit être inférieure à la date de fin.'
                    ], 200);
                }
                $user = auth()->user();
                $messages = DB::table('messages')
                    ->where('messages.created_at', '>=', $dateStart . ' 00:00:00')
                    ->where('messages.created_at', '<=', $dateEnd . ' 23:59:59')
                    ->where('messages.user_id', $user->id)
                    ->select(
                        'messages.id',
                        'messages.message',
                        'messages.created_at',
                        'messages.canal',
                        'messages.title',
                        'messages.destinataires'
                    )
                    ->orderBy('created_at', 'DESC')
                    ->paginate(25);
                return response()->json([
                    'status' => 'success',
                    'userName' => $user->name,
                    "response" => $messages
                ], 200);
            } catch (Exception $th) {
                return response()->json([
                    'status' => 'Error',
                    'message' => $th
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'veuillez envoyer des dates valides.'
            ], 200);
        }
    }

    public function getMessagesByKeywordCanalAndUser(Request $request)
    {
        if (!empty($request->search)) :
            try {
                $searchCanal = $request->search;
                $user = auth()->user();
                $messages = DB::table('messages')
                    ->where('user_id', '=', $user->id)
                    ->where('canal', 'like',  '%' . $request->canal . '%')
                    ->where(function ($query) use ($searchCanal) {
                        $query->where('title', 'like',  '%' . $searchCanal . '%')
                            ->orwhere('destinataires', 'like',  '%' . $searchCanal . '%')
                            ->orwhere('message', 'like',  '%' . $searchCanal . '%');
                    })
                    ->select(
                        'messages.id',
                        'messages.message',
                        'messages.finish',
                        'messages.created_at',
                        'messages.canal',
                        'messages.date_envoie',
                        'messages.title',
                        'messages.destinataires'
                    )
                    ->orderBy('created_at', 'DESC')
                    ->paginate(25);
                return response()->json([
                    'userName' => $user->name,
                    'status' => 'success',
                    "response" => $messages,
                ], 200);
            } catch (Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'message error' => $th
                ], 200);
            }
        else :
            return response()->json([
                'status' => 'echec',
                'message' => 'veuillez saisir un mot clé'
            ], 200);;
        endif;
    }

    public function getRecipients(Request $request)
    {
        try {
            if ($request->idMessage == '' || $request->idMessage == null) {
                return response()->json([
                    'status' => 'echec',
                    'message' => 'veuillez envoyer un message'
                ], 200);
            }
            $recipients = DB::table('notifications')
                ->where('message_id', $request->idMessage)
                ->select('message_id', 'destinataire', 'notify', 'chrone', 'delivery_status', 'canal')
                ->orderBy('created_at', 'DESC')
                ->paginate(25);
            return response()->json([
                'status' => 'success',
                "response" => $recipients,
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message error' => $th
            ], 403);
        }
    }
}
