<?php

namespace App\Enums;

namespace App\Enums;

class UserRole
{
    const USER = 0;
    const MANAGER = 1;
    const ADMIN = 2;
    const BETA_TESTER = 3;

    // Méthode d'aide pour récupérer le rôle sous forme de texte
    public static function getRoleText($role)
    {
        $roles = [
            self::USER => 'Utilisateur',
            self::MANAGER => 'Manager',
            self::ADMIN => 'Administrateur',
            self::BETA_TESTER => 'tester',
        ];

        return $roles[$role] ?? 'Inconnu';
    }
}

