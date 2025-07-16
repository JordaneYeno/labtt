<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // ✅ Liste des comptes secondaires du propriétaire
    public function index()
    {
        // dd(auth()->user());
        $authUser = Auth::user(); //dd($authUser);

        if (!$authUser->isOwner()) {
            return response()->json([
                'error' => 'Accès refusé. Vous devez être un compte principal.'
            ], 403);
        }

        $subUsers = User::where('owner_id', $authUser->id)->get();

        return response()->json([
            'data' => $subUsers
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // ✅ Créer un compte secondaire
    public function store(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser->isOwner()) {
            return response()->json([
                'error' => 'Accès refusé. Vous devez être un compte principal.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['owner_id'] = $authUser->id;

        $user = User::create($validated);

        return response()->json([
            'message' => 'Utilisateur secondaire créé.',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // ✅ Détail d’un utilisateur secondaire
    public function show(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isOwner()) {
            return response()->json([
                'error' => 'Accès refusé. Vous devez être un compte principal.'
            ], 403);
        }

        if ($user->owner_id !== $authUser->id) {
            return response()->json([
                'error' => 'Cet utilisateur ne vous appartient pas.'
            ], 403);
        }

        return response()->json([
            'data' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // ✅ Modifier un compte secondaire
    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isOwner()) {
            return response()->json([
                'error' => 'Accès refusé. Vous devez être un compte principal.'
            ], 403);
        }

        if ($user->owner_id !== $authUser->id) {
            return response()->json([
                'error' => 'Cet utilisateur ne vous appartient pas.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|required|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // ✅ Supprimer un compte secondaire
    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isOwner()) {
            return response()->json([
                'error' => 'Accès refusé. Vous devez être un compte principal.'
            ], 403);
        }

        if ($user->owner_id !== $authUser->id) {
            return response()->json([
                'error' => 'Cet utilisateur ne vous appartient pas.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé.'
        ]);
    }
}
