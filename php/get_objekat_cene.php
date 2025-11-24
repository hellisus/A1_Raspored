<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tip_objekta']) && isset($_POST['objekat_id'])) {
  try {
    $tip_objekta = $_POST['tip_objekta'];
    $objekat_id = (int)$_POST['objekat_id'];
    
    $crud = new CRUD($_SESSION['godina']);
    $data = [];
    
    switch ($tip_objekta) {
      case 'stan':
        $crud->table = "stanovi";
        $stan = $crud->select(['*'], ['id' => $objekat_id]);
        if (!empty($stan)) {
          $s = $stan[0];
          $data = [
            'success' => true,
            'tip' => 'stan',
            'kvadratura' => (float)($s['kvadratura'] ?? 0),
            'cena_po_m2' => (float)($s['cena_po_m2'] ?? 0),
            'realna_cena_po_m2' => (float)($s['realna_cena_po_m2'] ?? 0),
            'pdv' => (float)($s['pdv'] ?? 0),
            'pdv_procenat' => 0, // Izračunaj iz pdv i cene
            'rabat' => (float)($s['rabat'] ?? 0),
            'ukupna_cena' => (float)($s['ukupna_cena'] ?? 0)
          ];
          
          // Izračunaj PDV procenat
          if ($data['ukupna_cena'] > 0 && $data['kvadratura'] > 0) {
            $osnovna_cena = ($data['ukupna_cena'] - $data['pdv']);
            if ($osnovna_cena > 0) {
              $data['pdv_procenat'] = round(($data['pdv'] / $osnovna_cena) * 100, 2);
            }
          }
        }
        break;
        
      case 'lokal':
        $crud->table = "lokali";
        $lokal = $crud->select(['*'], ['id' => $objekat_id]);
        if (!empty($lokal)) {
          $l = $lokal[0];
          $kvadratura = (float)($l['kvadratura'] ?? 0);
          $cena_po_m2 = (float)($l['cena_po_m2'] ?? 0);
          $pdv_suma = (float)($l['pdv_suma'] ?? 0);
          $ukupna_cena = (float)($l['ukupna_cena'] ?? 0);
          $osnovna_cena_bez_pdv = 0;
          $pdv_procenat = 0;
          
          if ($kvadratura > 0 && $cena_po_m2 > 0) {
            $ukupna_sa_pdv = $kvadratura * $cena_po_m2;
            $osnovna_cena_bez_pdv = $ukupna_sa_pdv - $pdv_suma;
            if ($osnovna_cena_bez_pdv > 0) {
              $pdv_procenat = round(($pdv_suma / $osnovna_cena_bez_pdv) * 100, 2);
            }
          }
          
          $data = [
            'success' => true,
            'tip' => 'lokal',
            'kvadratura' => $kvadratura,
            'cena_po_m2' => $cena_po_m2,
            'pdv' => $pdv_suma,
            'pdv_procenat' => $pdv_procenat,
            'osnovna_cena' => $osnovna_cena_bez_pdv / ($kvadratura > 0 ? $kvadratura : 1),
            'ukupna_cena' => $ukupna_cena > 0 ? $ukupna_cena : ($kvadratura * $cena_po_m2),
            'rabat' => (float)($l['rabat'] ?? 0)
          ];
        }
        break;
        
      case 'garaza':
        $crud->table = "garaze";
        $garaza = $crud->select(['*'], ['id' => $objekat_id]);
        if (!empty($garaza)) {
          $g = $garaza[0];
          $data = [
            'success' => true,
            'tip' => 'garaza',
            'cena_sa_pdv' => (float)($g['cena_sa_pdv'] ?? 0),
            'cena' => (float)($g['cena'] ?? 0),
            'pdv' => (float)($g['pdv'] ?? 0)
          ];
        }
        break;
        
      case 'parking':
        $crud->table = "parking_mesta";
        $parking = $crud->select(['*'], ['id' => $objekat_id]);
        if (!empty($parking)) {
          $p = $parking[0];
          $data = [
            'success' => true,
            'tip' => 'parking',
            'cena' => (float)($p['cena'] ?? 0)
          ];
        }
        break;
    }
    
    if (empty($data)) {
      echo json_encode(['success' => false, 'message' => 'Objekat nije pronađen']);
    } else {
      echo json_encode($data);
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Nevalidni podaci']);
}
?>
