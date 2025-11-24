<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];
  
  try {
    $crud = new CRUD($_SESSION['godina']);
    $crud->table = "garaze";
    
    // Proveri da li garaža postoji
    $garaza = $crud->select(['id'], ['id' => $id]);
    
    if (count($garaza) > 0) {
      // Obriši garažu
      $crud->delete(['id' => $id]);
      
      // Proveri da li je garaža obrisana
      $proveri = $crud->select(['id'], ['id' => $id]);
      
      if (count($proveri) === 0) {
        $_SESSION['success_message'] = "Garaža je uspešno obrisana.";
      } else {
        $_SESSION['error_message'] = "Greška pri brisanju garaže.";
      }
    } else {
      $_SESSION['error_message'] = "Garaža nije pronađena.";
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = "Greška: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Neispravan ID garaže.";
}

header("location: lista_garaza.php");
exit();
?>
