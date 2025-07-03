<?php

namespace App\Enums;

class ServiceStatus
{
    const RESET = 0;     // Réinitialisé
    const PENDING = 1;   // En attente
    const REJECTED = 2;  // Rejeté
    const ACCEPTED = 3;  // Accepté

    // Optionnel : méthodes d’aide pour faciliter l’utilisation dans les contrôleurs
    public static function getStatusText($status)
    {
        $statuses = [
            self::RESET => 'Réinitialisé',
            self::PENDING => 'En attente',
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Rejeté',
        ];

        return $statuses[$status] ?? 'Inconnu';
    }
}
