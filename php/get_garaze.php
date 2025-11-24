<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Ime'])) {
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
    try {
        $podatci = new CRUD($_SESSION['godina']);
        $objekat_id = $_POST['objekat_id'];
        
        // Uzmi garaže za izabrani objekat koje nisu povezane sa stanovima
        $garaze = $podatci->select(['*'], [], "SELECT * FROM garaze WHERE objekat_id = ? AND stan_id IS NULL", [$objekat_id]);
        
        echo json_encode([
            'success' => true,
            'garaze' => $garaze
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Nema garaža za izabrani objekat : ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Neispravni zahtev'
    ]);
}
?>
