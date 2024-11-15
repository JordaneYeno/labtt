<?php 

namespace App\Services;
use App\Models\Abonnement;
use App\Models\Tarifications;

class PriceService{

    public function updateSolde ($users)
    {
        $usersCount = count($users);
        $tarification = new Tarifications();
        $emailPrice = $tarification->getEmailPrice();
        $amount = $usersCount * $emailPrice;
        $decrement = new Abonnement();
        $result = $decrement->decreditSolde($amount);
    }
    
}