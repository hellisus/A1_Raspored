<?php
require_once '../config.php';

// Funkcija za proveru redosleda faza
function proveri_redosled_faza($faza_1, $faza_2, $faza_3, $faza_4) {
    $greske = [];
    
    // Proveri da li su uneti datumi
    $datumi = [
        'faza_1' => $faza_1,
        'faza_2' => $faza_2, 
        'faza_3' => $faza_3,
        'faza_4' => $faza_4
    ];
    
    // Filtriraj samo unete datume
    $uneti_datumi = array_filter($datumi, function($datum) {
        return !empty($datum);
    });
    
    if (count($uneti_datumi) > 1) {
        // Sortiraj datume po redosledu
        $sortirani_datumi = [];
        foreach ($uneti_datumi as $faza => $datum) {
            $sortirani_datumi[$faza] = strtotime($datum);
        }
        
        // Proveri redosled
        if (isset($sortirani_datumi['faza_1']) && isset($sortirani_datumi['faza_2'])) {
            if ($sortirani_datumi['faza_2'] < $sortirani_datumi['faza_1']) {
                $greske[] = "Faza 2 ne može biti pre faze 1";
            }
        }
        
        if (isset($sortirani_datumi['faza_2']) && isset($sortirani_datumi['faza_3'])) {
            if ($sortirani_datumi['faza_3'] < $sortirani_datumi['faza_2']) {
                $greske[] = "Faza 3 ne može biti pre faze 2";
            }
        }
        
        if (isset($sortirani_datumi['faza_3']) && isset($sortirani_datumi['faza_4'])) {
            if ($sortirani_datumi['faza_4'] < $sortirani_datumi['faza_3']) {
                $greske[] = "Faza 4 ne može biti pre faze 3";
            }
        }
        
        // Proveri da li su sve faze u redosledu
        $faze_redosled = ['faza_1', 'faza_2', 'faza_3', 'faza_4'];
        $poslednja_faza = null;
        
        foreach ($faze_redosled as $faza) {
            if (isset($sortirani_datumi[$faza])) {
                if ($poslednja_faza !== null && $sortirani_datumi[$faza] < $sortirani_datumi[$poslednja_faza]) {
                    $greske[] = "Faze moraju biti u hronološkom redosledu";
                    break;
                }
                $poslednja_faza = $faza;
            }
        }
    }
    
    return $greske;
}

$objekti = new CRUD($_SESSION['godina']);
$objekti->table = "objekti";

$rezultat = $objekti->select(['*'], ['naziv' => $_POST['naziv']]);

if (count($rezultat) == 0) {
    
    // Proveri redosled faza
    $greske_faza = proveri_redosled_faza($_POST['faza_1'] ?? '', $_POST['faza_2'] ?? '', $_POST['faza_3'] ?? '', $_POST['faza_4'] ?? '');
    
    if (empty($greske_faza)) {
        // Konvertuj prazne stringove u NULL za bazu i validiraj datume
        $faza_1 = (!empty($_POST['faza_1']) && $_POST['faza_1'] !== '') ? $_POST['faza_1'] : null;
        $faza_2 = (!empty($_POST['faza_2']) && $_POST['faza_2'] !== '') ? $_POST['faza_2'] : null;
        $faza_3 = (!empty($_POST['faza_3']) && $_POST['faza_3'] !== '') ? $_POST['faza_3'] : null;
        $faza_4 = (!empty($_POST['faza_4']) && $_POST['faza_4'] !== '') ? $_POST['faza_4'] : null;
        
        // Validiraj format datuma
        if ($faza_1 && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $faza_1)) {
            $faza_1 = null;
        }
        if ($faza_2 && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $faza_2)) {
            $faza_2 = null;
        }
        if ($faza_3 && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $faza_3)) {
            $faza_3 = null;
        }
        if ($faza_4 && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $faza_4)) {
            $faza_4 = null;
        }
        
        $objekti->insert([
            'broj_stanova' => $_POST['broj_stanova'],
            'broj_lokala' => $_POST['broj_lokala'],
            'broj_garaza' => $_POST['broj_garaza'],
            'broj_parkinga' => $_POST['broj_parkinga'],
            'naziv' => $_POST['naziv'],
            'faza_1' => $faza_1,
            'faza_2' => $faza_2,
            'faza_3' => $faza_3,
            'faza_4' => $faza_4,
        ]);
    } else {
        // Vrati grešku ako redosled nije ispravan
        http_response_code(400);
        echo json_encode(['error' => implode(', ', $greske_faza)]);
        exit;
    }
} else {
    // Objekat već postoji
    http_response_code(400);
    echo json_encode(['error' => 'Objekat sa tim nazivom već postoji']);
    exit;
}
