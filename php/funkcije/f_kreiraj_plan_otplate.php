<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'config_ajax.php';
include_once 'CRUD_ajax.php';
include_once 'plan_otplate_utils.php';

// AJAX handler - pokreće se SAMO ako su prisutni svi potrebni parametri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jedinica_id']) && isset($_POST['tip_jedinice'])) {
    $jedinica_id = (int) $_POST['jedinica_id'];
    $tip_jedinice = $_POST['tip_jedinice'];
    $datum_prodaje = $_POST['datum_prodaje'] ?? date('Y-m-d');

    $kupac_id = planOtplateDohvatiKupacIdIzJedinice($jedinica_id, $tip_jedinice);
    if (!$kupac_id) {
        echo json_encode(['success' => false, 'message' => 'Kupac nije pronađen za ovu jedinicu']);
        exit;
    }

    $result = kreirajDefaultPlanOtplate($kupac_id, $jedinica_id, $tip_jedinice, $datum_prodaje);
    echo json_encode($result);
    exit;
}

/**
 * Kreira default plan otplate na osnovu tipa kupca
 * 
 * @param int $kupac_id ID kupca
 * @param int $jedinica_id ID jedinice (stan, lokal, garaža, parking)
 * @param string $tip_jedinice Tip jedinice ('stan', 'lokal', 'garaza', 'parking')
 * @param string $datum_prodaje Datum prodaje (Y-m-d format)
 * @return array Rezultat operacije
 */
function kreirajDefaultPlanOtplate($kupac_id, $jedinica_id, $tip_jedinice, $datum_prodaje) {
    try {
        $planResult = planOtplateGenerisiPlan($kupac_id, $jedinica_id, $tip_jedinice, $datum_prodaje);
        if (!$planResult['success']) {
            return $planResult;
        }

        $planCrud = new CRUD_ajax($_SESSION['godina']);
        $planCrud->table = "planovi_otplate";

        foreach ($planResult['plan'] as $rata) {
            $data = [
                'kupac_id' => $kupac_id,
                'jedinica_id' => $jedinica_id,
                'tip_jedinice' => $tip_jedinice,
                'datum_prodaje' => $planResult['datum_prodaje'],
                'datum_rate' => $rata['datum_rate'],
                'procenat' => $rata['procenat'],
                'suma' => $rata['suma'],
                'status' => $rata['status'] ?? 'neplaceno',
                'uplata_id' => null
            ];

            $result = $planCrud->insert($data);
            if (!$result) {
                return ['success' => false, 'message' => 'Greška pri kreiranju rate'];
            }
        }

        return ['success' => true, 'message' => 'Plan otplate je uspešno kreiran'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
    }
}
?>
