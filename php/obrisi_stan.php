<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];
  
  try {
    $crud = new CRUD($_SESSION['godina']);
    $crud->table = "stanovi";
    
    // Proveri da li stan postoji
    $stan = $crud->select(['id'], ['id' => $id]);
    
    if (count($stan) > 0) {
      // Obriši stan
      $crud->delete(['id' => $id]);
      
      // Proveri da li je stan obrisan
      $proveri = $crud->select(['id'], ['id' => $id]);
      
      if (count($proveri) === 0) {
        $_SESSION['success_message'] = "Stan je uspešno obrisan.";
      } else {
        $_SESSION['error_message'] = "Greška pri brisanju stana.";
      }
    } else {
      $_SESSION['error_message'] = "Stan nije pronađen.";
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = "Greška: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Neispravan ID stana.";
}

header("location: lista_stanova.php");
exit();
?>
