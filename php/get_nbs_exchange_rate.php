<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['datum'])) {
    try {
        $datum = $_POST['datum'];
        
        // Formatiranje datuma za NBS API (dd.mm.yyyy)
        $formatted_date = formatirajDatum($datum, '');
        if ($formatted_date === '') {
            echo json_encode([
                'success' => false,
                'error' => 'Neispravan format datuma.'
            ]);
            exit;
        }
        
        // NBS API endpoint sa GET parametrima
        $nbs_url = 'https://webappcenter.nbs.rs/ExchangeRateWebApp/ExchangeRate/IndexByDate?' . http_build_query([
            'isSearchExecuted' => 'true',
            'Date' => $formatted_date . '.',
            'ExchangeRateListTypeID' => '3'
        ]);
        
        // cURL zahtev
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $nbs_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            // Dekoduj HTML entitete
            $response = html_entity_decode($response, ENT_QUOTES, 'UTF-8');
            
            // Parsiranje HTML odgovora da pronađemo kurs evra
            $kurs_evra = null;
            
            // Tražimo EUR u tabeli kursne liste - različiti patterni
            $patterns = [
                '/<td>EUR<\/td>\s*<td>\d+<\/td>\s*<td>.*?<\/td>\s*<td>\d+<\/td>\s*<td>(\d+,\d+)<\/td>/',  // HTML tabela sa 5 kolona
                '/<td>EUR<\/td>.*?<td>(\d+,\d+)<\/td>/',  // HTML tabela direktno
                '/EUR.*?(\d+,\d{4})/',  // Sa 4 decimale (117,1735)
                '/ЕУР.*?(\d+,\d{4})/',  // Ćirilica sa 4 decimale
                '/EUR.*?(\d+,\d+)/',  // Osnovni pattern
                '/ЕУР.*?(\d+,\d+)/',  // Ćirilica
                '/\|.*?EUR.*?\|.*?\|.*?\|.*?\|.*?\|.*?(\d+,\d+)/',  // Tabela format
                '/EUR.*?\|.*?\|.*?\|.*?\|.*?\|.*?(\d+,\d+)/',  // Tabela bez početnog |
                '/EUR.*?(\d+\.\d+)/',  // Sa tačkom umesto zareza
                '/ЕУР.*?(\d+\.\d+)/',  // Ćirilica sa tačkom
                '/EUR.*?(\d{3},\d{4})/',  // Tačno 3 cifre pre zareza
                '/ЕУР.*?(\d{3},\d{4})/'   // Ćirilica sa 3 cifre pre zareza
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $response, $matches)) {
                    $kurs_evra = floatval(str_replace(',', '.', $matches[1]));
                    break;
                }
            }
            
            if ($kurs_evra && $kurs_evra > 0) {
                echo json_encode([
                    'success' => true,
                    'kurs' => $kurs_evra,
                    'datum' => $formatted_date
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Kurs evra nije pronađen za datum: ' . $formatted_date
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Greška pri komunikaciji sa NBS serverom'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Greška: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Neispravni zahtev'
    ]);
}
?>
