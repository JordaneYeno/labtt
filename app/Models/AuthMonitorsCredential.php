<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class AuthMonitorsCredential extends Model
// {
//     protected $table = 'auth_monitors_credentials';
//     protected $primaryKey = 'id';
//     protected $fillable = ['username', 'password'];

//     public function getJWTIdentifier()
//     {
//         return $this->getKey(); // Retourne l'ID de l'entité
//     }

//     public function getJWTCustomClaims()
//     {
//         return []; // Tu peux ajouter des claims personnalisés ici
//     }
// }
 


// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\Hash;

// class AuthMonitorsCredential extends Model
// {
//     use HasFactory;

    
//     protected $table = 'auth_monitors_credentials';
//     protected $primaryKey = 'id';
//     protected $fillable = ['username', 'password'];

//     public function getJWTIdentifier()
//     {
//         return $this->getKey(); // Retourne l'ID de l'entité
//     }

//     public function getJWTCustomClaims()
//     {
//         return []; // Tu peux ajouter des claims personnalisés ici
//     } 
//     // protected $fillable = [
//     //     'username',
//     //     'password',
//     //     'is_active',
//     //     'expires_at',
//     // ];

//     // Mutateur pour hasher le mot de passe automatiquement lors de la création/mise à jour
//     // public function setPasswordAttribute($value)
//     // {
//     //     $this->attributes['password'] = Hash::make($value);
//     // }
// }




// namespace App\Models;

// use Tymon\JWTAuth\Contracts\JWTSubject;
// use Illuminate\Database\Eloquent\Model;

// class AuthMonitorsCredential extends Model implements JWTSubject
// {
//     protected $table = 'auth_monitors_credentials'; // Nom de la table
//     protected $primaryKey = 'id'; // Clé primaire
//     protected $fillable = ['username', 'password']; // Champs remplissables

//     public function getJWTIdentifier()
//     {
//         return $this->getKey(); // Retourne l'ID de l'entité
//     }

//     public function getAuthIdentifierName()
//     {
//         return 'username'; // Le champ à utiliser pour l'identification
//     }

//     public function getJWTCustomClaims()
//     {
//         return []; // Tu peux ajouter des claims personnalisés ici
//     }
// }






// namespace App\Models;

// use Illuminate\Foundation\Auth\User as Authenticatable;

// class AuthMonitorsCredential extends Authenticatable
// {
//     // protected $table = 'auth_monitors_credentials'; // Table utilisée

//     // protected $fillable = ['username', 'password', 'is_active', 'expires_at'];

//     // protected $hidden = ['password']; // Masquer le mot de passe dans les réponses

//     // // Ajoutez des vérifications supplémentaires si besoin
//     // public function isAccountActive()
//     // {
//     //     return $this->is_active && (is_null($this->expires_at) || $this->expires_at > now());
//     // }





//     /**
//      * @return string
//      */
//     public function getJWTIdentifier()
//     {
//         // Retourne l'identifiant unique de l'utilisateur (par exemple, id de la base de données)
//         return $this->getKey();
//     }

//     /**
//      * @return array
//      */
//     public function getJWTCustomClaims()
//     {
//         // Retourne les claims personnalisés
//         return [];
//     }
// }





namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;

class AuthMonitorsCredential extends Model implements JWTSubject
{
    // Tu peux spécifier ici la table si elle n'est pas la table par défaut
    protected $table = 'auth_monitors_credentials'; 

    /**
     * Retourne l'identifiant unique de l'utilisateur (ici, l'ID de l'enregistrement).
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();  // Retourne l'ID unique de l'utilisateur dans la table
    }

    /**
     * Retourne les claims personnalisés pour ce modèle.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];  // Tu peux ajouter des claims personnalisés ici si nécessaire
    }
}
