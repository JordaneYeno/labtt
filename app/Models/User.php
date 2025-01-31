<?php

namespace App\Models;

use App\Models\Abonnement;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'password', 'statut', 'role_id', 'init_token', 'altern_key', 'email', 'phone', 'slug'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'otp', 'opt', 'is_notify', 'tarification_id', 'delete_status', 'is_valid',   /*'altern_key','init_token'*/
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime:d M Y h:i:s',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function messages()
    {
        return $this->hasMany(Message::class)->withPivot('created_at');
    }

    public function abonnement()
    {
        return $this->hasOne(Abonnement::class);
    }

    public static function getCurrentUser()
    {
        return auth()->user();
    }

    public static function getUser($id)
    {
        return User::where('id', $id)->first();
    }

    public static function isAdmin()
    {
        $user = auth()->user();
        if ($user->role_id != 1) {
            return true;
        }
        return false;
    }

    public static function isSuperAdmin(): bool
    {
        $user = auth()->user();
        if ($user->role_id == 2) {
            return true;
        }
        return false;
    }


    public static function __isSuperAdmin($usr_role): bool
    {
        if ($usr_role == 2) {
            return true;
        }
        return false;
    }

    public static function __isActivate($identity): bool
    {
        $user = $identity;
        $abonnement = new Abonnement();
        $solde = $abonnement->__getSolde($user->id);
        $tarification = new Tarifications();
        if ($user->status == 1 && $abonnement->__getStatut($user->id) == 1) {
            return true;
        }
        return false;
    }

    public static function isActivate(): bool
    {
        $user = auth()->user();
        $abonnement = new Abonnement();
        $solde = $abonnement->getSolde();
        $tarification = new Tarifications();
        if ($user->status == 1 && $abonnement->getstatut() == 1) {
            return true;
        }
        return false;
    }

    public function statusActivate()
    {
        $user = auth()->user();
        if ($user->status == 1 && $user->delete_status == 0) {
            return true;
        }
        return false;
    }
}
