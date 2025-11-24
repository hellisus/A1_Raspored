<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tip_jedinice']) && isset($_POST['id_jedinice']) && isset($_POST['kupac_id'])) {
    try {
        $tip_jedinice = $_POST['tip_jedinice'];
        $id_jedinice = $_POST['id_jedinice'];
        $kupac_id = $_POST['kupac_id'];
        
        // Pronađi cenu objekta
        $cena_objekta = 0;
        $crud = new CRUD($_SESSION['godina']);
        
        if ($tip_jedinice == 'stan') {
            $crud->table = "stanovi";
            $objekat = $crud->select(['ukupna_cena'], ['id' => $id_jedinice]);
            $cena_objekta = !empty($objekat) ? (float)$objekat[0]['ukupna_cena'] : 0;
        } elseif ($tip_jedinice == 'lokal') {
            $crud->table = "lokali";
            $objekat = $crud->select(['ukupna_cena'], ['id' => $id_jedinice]);
            $cena_objekta = !empty($objekat) ? (float)$objekat[0]['ukupna_cena'] : 0;
        } elseif ($tip_jedinice == 'garaza') {
            $crud->table = "garaze";
            $objekat = $crud->select(['cena_sa_pdv', 'cena'], ['id' => $id_jedinice]);
            if (!empty($objekat)) {
                $cena_objekta = isset($objekat[0]['cena_sa_pdv']) && $objekat[0]['cena_sa_pdv'] !== null
                    ? (float)$objekat[0]['cena_sa_pdv']
                    : (float)($objekat[0]['cena'] ?? 0);
            } else {
                $cena_objekta = 0;
            }
        } elseif ($tip_jedinice == 'parking') {
            $crud->table = "parking_mesta";
            $objekat = $crud->select(['cena'], ['id' => $id_jedinice]);
            $cena_objekta = !empty($objekat) ? (float)$objekat[0]['cena'] : 0;
        }
        
        // Pronađi već uplaćeno za taj objekat
        $uplataCrud = new CRUD($_SESSION['godina']);
        $uplataCrud->table = "uplata";
        $uplate = $uplataCrud->select(['iznos_uplate'], [
            'id_kupca' => $kupac_id,
            'tip_jedinice' => $tip_jedinice,
            'id_jedinice' => $id_jedinice
        ]);
        
        $uplaceno_za_objekat = 0;
        foreach ($uplate as $uplata) {
            $uplaceno_za_objekat += $uplata['iznos_uplate'];
        }
        
        echo json_encode([
            'success' => true,
            'cena_objekta' => $cena_objekta,
            'uplaceno_za_objekat' => $uplaceno_za_objekat
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request'
    ]);
}
?>
