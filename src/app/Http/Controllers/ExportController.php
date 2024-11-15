<?php

namespace App\Http\Controllers;

use App\Exports\NotificationExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function storeExcel() 
    {
        $arra = array('User', 'Transaction');
        $nomFichier = Str::random(25);
        $store = Excel::store((new NotificationExport()), "$nomFichier.xlsx", 'public');
        return response()->json([
            'store' => $store,
            'store_path' => asset("app/public/$nomFichier.xlsx")
        ]);
    }
}
