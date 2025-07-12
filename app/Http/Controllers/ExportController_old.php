<?php

namespace App\Http\Controllers;

// use App\Exports\NotificationExport;
// use Maatwebsite\Excel\Facades\Excel;
// use Illuminate\Support\Str;

// class ExportController extends Controller
// {
//     public function storeExcel()
//     {
//         $arra = array('User', 'Transaction');
//         $nomFichier = Str::random(25);
//         $store = Excel::store((new NotificationExport()), "$nomFichier.xlsx", 'public');
//         return response()->json([
//             'store' => $store,
//             'store_path' => asset("app/public/$nomFichier.xlsx")
//         ]);
//     }
// }


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Crypt;

class ExportController extends Controller
{
    public function exportToExcel(Request $request)
    {
        // Créer une nouvelle feuille Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Titre des colonnes
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nom');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Créé le');

        // Ajouter les données des utilisateurs
        $users = User::all(); // Ou toute autre logique pour filtrer les données
        // dd($users);

        $row = 2; // Commencer à la ligne 2 pour les données
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user->id);
            $sheet->setCellValue('B' . $row, $user->name);
            $sheet->setCellValue('C' . $row, $user->email);
            $sheet->setCellValue('D' . $row, $user->created_at->toDateTimeString());
            $row++;
        }

        // Créer un objet Writer pour écrire l'Excel
        $writer = new Xlsx($spreadsheet);

        // Définir un nom de fichier dynamique
        $filename = 'users_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        // Retourner le fichier Excel pour le téléchargement via l'API
        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportNotificationsToExcel($message_id)
    {
        // Récupérer les notifications pour un message_id spécifique
        $notifications = Notification::where('message_id', $message_id)->get(); //dd($notifications);

        if ($notifications->isEmpty()) {
            return response()->json(['error' => 'Aucune notification trouvée pour ce message.'], 404);
        }

        // Créer une nouvelle feuille Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Titre des colonnes
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Destinataire');
        $sheet->setCellValue('C1', 'Message ID');
        $sheet->setCellValue('D1', 'Canal');
        $sheet->setCellValue('E1', 'Notify');
        $sheet->setCellValue('F1', 'Chroné');
        $sheet->setCellValue('G1', 'Delivery Status');
        $sheet->setCellValue('H1', 'Wassenger ID');
        $sheet->setCellValue('I1', 'Has Final Status');
        $sheet->setCellValue('J1', 'Créé le');

        // Ajouter les données des notifications
        $row = 2; // Commencer à la ligne 2 pour les données
        foreach ($notifications as $notification) {
            $sheet->setCellValue('A' . $row, $notification->id);
            $sheet->setCellValue('B' . $row, $notification->destinataire);
            $sheet->setCellValue('C' . $row, $notification->message_id);
            $sheet->setCellValue('D' . $row, $notification->canal);
            $sheet->setCellValue('E' . $row, $notification->notify);
            $sheet->setCellValue('F' . $row, $notification->chrone);
            $sheet->setCellValue('G' . $row, $notification->delivery_status);
            $sheet->setCellValue('H' . $row, $notification->wassenger_id);
            $sheet->setCellValue('I' . $row, $notification->has_final_status);
            $sheet->setCellValue('J' . $row, $notification->created_at->toDateTimeString());
            $row++;
        }

        // Créer un objet Writer pour écrire l'Excel
        $writer = new Xlsx($spreadsheet);

        // Créer un ID crypté pour l'URL de téléchargement
        $encrypted_id = Crypt::encryptString($message_id);

        // Créer un lien de téléchargement avec l'ID crypté
        $download_url = url("api/dec/recep/outfiles/{$encrypted_id}");

        // Retourner la réponse avec le lien de téléchargement
        return response()->json([
            'message' => 'Fichier Excel prêt à être téléchargé',
            'download_url' => $download_url
        ]);
    }


    public function downloadEncryptedFile($encrypted_id)
    {
        try {
            // Décryptage de l'ID du message
            $message_id = Crypt::decryptString($encrypted_id); //dd($message_id);

            // Afficher l'ID décrypté pour déboguer
            \Log::info("Message ID décrypté : " . $message_id);

            // Récupérer les notifications pour l'ID message décrypté
            $notifications = Notification::where('message_id', $message_id)->get();

            if ($notifications->isEmpty()) {
                \Log::info("Aucune notification trouvée pour le message_id : " . $message_id);
                return response()->json(['error' => 'Aucune notification trouvée pour ce message.'], 404);
            }

            // Créer la feuille Excel et l'exporter comme dans la méthode précédente
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Destinataire');
            $sheet->setCellValue('C1', 'Message ID');
            $sheet->setCellValue('D1', 'Canal');
            $sheet->setCellValue('E1', 'Notify');
            $sheet->setCellValue('F1', 'Chroné');
            $sheet->setCellValue('G1', 'Delivery Status');
            $sheet->setCellValue('H1', 'Wassenger ID');
            $sheet->setCellValue('I1', 'Has Final Status');
            $sheet->setCellValue('J1', 'Créé le');

            $row = 2;
            foreach ($notifications as $notification) {
                $sheet->setCellValue('A' . $row, $notification->id);
                $sheet->setCellValue('B' . $row, $notification->destinataire);
                $sheet->setCellValue('C' . $row, $notification->message_id);
                $sheet->setCellValue('D' . $row, $notification->canal);
                $sheet->setCellValue('E' . $row, $notification->notify);
                $sheet->setCellValue('F' . $row, $notification->chrone);
                $sheet->setCellValue('G' . $row, $notification->delivery_status);
                $sheet->setCellValue('H' . $row, $notification->wassenger_id);
                $sheet->setCellValue('I' . $row, $notification->has_final_status);
                $sheet->setCellValue('J' . $row, $notification->created_at->toDateTimeString());
                $row++;
            }

            // Créer un objet Writer pour écrire l'Excel
            $writer = new Xlsx($spreadsheet);

            // Définir un nom de fichier dynamique
            $filename = 'notifications_export_' . $message_id . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

            // Retourner le fichier Excel pour le téléchargement via l'API
            return response()->stream(function() use ($writer) {
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur lors du décryptage ou de l'exportation du fichier : " . $e->getMessage());
            return response()->json(['error' => 'ID crypté invalide.'], 400);
        }
    }
}
