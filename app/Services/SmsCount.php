<?php 

namespace App\Services;

class SmsCount{

    function removeAccents($text) {
        $search = [
            'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 
            'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ',
            "'", '’' 
        ];
        
        $replace = [
            'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i',
            'o', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y',
            ' ', ' '  
        ];
        return str_replace($search, $replace, $text);
    }    

    function countSmsCharacters($text): int {
        $encoding = 'UTF-8';
        $gsm7bitChars = "@ΔSP0¡P¿pÁpÂpÃpÄpÅpÆpÇÈ0ÉÊpËpÌpÍ0ÎpÏpÐpÑ0Ò0ÓpÔpÕpÖ0×0ØpÙpÚpÛ0ÜpÝpÞ0ß0àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ";
        $text = mb_strtoupper( $this->removeAccents($text), $encoding);
        $charCount = 0;
        $textLength = mb_strlen($text, $encoding);
    
        for ($i = 0; $i < $textLength; $i++) {
            $char = mb_substr($text, $i, 1, $encoding);
            if (strpos($gsm7bitChars, $char) === false) {
                $charCount++;
            } else {
                $charCount += 2;
            }
        }
    
        return $charCount;
    }

    public function countSmsSend($text)
    {
        // return intdiv($this->countSmsCharacters($text), 120) + 1 ;
        return intdiv($this->countSmsCharacters($text), 150) + 1 ;
    }
    
    public function index () 
    {
        $text = "Hello";
        $encoding = 'UTF-8';
        $charCount = $this->countSmsCharacters($text);
        return response()->json([
            "SMS character count" => $charCount
        ]);
    }    
    
    // function countSmsCharacters($text): int {
    //     $gsm7bitChars = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ!\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    //     $textLength = mb_strlen($text, 'UTF-8');
    //     $isGsm7Bit = true;
    
    //     for ($i = 0; $i < $textLength; $i++) {
    //         $char = mb_substr($text, $i, 1, 'UTF-8');
    //         if (strpos($gsm7bitChars, $char) === false) {
    //             $isGsm7Bit = false;
    //             break;
    //         }
    //     }
    
    //     // UTF-16
    //     if (!$isGsm7Bit) {
    //         return ceil($textLength / 70); // 70 caractères max par SMS UTF-16
    //     }
    
    //     // Pour GSM 7-bit
    //     return ceil($textLength / 160); // 160 caractères max par SMS GSM 7-bit
    // }
} 