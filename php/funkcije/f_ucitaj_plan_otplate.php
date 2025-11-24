<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'config_ajax.php';
include_once 'CRUD_ajax.php';
include_once 'plan_otplate_utils.php';

// AJAX handler - pokreće se SAMO ako su prisutni svi potrebni parametri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jedinica_id']) && isset($_POST['tip_jedinice'])) {
    $jedinica_id = $_POST['jedinica_id'];
    $tip_jedinice = $_POST['tip_jedinice'];
    
    // Debug log
    error_log("f_ucitaj_plan_otplate.php - jedinica_id: $jedinica_id, tip_jedinice: $tip_jedinice");
    
    try {
        $result = ucitajPlanOtplate($jedinica_id, $tip_jedinice);
        echo json_encode($result);
    } catch (Exception $e) {
        error_log("f_ucitaj_plan_otplate.php error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Greška: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Učitava plan otplate za određenu jedinicu
 * 
 * @param int $jedinica_id ID jedinice
 * @param string $tip_jedinice Tip jedinice
 * @return array Plan otplate sa ratama
 */
function ucitajPlanOtplate($jedinica_id, $tip_jedinice) {
    try {
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        
        $plan = $planCrud->select(
            [], 
            [], 
            "SELECT * FROM planovi_otplate WHERE jedinica_id = ? AND tip_jedinice = ? ORDER BY datum_rate ASC, id ASC",
            [$jedinica_id, $tip_jedinice]
        );

        $trenutna_cena = planOtplateIzracunajCenuJedinice((int) $jedinica_id, $tip_jedinice);

        if (!empty($plan)) {
            $plan_suma = array_reduce($plan, function ($carry, $stavka) {
                return $carry + (float) ($stavka['suma'] ?? 0);
            }, 0.0);

            $plan_validan = $trenutna_cena <= 0 || abs($plan_suma - $trenutna_cena) <= 0.01;

            if ($plan_validan) {
                return [
                    'success' => true,
                    'plan' => $plan,
                    'auto_generated' => false,
                    'datum_prodaje' => $plan[0]['datum_prodaje'] ?? null,
                    'ukupna_cena' => $trenutna_cena > 0 ? $trenutna_cena : $plan_suma,
                ];
            }
        }

        $kupac_id = planOtplateDohvatiKupacIdIzJedinice((int) $jedinica_id, $tip_jedinice);
        if (!$kupac_id) {
            return [
                'success' => false,
                'message' => 'Kupac nije pronađen za ovu jedinicu',
                'plan' => []
            ];
        }

        $defaultPlan = planOtplateGenerisiPlan($kupac_id, (int) $jedinica_id, $tip_jedinice, null);
        if (!$defaultPlan['success']) {
            return [
                'success' => false,
                'message' => $defaultPlan['message'] ?? 'Plan otplate nije dostupan',
                'plan' => []
            ];
        }

        return [
            'success' => true,
            'plan' => $defaultPlan['plan'],
            'auto_generated' => true,
            'datum_prodaje' => $defaultPlan['datum_prodaje'] ?? null,
            'ukupna_cena' => $defaultPlan['ukupna_cena'] ?? $trenutna_cena,
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Greška pri učitavanju plana: ' . $e->getMessage(),
            'plan' => []
        ];
    }
}

/**
 * Učitava sve planove otplate za kupca
 * 
 * @param int $kupac_id ID kupca
 * @return array Svi planovi otplate kupca
 */
function ucitajSvePlanoveKupca($kupac_id) {
    try {
        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";
        
        $planovi = $planCrud->select(
            [], 
            [], 
            "SELECT * FROM planovi_otplate WHERE kupac_id = ? ORDER BY tip_jedinice, jedinica_id, id ASC",
            [$kupac_id]
        );
        
        return [
            'success' => true,
            'planovi' => $planovi
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Greška pri učitavanju planova: ' . $e->getMessage(),
            'planovi' => []
        ];
    }
}
?>
