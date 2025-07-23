<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\PaginationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    // ✅ Liste les utilisateurs secondaires du compte principal connecté
    public function index()
    {
        $authUser = Auth::user();

        // Si c'est un compte principal, on retourne ses comptes secondaires
        if ($authUser->owner_id === null) {
            $subUsers = User::where('owner_id', $authUser->id)->get();
            return response()->json(['data' => $subUsers]);
        }

        // Si c'est un utilisateur secondaire, il ne peut pas accéder à cette liste
        return response()->json([
            'error' => 'Accès refusé. Vous n’êtes pas un compte principal.'
        ], 403);
    }

    // ✅ Créer un utilisateur secondaire lié au compte principal connecté
    public function store(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->owner_id !== null) {
            return response()->json([
                'error' => 'Accès refusé. Vous devez être un compte principal.'
            ], 403);
        } //dd($authUser);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]); //dd($validated);

        $validated['password'] = Hash::make($validated['password']);
        $validated['owner_id'] = $authUser->id;

        $user = User::create($validated);

        return response()->json([
            'message' => 'Utilisateur secondaire créé.',
            'data' => $user
        ], 201);
    }

    // ✅ Détail d’un utilisateur secondaire
    public function show(User $user)
    {
        $authUser = Auth::user();

        if ($authUser->owner_id !== null || $user->owner_id !== $authUser->id) {
            return response()->json([
                'error' => 'Accès refusé. Vous n’êtes pas le propriétaire de cet utilisateur.'
            ], 403);
        }

        return response()->json([
            'data' => $user
        ]);
    }

    // ✅ Modifier un utilisateur secondaire
    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if ($authUser->owner_id !== null || $user->owner_id !== $authUser->id) {
            return response()->json([
                'error' => 'Accès refusé. Vous n’êtes pas le propriétaire de cet utilisateur.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|required|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour.',
            'data' => $user
        ]);
    }

    // ✅ Supprimer un utilisateur secondaire
    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if ($authUser->owner_id !== null || $user->owner_id !== $authUser->id) {
            return response()->json([
                'error' => 'Accès refusé. Vous n’êtes pas le propriétaire de cet utilisateur.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur secondaire supprimé.'
        ]);
    }



    // others

    // Récupère le compte principal
    protected function getOwnerOrSelf(Request $request)
    {
        $user = $request->user();

        if ($user->owner_id !== null) {
            return $user->owner;
        }

        return $user;
    }

    // Récupère l'abonnement du compte principal ou du compte lui-même
    protected function getAbonnement(Request $request)
    {
        $owner = $this->getOwnerOrSelf($request);
        return $owner->abonnement;
    }

    // Retourne le solde
    public function solde(Request $request)
    {
        $abonnement = $this->getAbonnement($request);

        return response()->json([
            'solde' => $abonnement ? $abonnement->solde : 0
        ], Response::HTTP_OK);
    }

    // Retourne l'état de WhatsApp
    public function etatWhatsApp(Request $request)
    {
        $abonnement = $this->getAbonnement($request);

        return response()->json([
            // 'whatsapp_status' => $abonnement ? $abonnement->whatsapp_status : 0
            'whatsapp_status' => $abonnement ? ServiceStatus::getStatusText($abonnement->whatsapp_status) : ServiceStatus::getStatusText(0)
        ], Response::HTTP_OK);
    }

    // Retourne l'état de SMS
    public function etatSms(Request $request)
    {
        $abonnement = $this->getAbonnement($request);

        return response()->json([
            // 'sms_status' => $abonnement ? $abonnement->sms_status : 0
            'sms_status' => $abonnement ? ServiceStatus::getStatusText($abonnement->sms_status) : ServiceStatus::getStatusText(0)
        ], Response::HTTP_OK);
    }

    // Retourne l'état de Email
    public function etatEmail(Request $request)
    {
        $abonnement = $this->getAbonnement($request);

        return response()->json([
            // 'email_status' => $abonnement ? $abonnement->email_status : 0
            'email_status' => $abonnement ? ServiceStatus::getStatusText($abonnement->email_status) : ServiceStatus::getStatusText(0)
        ], Response::HTTP_OK);
    }

    // Retourne tous les états en une seule fois
    public function etatServices(Request $request)
    {
        $abonnement = $this->getAbonnement($request);

        return response()->json([
            'whatsapp_status' => $abonnement ? $abonnement->whatsapp_status : 0,
            'sms_status' => $abonnement ? $abonnement->sms_status : 0,
            'email_status' => $abonnement ? $abonnement->email_status : 0
        ], Response::HTTP_OK);
    }


    // messages

    public function getMessagesByCanal(Request $request)
    {
        if (empty($request->canal)) {
            return response()->json([
                'status' => 'echec',
                'message' => 'Veuillez saisir un canal'
            ], 200);
        }

        $user = Auth::user();

        // Récupère le compte principal si l'utilisateur est secondaire
        $userId = $user->owner_id !== null ? $user->owner->id : $user->id;

        $searchCanal = $request->canal;

        $messages = Message::where('user_id', $userId)->where('canal', 'like', '%' . $searchCanal . '%')
            ->select(
                'messages.id',
                'messages.message',
                'messages.canal',
                'messages.date_envoie',
                'messages.title',
                'messages.destinataires',
                'messages.created_at'
            )->paginate(25);

        return response()->json([
            'status' => 'success',
            'response' => $messages,
        ], 200);
    }

    public function getMessages(Request $request)
    {
        $user = Auth::user();

        // Récupère le compte principal si l'utilisateur est secondaire
        $userId = $user->owner_id !== null ? $user->owner->id : $user->id;

        try {
            $paginate = new PaginationService();
                $messages = Message::leftJoin('users', 'users.id', '=', 'messages.user_id')
                ->where('messages.user_id', $userId)  // Filtrage des messages par utilisateur
                ->select(
                    'messages.id',
                    'users.name',
                    'messages.message',
                    'messages.created_at',
                    'messages.canal',
                    'messages.date_envoie',
                    'messages.title',
                    'messages.destinataires',
                )
                ->orderBy('created_at', 'DESC');
            if ($messages->get()->isEmpty()) {
                return response()->json([
                    'statut' => 'result error',
                    'message' => 'no result found'
                ], 200);
            }
            return response()->json([
                'statut' => 'success',
                "response" => $paginate->setPaginate($messages, $request->perPage),
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'statut' => 'error',
                'message error' => $th
            ], 500);
        }
    }
}
