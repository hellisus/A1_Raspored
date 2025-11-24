<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Ime'])) {
    echo json_encode(['success' => false, 'message' => 'Nije autorizovan']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
    try {
        $objekat_id = (int)$_POST['objekat_id'];
        
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "stanovi";
        
        // Uzima sve nazive stanova za dati objekat
        $stanovi = $podatci->select(['naziv'], ['objekat_id' => $objekat_id]);
        
        // Ekstraktuje brojeve iz naziva koji počinju sa 'S'
        $usedNumbers = [];
        foreach ($stanovi as $stan) {
            $naziv = $stan['naziv'];
            if (preg_match('/^S(\d+)$/', $naziv, $matches)) {
                $usedNumbers[] = (int)$matches[1];
            }
        }
        
        // Sortira brojeve
        sort($usedNumbers);
        
        // Pronalazi prvi slobodan broj
        $nextNumber = 1;
        foreach ($usedNumbers as $num) {
            if ($num == $nextNumber) {
                $nextNumber++;
            } else {
                break;
            }
        }
        
        $nextName = 'S' . $nextNumber;
        
        echo json_encode([
            'success' => true,
            'nextName' => $nextName
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Greška: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Neispravni zahtev'
    ]);
}
?>
