<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'config_ajax.php';
include_once 'CRUD_ajax.php';

// AJAX handler - pokreće se SAMO ako su prisutni svi potrebni parametri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kupac_id']) && isset($_POST['jedinica_id']) && isset($_POST['tip_jedinice'])) {
    $kupac_id = $_POST['kupac_id'];
    $jedinica_id = $_POST['jedinica_id'];
    $tip_jedinice = $_POST['tip_jedinice'];
    
    $result = mapirajUplateNaPlanove($kupac_id, $jedinica_id, $tip_jedinice);
    echo json_encode($result);
    exit;
}

/**
 * Mapira uplate na planirane rate za određenu jedinicu
 * 
 * @param int $kupac_id ID kupca
 * @param int $jedinica_id ID jedinice
 * @param string $tip_jedinice Tip jedinice
 * @return array Rezultat operacije
 */
function mapirajUplateNaPlanove($kupac_id, $jedinica_id, $tip_jedinice) {
    try {
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        
        // 1. Reset svih uplaćenih iznosa za ovu jedinicu
        $planCrud->update(
            ['uplaceno' => 0.00, 'status' => 'neplaceno'],
            [
                'kupac_id' => $kupac_id,
                'jedinica_id' => $jedinica_id,
                'tip_jedinice' => $tip_jedinice
            ]
        );
        
        // 2. Dohvati sve planirane rate sortirane po datumu
        $planovi = $planCrud->select(
            [], 
            [], 
            "SELECT * FROM planovi_otplate WHERE kupac_id = ? AND jedinica_id = ? AND tip_jedinice = ? ORDER BY datum_rate ASC",
            [$kupac_id, $jedinica_id, $tip_jedinice]
        );
        
        if (empty($planovi)) {
            return ['success' => true, 'message' => 'Nema planiranih rata za mapiranje'];
        }
        
        // 3. Dohvati sve uplate sortirane po datumu
        $uplataCrud = new CRUD_ajax($_SESSION['godina']);
        $uplataCrud->table = "uplata";
        $uplate = $uplataCrud->select(
            [], 
            [], 
            "SELECT * FROM uplata WHERE id_kupca = ? AND tip_jedinice = ? AND id_jedinice = ? ORDER BY datum_uplate ASC",
            [$kupac_id, $tip_jedinice, $jedinica_id]
        );
        
        if (empty($uplate)) {
            return ['success' => true, 'message' => 'Nema uplata za mapiranje'];
        }
        
        // 4. Mapiraj svaku uplatu na prvu dostupnu (nepotpuno plaćenu) ratu
        $index_planova = 0;
        $ukupno_mapirano = 0;
        
        foreach ($uplate as $uplata) {
            $iznos_uplate = floatval($uplata['iznos_uplate']);
            
            // Pronađi prvu nepotpuno plaćenu ratu
            while ($index_planova < count($planovi)) {
                $plan = $planovi[$index_planova];
                $trenutno_uplaceno = floatval($plan['uplaceno'] ?? 0);
                $suma_rate = floatval($plan['suma']);
                $preostalo = $suma_rate - $trenutno_uplaceno;
                
                if ($preostalo > 0) {
                    // Dodeli uplatu ovoj rati
                    $iznos_za_ovu_ratu = min($iznos_uplate, $preostalo);
                    $novo_uplaceno = $trenutno_uplaceno + $iznos_za_ovu_ratu;
                    
                    // Ažuriraj plan u bazi
                    $planCrud->update(
                        ['uplaceno' => $novo_uplaceno],
                        ['id' => $plan['id']]
                    );
                    
                    // Ažuriraj lokalni niz
                    $planovi[$index_planova]['uplaceno'] = $novo_uplaceno;
                    
                    // Ažuriraj status rate
                    $novi_status = 'placeno';
                    if ($novo_uplaceno < $suma_rate) {
                        $novi_status = 'delimicno_placeno';
                    }
                    
                    $planCrud->update(
                        ['status' => $novi_status],
                        ['id' => $plan['id']]
                    );
                    
                    $iznos_uplate -= $iznos_za_ovu_ratu;
                    $ukupno_mapirano += $iznos_za_ovu_ratu;
                    
                    // Ako je uplata potpuno dodeljena, pređi na sledeću
                    if ($iznos_uplate <= 0.01) { // 0.01 tolerance za floating point
                        break;
                    }
                }
                
                $index_planova++;
            }
            
            // Ako nema više planova, prekini
            if ($index_planova >= count($planovi)) {
                break;
            }
        }
        
        return [
            'success' => true, 
            'message' => "Uspešno mapirano {$ukupno_mapirano} € na planirane rate",
            'mapirano' => $ukupno_mapirano
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}

/**
 * Mapira sve uplate za kupca na sve planirane rate
 * 
 * @param int $kupac_id ID kupca
 * @return array Rezultat operacije
 */
function mapirajSveUplateZaKupca($kupac_id) {
    try {
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        
        // Dohvati sve jedinstvene kombinacije jedinica za kupca
        $jedinice = $planCrud->select(
            [], 
            [], 
            "SELECT DISTINCT jedinica_id, tip_jedinice FROM planovi_otplate WHERE kupac_id = ?",
            [$kupac_id]
        );
        
        $ukupno_mapirano = 0;
        $greske = [];
        
        foreach ($jedinice as $jedinica) {
            $result = mapirajUplateNaPlanove(
                $kupac_id, 
                $jedinica['jedinica_id'], 
                $jedinica['tip_jedinice']
            );
            
            if ($result['success']) {
                $ukupno_mapirano += $result['mapirano'] ?? 0;
            } else {
                $greske[] = $result['message'];
            }
        }
        
        if (empty($greske)) {
            return [
                'success' => true, 
                'message' => "Uspešno mapirano {$ukupno_mapirano} € za sve jedinice",
                'mapirano' => $ukupno_mapirano
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Greške pri mapiranju: ' . implode(', ', $greske),
                'mapirano' => $ukupno_mapirano
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}
?>
