<?php

namespace App\Enums;

class ServiceType
{
    const WHATSAPP = 'whatsapp';
    const SMS = 'sms';
    const EMAIL = 'email';

    public static function all()
    {
        return [self::WHATSAPP, self::SMS, self::EMAIL];
    }
}
