<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];
  
  try {
    $crud = new CRUD($_SESSION['godina']);
    $crud->table = "lokali";
    
    // Proveri da li lokal postoji
    $lokal = $crud->select(['id'], ['id' => $id]);
    
    if (count($lokal) > 0) {
      // Obriši lokal
      $crud->delete(['id' => $id]);
      
      // Proveri da li je lokal obrisan
      $proveri = $crud->select(['id'], ['id' => $id]);
      
      if (count($proveri) === 0) {
        $_SESSION['success_message'] = "Lokal je uspešno obrisan.";
      } else {
        $_SESSION['error_message'] = "Greška pri brisanju lokala.";
      }
    } else {
      $_SESSION['error_message'] = "Lokal nije pronađen.";
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = "Greška: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Neispravan ID lokala.";
}

header("location: lista_lokala.php");
exit();
?>
