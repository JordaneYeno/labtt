<?php

namespace App\Services;
use App\Models\International; 

class Convertor
{
    function validateAndFormatLocalPhoneNumber($number)
    {
        $number = trim($number);
        $number = preg_replace('/\D/', '', $number);

        // // Cas préfixes partiels valides (autoriser début de saisie utilisateur)
        // if (preg_match('/^241(06|07|6|7)?$/', $number)) {
        //     return $number;
        // }

        // Format 1 : 7XXXXXXX ou 6XXXXXXX
        if (preg_match('/^(7|6)\d{7}$/', $number)) {
            return '241' . $number;
        }

        // Format 2 : 07XXXXXXX ou 06XXXXXXX
        if (preg_match('/^0(7|6)\d{7}$/', $number)) {
            return '241' . substr($number, 1);
        }
        
        // Cas : 24106XXXXXXX ou 24107XXXXXXX => Corriger vers 2416XXXXXXX ou 2417XXXXXXX
        if (preg_match('/^2410(6|7)(\d{7})$/', $number, $matches)) {
            $number = '241' . $matches[1] . $matches[2]; // Ex: 24106 + 1234567 ➜ 2416 + 1234567
        }

        // Vérifier que le format final est bien 2416XXXXXXX ou 2417XXXXXXX (11 chiffres)
        if (preg_match('/^241(6|7)\d{7}$/', $number)) {
            return $number;
        }

        
        // Numéro invalide
        return response()->json([
            'error' => 'Numéro invalide. Le format doit commencer par 6 ou 7, ou être au format 2416/2417.'
        ], 422);
    }

    public function convertNumbert($number, $indicatif = 241)
    {
        $numberIndicatif = substr($number, 0, strlen($indicatif));
        if ($numberIndicatif == $indicatif) {
            return $number;
        } else {
            if ($indicatif == 241) {
                if (substr($number, 0, 1) == 0) {
                    $number = $indicatif . substr($number, 1);

                    return $number;
                } else {
                    return $number;
                }
            }
        }
    }

    public function convertNumberInternational($number, $indicatifs = ['241' => '+241', '33' => '+33', '1' => '+1'])
    {
        foreach ($indicatifs as $indicatif => $internationalIndicatif) {
            $numberIndicatif = substr($number, 0, strlen($indicatif));
            if ($numberIndicatif == $indicatif) {
                return $number; // Retourner le numéro tel quel s'il commence déjà par l'indicatif
            }
        }
        return $number;
    }

    public static function internationalisation($number, $pays = 'GA')
    {
        $rr = International::where('country', $pays)->select('country', 'sub')->first();
        $indicatif = $rr->sub;

        $numberIndicatif = substr($number, 0, strlen($indicatif));


        if ($numberIndicatif == $indicatif) {
            return $number;
        } else {
            if (substr($number, 0, 1) == 0) {
                $number = $indicatif . substr($number, 1);
                return $number;
            }
            if (substr($number, 0, 1) !== 0) {
                return 'invalid number';
            }
        }
    }

    private function isValidGabonPhoneNumber($phone)
    {
        $pattern = '/^(\+241|00241|241)([0-9]{9})$/';
        // $pattern = '/^(0[0-9]{6})$/';

        // Vérifier si le numéro correspond au pattern
        return preg_match($pattern, $phone);
    }

    public static function kinternationalisation($number, $pays = 'GA')
    {
        $rr = International::where('country', $pays)->select('country', 'sub')->first();
        dd($rr);
        // dd($number,  strpos($number, '+') !== false
        //     ?  $indicatif_rf =  $rr->indicatif
        //     :  $indicatif_rf =  $rr->sub);

        if ($indicatif === '+241') {


            strpos($number, '+') !== false
                ?  $indicatif_rf =  $rr->indicatif
                :  $indicatif_rf =  $rr->sub;

            $indicatif = substr($number, 0, strlen($indicatif_rf));

            return $indicatif != '241' || $indicatif != '+241' ? false : true;
        } else if (strpos($indicatif, '+') === false) {
            return 'join +';
        }
    }

    public static function next($number, $indicatif)
    {
        $rr = International::where('indicatif', $indicatif)->select('pays', 'indicatif', 'sub')->first();
        $numberIndicatif = substr($number, 0, strlen($indicatif) - 1);

        if ($numberIndicatif == $rr->sub) {
            return $number;
        }
    }
}
