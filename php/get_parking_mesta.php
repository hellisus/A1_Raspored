<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
    try {
        $objekat_id = $_POST['objekat_id'];
        
        $crud = new CRUD($_SESSION['godina']);
        $crud->table = "parking_mesta";
        
        // Pronađi sva neprodata parking mesta za objekat
        $parking_mesta = $crud->select(
            ['id', 'naziv', 'cena'], 
            ['objekat_id' => $objekat_id, 'prodat' => 0]
        );
        
        echo json_encode([
            'success' => true,
            'parking_mesta' => $parking_mesta
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
