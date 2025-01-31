<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;

class AuthMonitorsCredential extends Model implements JWTSubject
{
    protected $table = 'auth_monitors_credentials'; 

    /**
     * Returns the unique user identifier 
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();  
    }

    /**
     * Return the personalized claims for this model.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return []; 
    }
}
