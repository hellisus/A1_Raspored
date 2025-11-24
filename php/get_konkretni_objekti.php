<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  echo '<option value="">Greška: Niste ulogovani</option>';
  exit;
}

$objekat_id = isset($_POST['objekat_id']) ? (int)$_POST['objekat_id'] : 0;
$tip_objekta = isset($_POST['tip_objekta']) ? $_POST['tip_objekta'] : '';

if (!$objekat_id || !$tip_objekta) {
  echo '<option value="">Greška: Nedostaju parametri</option>';
  exit;
}

try {
  $crud = new CRUD($_SESSION['godina']);
  
  // Odredi tabelu na osnovu tipa objekta
  $tabela = '';
  switch ($tip_objekta) {
    case 'stan':
      $tabela = 'stanovi';
      break;
    case 'garaza':
      $tabela = 'garaze';
      break;
    case 'lokal':
      $tabela = 'lokali';
      break;
    case 'parking':
      $tabela = 'parking_mesta';
      break;
    default:
      echo '<option value="">Greška: Nepoznat tip objekta</option>';
      exit;
  }
  
  $crud->table = $tabela;
  
  // Pronađi sve slobodne objekte tog tipa na izabranom objektu
  $uslov = ['objekat_id' => $objekat_id, 'prodat' => 0];
  $slobodni_objekti = $crud->select(['id', 'naziv'], $uslov);
  
  if (empty($slobodni_objekti)) {
    echo '<option value="">Nema slobodnih ' . $tip_objekta . ' na ovom objektu</option>';
  } else {
    echo '<option value="">Izaberite ' . $tip_objekta . '</option>';
    foreach ($slobodni_objekti as $objekat) {
      $naziv = htmlspecialchars($objekat['naziv']);
      $id = $objekat['id'];
      echo "<option value=\"$id\">$naziv</option>";
    }
  }
  
} catch (Exception $e) {
  echo '<option value="">Greška: ' . htmlspecialchars($e->getMessage()) . '</option>';
}
?>
