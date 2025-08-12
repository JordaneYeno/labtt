<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Exportation des utilisateurs vers Excel.
     */
    public function exportUsersToExcel(): StreamedResponse
    {
        $users = User::all();
        $headers = ['ID', 'Nom', 'Email', 'Créé le'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name,
                $user->email,
                $user->created_at->toDateTimeString(),
            ];
        })->toArray();

        return $this->createExcelDownloadResponse($headers, $rows, 'users_export');
    }

    /**
     * Génère un lien temporaire de téléchargement Excel pour des notifications liées à un message.
     */
    public function exportNotificationsToExcel($message_id)
    {
        $notifications = Notification::where('message_id', $message_id)->get();

        if ($notifications->isEmpty()) {
            return response()->json(['error' => 'Aucune notification trouvée pour ce message.'], 404);
        }

        $encrypted_id = Crypt::encryptString($message_id);
        // $download_url = route('download.encrypted.file', [
        //     'encrypted_id' => $encrypted_id,
        //     'expires' => now()->addMinutes(30)->timestamp
        // ]);

        $download_url = URL::temporarySignedRoute(
            'download.encrypted.file', // Nom de ta route
            now()->addMinutes(7),     // Durée de validité
            ['encrypted_id' => $encrypted_id]
        );

        return response()->json([
            'message' => 'Fichier Excel prêt à être téléchargé',
            'download_url' => $download_url
        ]);
    }

    /**
     * Téléchargement du fichier Excel depuis un lien sécurisé.
     */
    public function downloadEncryptedFile($encrypted_id, Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $expiration_time = $request->query('expires');
            if (!$expiration_time || now()->timestamp > $expiration_time) {
                return response()->json(['error' => 'Le lien a expiré.'], 400);
            }

            $message_id = Crypt::decryptString($encrypted_id);
            Log::info("Message ID décrypté : $message_id");

            $notifications = Notification::where('message_id', $message_id)->get();
            if ($notifications->isEmpty()) {
                return response()->json(['error' => 'Aucune notification trouvée pour ce message.'], 404);
            }

            $headers = [
                'ID', 'Destinataire', 'Message ID', 'Canal', 'Notify',
                /*'Chroné',*/ 'Delivery Status', 'Has Final Status', 'Créé le'
            ];

            $rows = $notifications->map(function ($notif) {
                return [
                    $notif->id,
                    $notif->destinataire,
                    $notif->message_id,
                    $notif->canal,
                    $this->resolveNotifyLabel($notif->notify),
                    // $notif->chrone,
                    $notif->delivery_status,
                    $notif->has_final_status,
                    $notif->created_at->toDateTimeString(),
                ];
            })->toArray();

            return $this->createExcelDownloadResponse($headers, $rows, "notifications_export_{$message_id}");
        } catch (\Exception $e) {
            Log::error("Erreur : " . $e->getMessage());
            return response()->json(['error' => 'ID crypté invalide.'], 400);
        }
    }

    /**
     * Génère une réponse de téléchargement Excel à partir d’un tableau d’en-têtes et de données.
     */
    private function createExcelDownloadResponse(array $headers, array $rows, string $filenamePrefix): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Données
        $rowNum = 2;
        foreach ($rows as $row) {
            foreach ($row as $col => $value) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowNum, $value);
            }
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "{$filenamePrefix}_" . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Retourne la valeur textuelle du champ notify.
     */
    // private function resolveNotifyLabel(int $notify): string
    // {
    //     return match ($notify) {
    //         4 => 'API',
    //         1 => 'Plateforme',
    //         default => 'Inconnu',
    //     };
    // }

    private function resolveNotifyLabel($notify)
    {
        switch ($notify) {
            case 4:
                return 'API';
            case 1:
                return 'Plateforme';
            default:
                return 'Inconnu';
        }
    }

}
