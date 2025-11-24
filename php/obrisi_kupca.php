<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];
  
  try {
    $crud = new CRUD($_SESSION['godina']);
    $crud->table = "kupci";
    
    // Proveri da li kupac postoji
    $kupac = $crud->select(['id'], ['id' => $id]);
    
    if (count($kupac) > 0) {
      // Obriši kupca
      $crud->delete(['id' => $id]);
      
      // Proveri da li je kupac obrisan
      $proveri = $crud->select(['id'], ['id' => $id]);
      
      if (count($proveri) === 0) {
        $_SESSION['success_message'] = "Kupac je uspešno obrisan.";
      } else {
        $_SESSION['error_message'] = "Greška pri brisanju kupca.";
      }
    } else {
      $_SESSION['error_message'] = "Kupac nije pronađen.";
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = "Greška: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Neispravan ID kupca.";
}

header("location: lista_kupaca.php");
exit();
?>
