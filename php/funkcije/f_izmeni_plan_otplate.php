<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'config_ajax.php';
include_once 'CRUD_ajax.php';
include_once 'plan_otplate_utils.php';

// AJAX handler - pokreće se SAMO ako su prisutni svi potrebni parametri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kupac_id']) && isset($_POST['jedinica_id']) && isset($_POST['tip_jedinice']) && isset($_POST['rate'])) {
    $kupac_id = $_POST['kupac_id'];
    $jedinica_id = $_POST['jedinica_id'];
    $tip_jedinice = $_POST['tip_jedinice'];
    $rate_json = $_POST['rate'];
    
    $rate = json_decode($rate_json, true);
    if (!$rate) {
        echo json_encode(['success' => false, 'message' => 'Neispravni podaci za rate']);
        exit;
    }
    
    $result = izmeniPlanOtplate($kupac_id, $jedinica_id, $tip_jedinice, $rate);
    echo json_encode($result);
    exit;
}

/**
 * Izmenjuje plan otplate - dodaje, briše ili ažurira rate
 * 
 * @param int $kupac_id ID kupca
 * @param int $jedinica_id ID jedinice
 * @param string $tip_jedinice Tip jedinice
 * @param array $rate Array sa ratama [['datum_rate' => 'Y-m-d', 'procenat' => float, 'suma' => float], ...]
 * @return array Rezultat operacije
 */
function izmeniPlanOtplate($kupac_id, $jedinica_id, $tip_jedinice, $rate) {
    try {
        // Validacija
        $ukupan_procenat = array_sum(array_column($rate, 'procenat'));
        if (abs($ukupan_procenat - 100.00) > 0.01) {
            return ['success' => false, 'message' => 'Ukupan procenat mora biti 100%'];
        }
        
        // Dohvati ukupnu cenu jedinice
        $ukupna_cena = izracunajCenuJedinice($jedinica_id, $tip_jedinice);
        $ukupna_suma = array_sum(array_column($rate, 'suma'));
        
        if (abs($ukupna_suma - $ukupna_cena) > 0.01) {
            return ['success' => false, 'message' => 'Ukupna suma mora biti jednaka ceni jedinice'];
        }
        
        // Dohvati datum prodaje iz postojećeg plana ili kreiranje novog
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        
        $postojeci_plan = $planCrud->select(
            [], 
            [], 
            "SELECT datum_prodaje FROM planovi_otplate WHERE kupac_id = ? AND jedinica_id = ? AND tip_jedinice = ? LIMIT 1",
            [$kupac_id, $jedinica_id, $tip_jedinice]
        );
        
        $datum_prodaje = !empty($postojeci_plan) ? $postojeci_plan[0]['datum_prodaje'] : null;

        if (!$datum_prodaje) {
            $informacije = planOtplateDohvatiInformacijeOJedinici((int) $jedinica_id, $tip_jedinice);
            $datum_prodaje = planOtplateNormalizujDatum($informacije['datum_prodaje'] ?? null) ?? date('Y-m-d');
        }
        
        // Obriši postojeći plan
        $planCrud->delete([
            'kupac_id' => $kupac_id,
            'jedinica_id' => $jedinica_id,
            'tip_jedinice' => $tip_jedinice
        ]);
        
        // Kreiraj novi plan
        foreach ($rate as $rata) {
            $data = [
                'kupac_id' => $kupac_id,
                'jedinica_id' => $jedinica_id,
                'tip_jedinice' => $tip_jedinice,
                'datum_prodaje' => $datum_prodaje,
                'datum_rate' => $rata['datum_rate'],
                'procenat' => $rata['procenat'],
                'suma' => $rata['suma'],
                'status' => 'neplaceno',
                'uplata_id' => null
            ];
            
            $result = $planCrud->insert($data);
            if (!$result) {
                return ['success' => false, 'message' => 'Greška pri kreiranju rate'];
            }
        }
        
        // Mapiraj uplate na planirane rate nakon izmene
        include_once 'f_mapiraj_uplate_na_planove.php';
        mapirajSveUplateZaKupca($kupac_id);
        
        return ['success' => true, 'message' => 'Plan otplate je uspešno ažuriran'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}

/**
 * Briše plan otplate za određenu jedinicu
 * 
 * @param int $kupac_id ID kupca
 * @param int $jedinica_id ID jedinice
 * @param string $tip_jedinice Tip jedinice
 * @return array Rezultat operacije
 */
function obrisiPlanOtplate($kupac_id, $jedinica_id, $tip_jedinice) {
    try {
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        
        $result = $planCrud->delete([
            'kupac_id' => $kupac_id,
            'jedinica_id' => $jedinica_id,
            'tip_jedinice' => $tip_jedinice
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Plan otplate je uspešno obrisan'];
        } else {
            return ['success' => false, 'message' => 'Greška pri brisanju plana'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}

/**
 * Izračunava ukupnu cenu jedinice (isti kod kao u f_kreiraj_plan_otplate.php)
 */
function izracunajCenuJedinice($jedinica_id, $tip_jedinice) {
    $ukupna_cena = 0;
    
    switch ($tip_jedinice) {
        case 'stan':
            $stanCrud = new CRUD_ajax($_SESSION['godina']);
            $stanCrud->table = "stanovi";
            $stan = $stanCrud->select(['ukupna_cena'], ['id' => $jedinica_id]);
            
            if (!empty($stan)) {
                $ukupna_cena = (float)$stan[0]['ukupna_cena'];
            }
            break;
            
        case 'lokal':
            $lokalCrud = new CRUD_ajax($_SESSION['godina']);
            $lokalCrud->table = "lokali";
            $lokal = $lokalCrud->select(['ukupna_cena'], ['id' => $jedinica_id]);
            
            if (!empty($lokal)) {
                $ukupna_cena = $lokal[0]['ukupna_cena'];
            }
            break;
            
        case 'garaza':
            $garazaCrud = new CRUD_ajax($_SESSION['godina']);
            $garazaCrud->table = "garaze";
            $garaza = $garazaCrud->select(['cena_sa_pdv'], ['id' => $jedinica_id]);
            
            if (!empty($garaza)) {
                $ukupna_cena = $garaza[0]['cena_sa_pdv'];
            }
            break;
            
        case 'parking':
            $parkingCrud = new CRUD_ajax($_SESSION['godina']);
            $parkingCrud->table = "parking_mesta";
            $parking = $parkingCrud->select(['cena'], ['id' => $jedinica_id]);
            
            if (!empty($parking)) {
                $ukupna_cena = $parking[0]['cena'];
            }
            break;
    }
    
    return $ukupna_cena;
}
?>
