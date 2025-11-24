<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'config_ajax.php';
include_once 'CRUD_ajax.php';

// AJAX handler - pokreće se SAMO ako su prisutni svi potrebni parametri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'get_info' && isset($_POST['plan_id'])) {
        $plan_id = $_POST['plan_id'];
        
        $result = dohvatiInfoZaPotvrduUplate($plan_id);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'potvrdi' && isset($_POST['plan_id']) && isset($_POST['iznos_uplate']) && isset($_POST['srednji_kurs'])) {
        $plan_id = $_POST['plan_id'];
        $iznos_uplate = $_POST['iznos_uplate'];
        $srednji_kurs = $_POST['srednji_kurs'];
        
        $result = potvrdiUplatuIzPlana($plan_id, $iznos_uplate, $srednji_kurs);
        echo json_encode($result);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Nepoznata akcija ili nedostaju parametri']);
    exit;
}

/**
 * Potvrđuje uplatu iz planirane rate
 * 
 * @param int $plan_id ID planirane rate
 * @param float $iznos_uplate Iznos uplate u RSD
 * @param float $srednji_kurs Srednji kurs RSD/EUR
 * @return array Rezultat operacije
 */
function potvrdiUplatuIzPlana($plan_id, $iznos_uplate, $srednji_kurs) {
    try {
        $iznos_uplate_rsd = isset($iznos_uplate) ? floatval($iznos_uplate) : 0;
        $srednji_kurs = isset($srednji_kurs) ? floatval($srednji_kurs) : 0;

        if ($iznos_uplate_rsd <= 0) {
            return ['success' => false, 'message' => 'Iznos uplate mora biti veći od nule.'];
        }

        if ($srednji_kurs <= 0) {
            return ['success' => false, 'message' => 'Srednji kurs mora biti unet i veći od nule.'];
        }

        // Dohvati planiranu ratu
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        $plan = $planCrud->select(['*'], ['id' => $plan_id]);
        
        if (empty($plan)) {
            return ['success' => false, 'message' => 'Planirana rata nije pronađena'];
        }
        
        $plan = $plan[0];
        
        // Konvertuj iznos iz RSD u EUR
        $iznos_uplate_eur = round($iznos_uplate_rsd / $srednji_kurs, 2);
        $vrednost_u_dinarima = round($iznos_uplate_rsd, 2);
        
        // Kreiraj uplatu
        $uplataCrud = new CRUD_ajax($_SESSION['godina']);
        $uplataCrud->table = "uplata";
        
        $uplata_data = [
            'id_kupca' => $plan['kupac_id'],
            'datum_uplate' => date('Y-m-d'),
            'trenutna_vrednost_eura' => $_SESSION['euro'] ?? 0,
            'srednji_kurs' => $srednji_kurs,
            'iznos_uplate' => $iznos_uplate_eur,
            'tip_jedinice' => $plan['tip_jedinice'],
            'id_jedinice' => $plan['jedinica_id'],
            'kes' => 0,
            'vrednost_u_dinarima' => $vrednost_u_dinarima
        ];
        
        $uplata_id = $uplataCrud->insert($uplata_data);
        
        if (!$uplata_id) {
            return ['success' => false, 'message' => 'Greška pri kreiranju uplate'];
        }
        
        // Ažuriraj status planirane rate
        $novi_status = 'placeno';
        if ($iznos_uplate_eur < $plan['suma']) {
            $novi_status = 'delimicno_placeno';
        }
        
        $planCrud->update(
            ['status' => $novi_status, 'uplata_id' => $uplata_id],
            ['id' => $plan_id]
        );
        
        // Mapiraj uplate na planirane rate nakon potvrde
        include_once 'f_mapiraj_uplate_na_planove.php';
        mapirajSveUplateZaKupca($plan['kupac_id']);
        
        return [
            'success' => true, 
            'message' => 'Uplata je uspešno potvrđena',
            'uplata_id' => $uplata_id
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}

/**
 * Dohvata informacije o planiranoj rati za potvrdu uplate
 * 
 * @param int $plan_id ID planirane rate
 * @return array Informacije o planiranoj rati
 */
function dohvatiInfoZaPotvrduUplate($plan_id) {
    try {
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        $plan = $planCrud->select(['*'], ['id' => $plan_id]);
        
        if (empty($plan)) {
            return ['success' => false, 'message' => 'Planirana rata nije pronađena'];
        }
        
        $plan = $plan[0];
        
        // Dohvati naziv jedinice
        $naziv_jedinice = '';
        switch ($plan['tip_jedinice']) {
            case 'stan':
                $stanCrud = new CRUD_ajax($_SESSION['godina']);
                $stanCrud->table = "stanovi";
                $stan = $stanCrud->select(['naziv'], ['id' => $plan['jedinica_id']]);
                $naziv_jedinice = !empty($stan) ? $stan[0]['naziv'] : 'Nepoznato';
                break;
            case 'lokal':
                $lokalCrud = new CRUD_ajax($_SESSION['godina']);
                $lokalCrud->table = "lokali";
                $lokal = $lokalCrud->select(['naziv'], ['id' => $plan['jedinica_id']]);
                $naziv_jedinice = !empty($lokal) ? $lokal[0]['naziv'] : 'Nepoznato';
                break;
            case 'garaza':
                $garazaCrud = new CRUD_ajax($_SESSION['godina']);
                $garazaCrud->table = "garaze";
                $garaza = $garazaCrud->select(['naziv'], ['id' => $plan['jedinica_id']]);
                $naziv_jedinice = !empty($garaza) ? $garaza[0]['naziv'] : 'Nepoznato';
                break;
            case 'parking':
                $parkingCrud = new CRUD_ajax($_SESSION['godina']);
                $parkingCrud->table = "parking_mesta";
                $parking = $parkingCrud->select(['naziv'], ['id' => $plan['jedinica_id']]);
                $naziv_jedinice = !empty($parking) ? $parking[0]['naziv'] : 'Nepoznato';
                break;
        }
        
        return [
            'success' => true,
            'plan' => $plan,
            'naziv_jedinice' => $naziv_jedinice
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}
?>
