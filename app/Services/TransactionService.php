<?php 

namespace App\Services;
use App\Models\Abonnement;
use App\Models\Tarifications;
use App\Models\Transaction;

class TransactionService{
        
    public function makeTransaction($type, $users, $total, $messageId = null, $paiementId = null)
    {
        $totalSold = (new Tarifications)->getEmailPrice() * count($users);
        (new Abonnement)->decreditSolde($totalSold);
        $transaction = (new Transaction)->addTransactionAfterSendMessage($type, $totalSold, $messageId, $paiementId);
    }

}