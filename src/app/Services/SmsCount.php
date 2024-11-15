<?php 

namespace App\Services;

class SmsCount{

    function countSmsCharacters($text): int {
        $encoding = 'UTF-8';
        $gsm7bitChars = "@ΔSP0¡P¿pÁpÂpÃpÄpÅpÆpÇÈ0ÉÊpËpÌpÍ0ÎpÏpÐpÑ0Ò0ÓpÔpÕpÖ0×0ØpÙpÚpÛ0ÜpÝpÞ0ß0àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ";
        $text = mb_strtoupper($text, $encoding);
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
        return intdiv($this->countSmsCharacters($text), 120) + 1 ;
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
        
} 