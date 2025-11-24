<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];
  
  try {
    $crud = new CRUD($_SESSION['godina']);
    $crud->table = "parking_mesta";
    
    // Proveri da li parking mesto postoji
    $parking = $crud->select(['id'], ['id' => $id]);
    
    if (count($parking) > 0) {
      // Obriši parking mesto
      $crud->delete(['id' => $id]);
      
      // Proveri da li je parking mesto obrisano
      $proveri = $crud->select(['id'], ['id' => $id]);
      
      if (count($proveri) === 0) {
        $_SESSION['success_message'] = "Parking mesto je uspešno obrisano.";
      } else {
        $_SESSION['error_message'] = "Greška pri brisanju parking mesta.";
      }
    } else {
      $_SESSION['error_message'] = "Parking mesto nije pronađeno.";
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = "Greška: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Neispravan ID parking mesta.";
}

header("location: lista_parking_mesta.php");
exit();
?>
